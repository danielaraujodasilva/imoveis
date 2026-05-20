# Radar Imoveis

Sistema web em PHP + MySQL + Node para rastrear oportunidades imobiliarias em varias fontes, salvar os anuncios por usuario e permitir filtros por cidade, bairro, preco, quartos, area, fonte e status.

## Reaproveitamento do Paula

O projeto usa o mesmo banco `agente_vagas` e a mesma tabela `usuarios` do `danielaraujodasilva/paula`. Isso permite entrar com os mesmos logins e senhas sem duplicar cadastro. Os dados imobiliarios ficam separados em tabelas proprias:

- `buscas_imoveis`
- `imoveis`
- `logs_execucao_imoveis`

## Instalação

1. Copie o projeto para a pasta web, por exemplo `C:\xampp\htdocs\site\imoveis`.
2. Importe `database/schema.sql` no MySQL usado pelo Paula.
3. Ajuste `config/local.php` se o servidor usar senha de banco, base URL ou webhook customizado.
4. Instale as dependencias Node:

```bash
cd node
npm install
```

## Webhook de deploy

O endpoint e o mesmo modelo usado no Paula:

```text
https://SEU_DOMINIO.com/imoveis/api/github_webhook.php
```

No servidor, configure em `config/local.php`:

```php
<?php
defined('BASE_URL') || define('BASE_URL', '/imoveis');
defined('DEPLOY_WEBHOOK_SECRET') || define('DEPLOY_WEBHOOK_SECRET', 'troque-por-um-segredo-grande');
defined('DEPLOY_BRANCH') || define('DEPLOY_BRANCH', 'main');
defined('DEPLOY_REPO_PATH') || define('DEPLOY_REPO_PATH', __DIR__ . '/..');
```

No GitHub, crie um webhook para evento `push`, content type `application/json`, usando o mesmo segredo.

## Uso

1. Entre com o mesmo login do Paula.
2. Acesse `Buscas` e cadastre cidade, bairro, tipo, preco e fontes.
3. Clique em `Rastrear agora`.
4. Abra `Imoveis` para filtrar, ordenar e marcar oportunidades como interessante, contatado, visita, descartado ou arquivado.

## Fontes iniciais

- Mercado Livre
- OLX
- Zap Imoveis
- Viva Real

Essas fontes sao rastreadas por paginas publicas e podem mudar estrutura. Quando uma fonte bloquear ou alterar HTML, o script segue com as outras fontes e registra alerta nos logs.
