<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'Use este script via CLI.';
    exit;
}

require_once __DIR__ . '/lib.php';

$opcoes = getopt('', [
    'host::',
    'user::',
    'pass::',
    'saas-db::',
    'tenant-db::',
    'tenant-name::',
    'tenant-slug::',
    'domain::',
    'extra-domains::',
]);

$mysqlHost = isset($opcoes['host']) ? $opcoes['host'] : '127.0.0.1';
$mysqlUser = isset($opcoes['user']) ? $opcoes['user'] : 'barbearia_app';
$mysqlPass = isset($opcoes['pass']) ? $opcoes['pass'] : '@Vito4747';
$saasDb = isset($opcoes['saas-db']) ? $opcoes['saas-db'] : 'barbearia_saas';

$tenantDb = isset($opcoes['tenant-db']) ? $opcoes['tenant-db'] : 'barbearia';
$tenantName = isset($opcoes['tenant-name']) ? $opcoes['tenant-name'] : 'Barbearia Principal';
$tenantSlug = isset($opcoes['tenant-slug']) ? $opcoes['tenant-slug'] : saas_normalizar_slug($tenantName);
$dominioPrincipal = isset($opcoes['domain']) ? $opcoes['domain'] : 'barbearia.superzap.fun';

$dominios = [$dominioPrincipal, 'localhost', '127.0.0.1'];
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
    'nome' => $tenantName,
    'slug' => $tenantSlug,
    'banco' => $tenantDb,
    'db_host' => $mysqlHost,
    'db_usuario' => $mysqlUser,
    'db_senha' => $mysqlPass,
    'ativo' => 'Sim',
], $dominios);

saas_garantir_assinatura_empresa($pdoSaas, $empresaId, 'starter', 'Trial');

echo '[OK] SaaS inicializado com sucesso.' . PHP_EOL;
echo '[OK] Empresa principal ID: ' . $empresaId . PHP_EOL;
echo '[OK] Dominios vinculados: ' . implode(', ', $dominios) . PHP_EOL;
echo '[OK] Assinatura inicial: plano starter (Trial)' . PHP_EOL;
