const { normalizeText, stripHtml } = require('./text');

function asArray(value) {
  if (!value) return [];
  return Array.isArray(value) ? value.filter(Boolean) : String(value).split(/\r?\n|,/).map((v) => v.trim()).filter(Boolean);
}

function containsAny(text, terms) {
  const normalized = normalizeText(text);
  return asArray(terms).some((term) => normalized.includes(normalizeText(term)));
}

function matchedTerms(text, terms) {
  const normalized = normalizeText(text);
  return asArray(terms).filter((term) => {
    const normalizedTerm = normalizeText(term);
    if (!normalizedTerm) return false;
    if (normalizedTerm.length <= 2) {
      return new RegExp(`(^|\\s)${normalizedTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(\\s|$)`).test(normalized);
    }
    return normalized.includes(normalizedTerm);
  });
}

function countMatches(text, terms) {
  return matchedTerms(text, terms).length;
}

function tooSenior(jobText, profile) {
  const seniority = normalizeText(profile.senioridade || '');
  if (seniority.includes('junior') || seniority.includes('estagio')) {
    return containsAny(jobText, ['senior', 'sr', 'lead', 'principal', 'staff']);
  }
  if (seniority.includes('pleno')) {
    return containsAny(jobText, ['principal', 'staff']);
  }
  return false;
}

function scoreJob(job, profile) {
  const description = stripHtml(job.descricao || '');
  const text = `${job.titulo || ''} ${job.empresa || ''} ${job.localizacao || ''} ${description}`;
  const title = job.titulo || '';
  const positives = [];
  const alerts = [];
  let score = 0;

  const targetTerms = [...asArray(profile.cargo_alvo), ...asArray(profile.palavras_chave)];
  const targetTitleMatches = matchedTerms(title, targetTerms);
  const targetTextMatches = matchedTerms(text, targetTerms);
  if (targetTitleMatches.length) {
    score += 26;
    positives.push(`titulo combina com: ${targetTitleMatches.join(', ')}`);
  } else if (targetTextMatches.length) {
    score += 14;
    positives.push(`descricao combina com: ${targetTextMatches.slice(0, 5).join(', ')}`);
  }

  const skills = matchedTerms(text, profile.habilidades);
  if (skills.length) {
    const add = Math.min(30, Math.round((skills.length / Math.max(asArray(profile.habilidades).length, 1)) * 30));
    score += add;
    positives.push(`habilidades encontradas: ${skills.join(', ')}`);
  } else {
    score -= 8;
    alerts.push('nenhuma habilidade do preferencia apareceu claramente');
  }

  const local = normalizeText(`${job.localizacao || ''} ${job.descricao || ''}`);
  const profileLocal = normalizeText(profile.localizacao || '');
  if ((profile.aceita_remoto && containsAny(local, ['remote', 'remoto', 'anywhere', 'worldwide'])) || (profileLocal && local.includes(profileLocal))) {
    score += 15;
    positives.push('localizacao/remoto compativel');
  }

  if (profile.senioridade && containsAny(text, [profile.senioridade])) {
    score += 12;
    positives.push(`senioridade citada: ${profile.senioridade}`);
  }

  const tools = matchedTerms(text, profile.ferramentas);
  if (tools.length) {
    score += Math.min(12, 4 + tools.length * 3);
    positives.push(`ferramentas encontradas: ${tools.join(', ')}`);
  }

  const experiences = matchedTerms(text, asArray(profile.experiencias).map((item) => String(item).slice(0, 80)));
  if (experiences.length) {
    score += 8;
    positives.push('experiencia descrita no preferencia aparece na Imóvel');
  }

  const forbidden = matchedTerms(text, profile.palavras_proibidas);
  if (!forbidden.length) {
    score += 6;
  } else {
    score -= 30;
    alerts.push(`palavras proibidas: ${forbidden.join(', ')}`);
  }

  const salesTerms = ['sales', 'vendas', 'prospectar', 'comercial', 'closer', 'sdr', 'bdr'];
  if (containsAny(text, salesTerms) && !containsAny([...asArray(profile.cargo_alvo), ...asArray(profile.habilidades)].join(' '), ['vendas', 'sales', 'comercial'])) {
    score -= 20;
    alerts.push('parece uma Imóvel de vendas/comercial');
  }

  if (tooSenior(text, profile)) {
    score -= 20;
    alerts.push('senioridade aparenta estar acima do perfil');
  }

  if (!profile.aceita_remoto && containsAny(text, ['presencial', 'on-site', 'onsite']) && profileLocal && !local.includes(profileLocal)) {
    score -= 15;
    alerts.push('Imóvel parece presencial em local incompatibivel');
  }

  if (stripHtml(job.descricao || '').length < 180) {
    score -= 12;
    alerts.push('descricao curta ou pouco informativa');
  }

  if (!targetTitleMatches.length && !skills.length && !tools.length) {
    score -= 18;
    alerts.push('pouca relacao objetiva com o preferencia');
  }

  score = Math.max(0, Math.min(100, score));
  const missingSkills = asArray(profile.habilidades).filter((skill) => !skills.includes(skill)).slice(0, 8);
  const resumo = [
    positives.length ? `Pontos positivos: ${positives.join('; ')}.` : 'Pontos positivos: poucos sinais claros de aderencia.',
    alerts.length ? `Alertas: ${alerts.join('; ')}.` : 'Alertas: nenhum alerta forte encontrado.',
    skills.length ? `Habilidades que bateram: ${skills.join(', ')}.` : 'Habilidades que bateram: nenhuma habilidade detectada.',
    missingSkills.length ? `Habilidades ausentes: ${missingSkills.join(', ')}.` : 'Habilidades ausentes: nenhuma relevante.'
  ].join('\n');

  return { score, resumo, skills, alerts, positives };
}

module.exports = { normalizeText, containsAny, countMatches, matchedTerms, scoreJob };
