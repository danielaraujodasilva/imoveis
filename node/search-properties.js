const crypto = require('crypto');
const axios = require('axios');
const cheerio = require('cheerio');
const { pool } = require('./lib/db');
const { lines } = require('./lib/text');
const { normalizeText } = require('./lib/matcher');

const SOURCE_SITES = {
  'Mercado Livre': 'lista.mercadolivre.com.br/imoveis',
  OLX: 'olx.com.br/imoveis',
  'Zap Imoveis': 'zapimoveis.com.br',
  'Viva Real': 'vivareal.com.br',
  Imovelweb: 'imovelweb.com.br',
  'Chaves na Mao': 'chavesnamao.com.br',
  QuintoAndar: 'quintoandar.com.br',
  Loft: 'loft.com.br',
  Netimoveis: 'netimoveis.com',
  'Lugar Certo': 'lugarcerto.com.br',
  Wimoveis: 'wimoveis.com.br',
  'Casa Mineira': 'casamineira.com.br',
  Properati: 'properati.com.br'
};
const SITEMAP_SOURCES = {
  'Chaves na Mao': [
    'https://www.chavesnamao.com.br/sitemap-venda-imoveis-01.xml.gz',
    'https://www.chavesnamao.com.br/sitemap-venda-imoveis-02.xml.gz',
    'https://www.chavesnamao.com.br/sitemap-aluguel-imoveis-01.xml.gz'
  ],
  Netimoveis: [
    'https://www.netimoveis.com/sitemaps/sitemap-imoveis_1.xml',
    'https://www.netimoveis.com/sitemaps/sitemap-imoveis_2.xml',
    'https://www.netimoveis.com/sitemaps/sitemap-imoveis_3.xml'
  ]
};
const DEFAULT_SOURCES = Object.keys(SOURCE_SITES);
const DEBUG = process.argv.includes('--debug') || process.env.DEBUG_PROPERTIES === '1';
const userArg = process.argv.find((arg) => arg.startsWith('--user='));
const USER_ID = userArg ? Number(userArg.split('=')[1]) : Number(process.env.IMOVEIS_USER_ID || 0);
const zlib = require('zlib');

function hashProperty(item) {
  return crypto.createHash('sha256').update(`${item.fonte}|${item.url}|${item.titulo}|${item.preco_texto}`).digest('hex');
}

function compact(value) {
  return String(value || '').replace(/\s+/g, ' ').trim();
}

function toMoney(value) {
  const match = String(value || '').match(/(?:R\$\s?|RS)[\d.]+(?:,\d{2})?/i);
  if (!match) return null;
  const number = Number(match[0].replace(/[^\d,]/g, '').replace(/\./g, '').replace(',', '.'));
  return Number.isFinite(number) && number > 0 ? number : null;
}

function firstNumber(text, patterns) {
  for (const pattern of patterns) {
    const match = String(text || '').match(pattern);
    if (match) return Number(String(match[1]).replace(',', '.'));
  }
  return null;
}

function decodeDuckUrl(href) {
  if (!href) return '';
  try {
    const url = new URL(href, 'https://duckduckgo.com');
    return url.searchParams.get('uddg') || url.href;
  } catch {
    return href;
  }
}

function buildQuery(term, search, site) {
  const parts = [
    `site:${site}`,
    search.tipo_negocio || 'venda',
    term,
    search.tipo_imovel,
    search.cidade,
    search.bairro,
    search.preco_max ? `ate ${search.preco_max}` : ''
  ].filter(Boolean);
  return parts.join(' ');
}

function parseSearchResult($, element, source, search) {
  const result = $(element);
  const linkEl = result.find('a.result__a').first();
  const title = compact(linkEl.text());
  const url = decodeDuckUrl(linkEl.attr('href'));
  const snippet = compact(result.find('.result__snippet').text());
  const text = compact(`${title} ${snippet}`);
  return {
    titulo: title || text.slice(0, 120),
    anunciante: source,
    tipo_negocio: search.tipo_negocio || 'venda',
    tipo_imovel: search.tipo_imovel || inferType(text),
    cidade: search.cidade || '',
    bairro: search.bairro || '',
    endereco: '',
    preco: toMoney(text),
    preco_texto: (text.match(/R\$\s?[\d.]+(?:,\d{2})?/i) || [''])[0],
    condominio: null,
    iptu: null,
    quartos: firstNumber(text, [/(\d+)\s+quarto/i, /(\d+)\s+dorm/i]),
    banheiros: firstNumber(text, [/(\d+)\s+banheiro/i]),
    vagas_garagem: firstNumber(text, [/(\d+)\s+vaga/i]),
    area_m2: firstNumber(text, [/(\d+(?:[,.]\d+)?)\s*m[²2]/i]),
    fonte: source,
    url,
    descricao: snippet || text,
    data_publicacao: '',
    raw_json: { title, snippet, url }
  };
}

