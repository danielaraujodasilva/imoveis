const crypto = require('crypto');
const axios = require('axios');
const cheerio = require('cheerio');
const { pool } = require('./lib/db');
const { lines } = require('./lib/text');
const { normalizeText } = require('./lib/matcher');

const DEFAULT_SOURCES = ['Mercado Livre', 'OLX', 'Zap Imoveis', 'Viva Real'];
const DEBUG = process.argv.includes('--debug') || process.env.DEBUG_PROPERTIES === '1';
const userArg = process.argv.find((arg) => arg.startsWith('--user='));
const USER_ID = userArg ? Number(userArg.split('=')[1]) : Number(process.env.IMOVEIS_USER_ID || 0);

function hashProperty(item) {
  return crypto.createHash('sha256').update(`${item.fonte}|${item.url}|${item.titulo}|${item.preco_texto}`).digest('hex');
}

function slug(value) {
  return normalizeText(value || '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}

function toMoney(value) {
  const digits = String(value || '').replace(/[^\d,]/g, '').replace(/\./g, '').replace(',', '.');
  const number = Number(digits);
  return Number.isFinite(number) && number > 0 ? number : null;
}

function firstNumber(text, patterns) {
  for (const pattern of patterns) {
    const match = String(text || '').match(pattern);
    if (match) return Number(match[1]);
  }
  return null;
}

function parseCard($, element, source, fallback = {}) {
  const card = $(element);
  const link = card.find('a[href]').first().attr('href') || card.attr('href') || '';
  const text = card.text().replace(/\s+/g, ' ').trim();
  const title = card.find('h2,h3,[class*="title"],[class*="Title"]').first().text().replace(/\s+/g, ' ').trim() || text.slice(0, 120);
  const priceText = card.find('[class*="price"],[class*="Price"]').first().text().replace(/\s+/g, ' ').trim() || (text.match(/R\$\s?[\d.,]+/) || [''])[0];
  return {
    titulo: title,
    anunciante: '',
    tipo_negocio: fallback.tipo_negocio || 'venda',
    tipo_imovel: fallback.tipo_imovel || '',
    cidade: fallback.cidade || '',
    bairro: fallback.bairro || '',
    endereco: '',
    preco: toMoney(priceText),
    preco_texto: priceText,
    condominio: null,
    iptu: null,
    quartos: firstNumber(text, [/(\d+)\s+quarto/i, /(\d+)\s+dorm/i]),
    banheiros: firstNumber(text, [/(\d+)\s+banheiro/i]),
    vagas_garagem: firstNumber(text, [/(\d+)\s+vaga/i]),
    area_m2: firstNumber(text, [/(\d+(?:[,.]\d+)?)\s*m/i]),
    fonte: source,
    url: link.startsWith('http') ? link : '',
    descricao: text,
    data_publicacao: '',
    raw_json: { text }
  };
}

async function fetchHtml(url) {
  const response = await axios.get(url, {
    timeout: 20000,
    headers: {
      'User-Agent': 'Mozilla/5.0 Radar Imoveis/1.0',
      'Accept-Language': 'pt-BR,pt;q=0.9,en;q=0.8'
    }
  });
  return response.data;
}

async function searchMercadoLivre(term, search) {
  const query = slug([term, search.tipo_imovel, search.cidade, search.bairro].filter(Boolean).join(' '));
  const url = `https://lista.mercadolivre.com.br/imoveis/${query}`;
  const html = await fetchHtml(url);
  const $ = cheerio.load(html);
  const cards = $('.ui-search-result, li[class*="ui-search-layout__item"]').toArray().slice(0, 20);
  return cards.map((card) => parseCard($, card, 'Mercado Livre', search)).filter((item) => item.url || item.titulo);
}

async function searchOlx(term, search) {
  const query = encodeURIComponent([term, search.tipo_imovel, search.cidade, search.bairro].filter(Boolean).join(' '));
  const url = `https://www.olx.com.br/imoveis?q=${query}`;
  const html = await fetchHtml(url);
  const $ = cheerio.load(html);
  const cards = $('a[href*="/imovel"], section, li').toArray().slice(0, 30);
  return cards.map((card) => parseCard($, card, 'OLX', search)).filter((item) => item.url && item.titulo);
}

async function searchZapFamily(term, search, source) {
  const base = source === 'Zap Imoveis' ? 'https://www.zapimoveis.com.br' : 'https://www.vivareal.com.br';
  const operation = search.tipo_negocio === 'aluguel' ? 'aluguel' : 'venda';
  const city = slug(search.cidade || 'brasil');
  const type = slug(search.tipo_imovel || term || 'imoveis');
  const url = `${base}/${operation}/${type}/${city}/`;
  const html = await fetchHtml(url);
  const $ = cheerio.load(html);
  const cards = $('[data-cy*="rp-card"], article, li').toArray().slice(0, 20);
  return cards.map((card) => parseCard($, card, source, search)).filter((item) => item.titulo);
}

async function runSource(source, term, search) {
  if (source === 'Mercado Livre') return searchMercadoLivre(term, search);
  if (source === 'OLX') return searchOlx(term, search);
  if (source === 'Zap Imoveis') return searchZapFamily(term, search, source);
  if (source === 'Viva Real') return searchZapFamily(term, search, source);
  return [];
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
    const sources = search.fontes ? JSON.parse(search.fontes) : DEFAULT_SOURCES;
    console.log(`Busca "${search.nome || search.id}": ${sources.join(', ')} | termos: ${terms.join(', ')}`);
    for (const term of terms) {
      for (const source of sources) {
        try {
          const items = await runSource(source, term, search);
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
          const warning = `${source} falhou para "${term}": ${error.message}`;
          failures.push(warning);
          if (DEBUG) console.error(warning);
        }
      }
    }
  }
  const logMessage = `${inserted} imoveis novos salvos. ${ignored} ignorados pelos filtros.${failures.length ? ` Alertas: ${failures.length} fonte(s)/termo(s) falharam.` : ''}`;
  await db.execute('INSERT INTO logs_execucao_imoveis (user_id, tipo, mensagem) VALUES (?, ?, ?)', [USER_ID, 'search-properties', logMessage]);
  console.log(logMessage);
  await db.end();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
