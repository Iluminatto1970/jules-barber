<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'Use este script via CLI.';
    exit;
}

require_once __DIR__ . '/lib.php';

$opcoes = getopt('', [
    'nome:',
    'slug:',
    'dominio:',
    'banco:',
    'host::',
    'user::',
    'pass::',
    'saas-db::',
    'extra-domains::',
    'db-host::',
    'db-user::',
    'db-pass::',
    'ativo::',
    'plano::',
    'status-assinatura::',
    'trial-dias::',
    'service-url::',
    'tunnel-name::',
    'skip-network',
]);

if (empty($opcoes['nome']) || empty($opcoes['slug']) || empty($opcoes['dominio']) || empty($opcoes['banco'])) {
    saas_cli_fail('Uso: php sistema/saas/registrar_empresa.php --nome="Empresa X" --slug="empresa-x" --dominio="empresa-x.seudominio.com" --banco="barbearia_empresa_x"');
}

$mysqlHost = isset($opcoes['host']) ? $opcoes['host'] : '127.0.0.1';
$mysqlUser = isset($opcoes['user']) ? $opcoes['user'] : 'barbearia_app';
$mysqlPass = isset($opcoes['pass']) ? $opcoes['pass'] : '@Vito4747';
$saasDb = isset($opcoes['saas-db']) ? $opcoes['saas-db'] : 'barbearia_saas';

$tenantDbHost = isset($opcoes['db-host']) ? $opcoes['db-host'] : $mysqlHost;
$tenantDbUser = isset($opcoes['db-user']) ? $opcoes['db-user'] : $mysqlUser;
$tenantDbPass = isset($opcoes['db-pass']) ? $opcoes['db-pass'] : $mysqlPass;
$ativo = (isset($opcoes['ativo']) && strtolower($opcoes['ativo']) === 'nao') ? 'Nao' : 'Sim';
$planoSlug = isset($opcoes['plano']) ? $opcoes['plano'] : 'starter';
$statusAssinatura = isset($opcoes['status-assinatura']) ? ucfirst(strtolower($opcoes['status-assinatura'])) : 'Trial';
$trialDias = isset($opcoes['trial-dias']) ? (int) $opcoes['trial-dias'] : null;
$serviceUrl = isset($opcoes['service-url']) ? trim($opcoes['service-url']) : 'http://127.0.0.1:8000';

if ($serviceUrl === '') {
    $serviceUrl = 'http://127.0.0.1:8000';
}

$slug = saas_normalizar_slug($opcoes['slug']);
$tunnelNome = isset($opcoes['tunnel-name']) ? $opcoes['tunnel-name'] : ('tenant-' . $slug);
$tunnelNome = saas_normalizar_tunnel_nome($tunnelNome);
$criarRede = !isset($opcoes['skip-network']);

$dominios = [$opcoes['dominio']];
if (!empty($opcoes['extra-domains'])) {
    $extras = explode(',', $opcoes['extra-domains']);
    foreach ($extras as $extra) {
        $extra = trim($extra);
        if ($extra != '') {
            $dominios[] = $extra;
        }
    }
}

$dominios = array_values(array_unique($dominios));

$pdoServer = saas_abrir_servidor($mysqlHost, $mysqlUser, $mysqlPass);
$pdoSaas = saas_garantir_estrutura($pdoServer, $saasDb, $mysqlHost, $mysqlUser, $mysqlPass);

$empresaId = saas_registrar_empresa($pdoSaas, [
    'nome' => $opcoes['nome'],
    'slug' => $slug,
    'banco' => $opcoes['banco'],
    'db_host' => $tenantDbHost,
    'db_usuario' => $tenantDbUser,
    'db_senha' => $tenantDbPass,
    'ativo' => $ativo,
], $dominios);

saas_garantir_assinatura_empresa($pdoSaas, $empresaId, $planoSlug, $statusAssinatura, $trialDias);

$dominiosNormalizados = [];
foreach ($dominios as $dominio) {
    $dominiosNormalizados[] = saas_normalizar_dominio($dominio);
}

$dominioPrincipal = $dominiosNormalizados[0];

$tunnelId = null;
$configPath = null;
$tunnelExecucao = null;
$dominiosDnsCriados = [];
$dominiosDnsIgnorados = [];
if ($criarRede) {
    if (!saas_cloudflared_disponivel()) {
        saas_cli_fail('cloudflared nao encontrado. Instale e autentique antes de registrar empresa.');
    }

    $tunnel = saas_criar_tunnel($tunnelNome);
    $tunnelId = $tunnel['id'];

    foreach ($dominiosNormalizados as $dominio) {
        if (saas_dominio_publico($dominio)) {
            saas_criar_dns_tunnel($tunnelId, $dominio);
            $dominiosDnsCriados[] = $dominio;
        } else {
            $dominiosDnsIgnorados[] = $dominio;
        }

        saas_registrar_tunnel_empresa($pdoSaas, $empresaId, $tunnelNome, $tunnelId, $dominio, $serviceUrl);
    }

    $configPath = saas_salvar_config_tunnel($slug, $tunnelId, $dominiosNormalizados, $serviceUrl);
    $tunnelExecucao = saas_iniciar_tunnel_background($slug, $tunnelId, $configPath);
}

echo '[OK] Empresa registrada/atualizada com sucesso.' . PHP_EOL;
echo '[OK] Empresa ID: ' . $empresaId . PHP_EOL;
echo '[OK] Banco vinculado: ' . $opcoes['banco'] . PHP_EOL;
echo '[OK] Dominios vinculados: ' . implode(', ', $dominios) . PHP_EOL;
echo '[OK] Assinatura: plano ' . $planoSlug . ' (' . $statusAssinatura . ')' . PHP_EOL;

if ($criarRede) {
    echo '[OK] Tunnel: ' . $tunnelNome . ' (' . $tunnelId . ')' . PHP_EOL;
    if (count($dominiosDnsCriados) > 0) {
        echo '[OK] DNS criado para: ' . implode(', ', $dominiosDnsCriados) . PHP_EOL;
    } else {
        echo '[INFO] Nenhum dominio publico para DNS automatico.' . PHP_EOL;
    }

    if (count($dominiosDnsIgnorados) > 0) {
        echo '[INFO] Dominios sem DNS Cloudflare (locais): ' . implode(', ', $dominiosDnsIgnorados) . PHP_EOL;
    }

    echo '[OK] Config do tunnel: ' . $configPath . PHP_EOL;
    if ($tunnelExecucao) {
        if ($tunnelExecucao['iniciado']) {
            echo '[OK] Tunnel iniciado em background.' . PHP_EOL;
        } else {
            echo '[INFO] Tunnel ja estava em execucao.' . PHP_EOL;
        }
        echo '[OK] Log do tunnel: ' . $tunnelExecucao['log'] . PHP_EOL;
    }
    echo '[INFO] Para iniciar tunnel: cloudflared --config ' . escapeshellarg($configPath) . ' tunnel run ' . escapeshellarg($tunnelId) . PHP_EOL;
} else {
    echo '[INFO] Rede ignorada (--skip-network). Nenhum tunnel/dns foi criado.' . PHP_EOL;
}