function inferType(text) {
  const normalized = normalizeText(text);
  for (const type of ['apartamento', 'casa', 'terreno', 'sobrado', 'studio', 'kitnet', 'loja', 'sala comercial', 'chacara']) {
    if (normalized.includes(normalizeText(type))) return type;
  }
  return '';
}

function titleFromUrl(url) {
  try {
    const path = new URL(url).pathname;
    const parts = path.split('/').filter(Boolean).map((part) => decodeURIComponent(part));
    const slug = parts.find((part) => part !== 'imovel' && /venda|locacao|aluguel|quarto|casa|apartamento|terreno|sobrado|sala|comercial/i.test(part)) || parts.at(-2) || path;
    return compact(slug
      .replace(/[-_]+/g, ' ')
      .replace(/\bRS\s?\d+.*$/i, '')
      .replace(/\bid\b.*$/i, ''));
  } catch {
    return compact(String(url).replace(/[-_/]+/g, ' ')).slice(0, 140);
  }
}

function saneArea(value) {
  const number = Number(value);
  if (!Number.isFinite(number) || number <= 0) return null;
  if (number > 2000 && number % 1000 === 0) return number / 1000;
  return number > 5000 ? null : number;
}

function titleCase(value) {
  return compact(value)
    .split(' ')
    .map((part) => part ? part.charAt(0).toUpperCase() + part.slice(1).toLowerCase() : '')
    .join(' ');
}

function locationFromUrl(url) {
  try {
    const path = decodeURIComponent(new URL(url).pathname);
    const normalizedPath = path.replace(/[-_]+/g, ' ').replace(/\bRS\s?\d+.*$/i, '').replace(/\d+(?:[,.]\d+)?m2.*$/i, '');
    const ufMatch = normalizedPath.match(/\b(ac|al|ap|am|ba|ce|df|es|go|ma|mt|ms|mg|pa|pb|pr|pe|pi|rj|rn|rs|ro|rr|sc|sp|se|to)\b\s+(.+)$/i);
    if (ufMatch) {
      return `${ufMatch[1].toUpperCase()} - ${titleCase(ufMatch[2])}`;
    }
    const netimoveisMatch = path.match(/\/imovel\/[^/]+-(acre|alagoas|amapa|amazonas|bahia|ceara|distrito-federal|espirito-santo|goias|maranhao|mato-grosso|mato-grosso-do-sul|minas-gerais|para|paraiba|parana|pernambuco|piaui|rio-de-janeiro|rio-grande-do-norte|rio-grande-do-sul|rondonia|roraima|santa-catarina|sao-paulo|sergipe|tocantins)-([^/]+)\/\d+/i);
    if (netimoveisMatch) {
      return titleCase(`${netimoveisMatch[1]} ${netimoveisMatch[2]}`.replace(/-/g, ' '));
    }
  } catch {
    return '';
  }
  return '';
}

function parseUrlProperty(url, source, search) {
  const decoded = decodeURIComponent(url);
  const text = compact(titleFromUrl(decoded));
  const normalized = normalizeText(`${decoded} ${text}`);
  const operation = normalized.includes('aluguel') || normalized.includes('locacao') ? 'aluguel' : 'venda';
  return {
    titulo: text || `${source} - imovel`,
    anunciante: source,
    tipo_negocio: operation || search.tipo_negocio || 'venda',
    tipo_imovel: search.tipo_imovel || inferType(decoded),
    cidade: search.cidade || locationFromUrl(url),
    bairro: search.bairro || '',
    endereco: '',
    preco: toMoney(decoded),
    preco_texto: (decoded.match(/R\$\s?[\d.]+(?:,\d{2})?|RS\d+/i) || [''])[0].replace(/^RS/i, 'R$ '),
    condominio: null,
    iptu: null,
    quartos: firstNumber(decoded, [/(\d+)-quarto/i, /(\d+)-dorm/i, /(\d+)\s+quarto/i]),
    banheiros: firstNumber(decoded, [/(\d+)-banheiro/i]),
    vagas_garagem: firstNumber(decoded, [/(\d+)-vaga/i, /com-garagem/i]),
    area_m2: saneArea(firstNumber(decoded, [/(\d+(?:[,.]\d+)?)m2/i])),
    fonte: source,
    url,
    descricao: text,
    data_publicacao: '',
    raw_json: { url }
  };
}

