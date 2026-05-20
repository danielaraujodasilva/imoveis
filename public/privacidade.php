<?php
$pageTitle = 'Privacidade';
require_once __DIR__ . '/../includes/header.php';
$contact = app_setting('SITE_CONTACT_EMAIL') ?: 'contato do responsavel pelo site';
?>
<div class="auth-card legal-card">
    <span class="brand-mark">P</span>
    <h1 class="h4 mt-3">Politica de privacidade</h1>
    <p class="text-muted">Esta pagina resume como o Radar Imoveis trata dados dos usuarios.</p>

    <h2 class="h6 mt-4">Dados usados pelo sistema</h2>
    <p>O Radar Imoveis pode armazenar nome, e-mail, buscas enviados, perfil profissional extraido do busca, buscas configuradas, imoveis encontradas e status de acompanhamento.</p>

    <h2 class="h6 mt-4">Finalidade</h2>
    <p>Esses dados sao usados para autenticar usuarios, organizar buscas, buscar imoveis, calcular compatibilidade e salvar o historico de análises.</p>

    <h2 class="h6 mt-4">Analytics e anuncios</h2>
    <p>O site pode usar ferramentas de medicao de audiencia e exibicao de anuncios, como Google Analytics e Google AdSense, quando configuradas pelo responsavel. Essas ferramentas podem usar cookies ou identificadores para medir visitas, prevenir fraude e personalizar ou limitar publicidade.</p>

    <h2 class="h6 mt-4">Compartilhamento</h2>
    <p>Os dados do usuario nao sao vendidos. Informacoes tecnicas podem ser processadas por provedores necessarios para hospedagem, analytics, seguranca e monetizacao.</p>

    <h2 class="h6 mt-4">Solicitacoes</h2>
    <p>Para pedir acesso, correcao ou exclusao de dados, entre em contato pelo e-mail: <?= e($contact) ?>.</p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