async function fetchXml(url) {
  const response = await axios.get(url, {
    timeout: 30000,
    responseType: 'arraybuffer',
    headers: { 'User-Agent': 'Mozilla/5.0 Radar Imoveis/1.0' }
  });
  let body = Buffer.from(response.data);
  if (url.endsWith('.gz')) {
    body = zlib.gunzipSync(body);
  }
  return body.toString('utf8');
}

async function searchViaSitemap(source, term, search) {
  const maps = SITEMAP_SOURCES[source] || [];
  const wanted = [term, search.tipo_imovel, search.cidade, search.bairro]
    .filter(Boolean)
    .map((item) => normalizeText(item));
  const operation = normalizeText(search.tipo_negocio || '');
  const results = [];
  for (const sitemapUrl of maps) {
    const xml = await fetchXml(sitemapUrl);
    const $ = cheerio.load(xml, { xmlMode: true });
    const locs = $('loc').map((_, element) => $(element).text()).get();
    for (const loc of locs) {
      const normalized = normalizeText(decodeURIComponent(loc));
      if (operation === 'venda' && normalized.includes('locacao')) continue;
      if (operation === 'aluguel' && normalized.includes('venda')) continue;
      if (wanted.length && !wanted.every((part) => normalized.includes(part))) continue;
      results.push(parseUrlProperty(loc, source, search));
      if (results.length >= 25) return results;
    }
  }
  return results;
}

async function searchViaDuckDuckGo(source, term, search) {
  const site = SOURCE_SITES[source];
  if (!site) return [];
  const query = buildQuery(term, search, site);
  const response = await axios.get('https://html.duckduckgo.com/html/', {
    timeout: 20000,
    params: { q: query },
    headers: {
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Radar Imoveis/1.0',
      'Accept-Language': 'pt-BR,pt;q=0.9,en;q=0.8'
    }
  });
  const $ = cheerio.load(response.data);
  const rows = $('.result').toArray().slice(0, 12);
  return rows
    .map((row) => parseSearchResult($, row, source, search))
    .filter((item) => item.url && item.titulo);
}

async function runSource(source, term, search) {
  if (SITEMAP_SOURCES[source]) {
    return searchViaSitemap(source, term, search);
  }
  return searchViaDuckDuckGo(source, term, search);
}

function splitFilterTerms(value) {
  return String(value || '').split(/[\n,;|]+/).map((item) => item.trim()).filter(Boolean);
}

function propertyText(item) {
  return normalizeText(`${item.titulo || ''} ${item.anunciante || ''} ${item.cidade || ''} ${item.bairro || ''} ${item.descricao || ''}`);
}

function matchesFilters(item, search) {
  const text = propertyText(item);
  const required = splitFilterTerms(search.palavras_obrigatorias);
  const forbidden = splitFilterTerms(search.palavras_proibidas);
  if (required.length && !required.every((term) => text.includes(normalizeText(term)))) return false;
  if (forbidden.length && forbidden.some((term) => text.includes(normalizeText(term)))) return false;
  if (search.preco_min && item.preco && item.preco < Number(search.preco_min)) return false;
  if (search.preco_max && item.preco && item.preco > Number(search.preco_max)) return false;
  if (search.quartos_min && item.quartos && item.quartos < Number(search.quartos_min)) return false;
  if (search.area_min && item.area_m2 && item.area_m2 < Number(search.area_min)) return false;
  return true;
}

function scoreProperty(item, search) {
  let score = 50;
  const notes = [];
  if (search.preco_max && item.preco && item.preco <= Number(search.preco_max)) { score += 15; notes.push('dentro do teto de preco'); }
  if (search.bairro && normalizeText(item.bairro || item.descricao).includes(normalizeText(search.bairro))) { score += 15; notes.push('bairro desejado aparece no anuncio'); }
  if (search.quartos_min && item.quartos >= Number(search.quartos_min)) { score += 10; notes.push('quantidade de quartos atende ao filtro'); }
  if (search.area_min && item.area_m2 >= Number(search.area_min)) { score += 10; notes.push('area minima atendida'); }
  return { score: Math.max(0, Math.min(100, score)), summary: notes.length ? notes.join('; ') : 'anuncio salvo pelos filtros da busca' };
}

function selectedSources(value) {
  let parsed = [];
  try {
    parsed = value ? JSON.parse(value) : [];
  } catch {
    parsed = [];
  }
  return [...new Set([...parsed, ...DEFAULT_SOURCES])].filter((source) => SOURCE_SITES[source]);
}

async function main() {
  if (!USER_ID) throw new Error('Informe o usuario com --user=ID.');
  const db = pool();
  const [searches] = await db.execute('SELECT * FROM buscas_imoveis WHERE ativa = 1 AND user_id = ? ORDER BY id DESC', [USER_ID]);
  if (!searches.length) {
    console.log('Nenhuma busca imobiliaria ativa encontrada.');
    await db.end();
    return;
  }

  let inserted = 0;
  let ignored = 0;
  const failures = [];
  for (const search of searches) {
    const terms = lines(search.termos || 'imovel');
    const sources = selectedSources(search.fontes);
    console.log(`Busca "${search.nome || search.id}": ${sources.join(', ')} | termos: ${terms.join(', ')}`);
    for (const term of terms) {
      for (const source of sources) {
        try {
          const items = await runSource(source, term, search);
          if (!items.length) {
            failures.push(`${source} nao retornou resultados para "${term}"`);
          }
          let savedFromSource = 0;
          for (const item of items) {
            if (!matchesFilters(item, search)) { ignored += 1; continue; }
            const score = scoreProperty(item, search);
            const [result] = await db.execute(
              `INSERT IGNORE INTO imoveis (user_id, titulo, anunciante, tipo_negocio, tipo_imovel, cidade, bairro, endereco, preco, preco_texto, condominio, iptu, quartos, banheiros, vagas_garagem, area_m2, fonte, url, descricao, data_publicacao, hash_imovel, nota_oportunidade, resumo_oportunidade, raw_json)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
              [USER_ID, item.titulo || '', item.anunciante || '', item.tipo_negocio || search.tipo_negocio || 'venda', item.tipo_imovel || search.tipo_imovel || '', item.cidade || search.cidade || '', item.bairro || search.bairro || '', item.endereco || '', item.preco, item.preco_texto || '', item.condominio, item.iptu, item.quartos, item.banheiros, item.vagas_garagem, item.area_m2, item.fonte || source, item.url || '', item.descricao || '', item.data_publicacao || '', hashProperty(item), score.score, score.summary, JSON.stringify(item.raw_json || item)]
            );
            if (result.affectedRows) { inserted += 1; savedFromSource += 1; }
          }
          console.log(`${source}: ${items.length} brutos para "${term}". ${savedFromSource} novos salvos.`);
        } catch (error) {
          const status = error.response?.status ? `HTTP ${error.response.status}` : error.code || error.message;
          const warning = `${source} falhou para "${term}": ${status}`;
          failures.push(warning);
          if (DEBUG) console.error(warning);
        }
      }
    }
  }
  const uniqueFailures = [...new Set(failures)];
  const logMessage = `${inserted} imoveis novos salvos. ${ignored} ignorados pelos filtros.${uniqueFailures.length ? ` Alertas: ${uniqueFailures.length} falha(s) de fonte.` : ''}`;
  await db.execute('INSERT INTO logs_execucao_imoveis (user_id, tipo, mensagem) VALUES (?, ?, ?)', [USER_ID, 'search-properties', `${logMessage}\n${uniqueFailures.join('\n')}`.trim()]);
  console.log(logMessage);
  if (uniqueFailures.length) {
    console.log(`WARNINGS_JSON:${JSON.stringify(uniqueFailures)}`);
  }
  await db.end();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
