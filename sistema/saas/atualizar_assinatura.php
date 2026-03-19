<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'Use este script via CLI.';
    exit;
}

require_once __DIR__ . '/lib.php';

$opcoes = getopt('', [
    'empresa-id::',
    'slug::',
    'plano::',
    'status::',
    'trial-dias::',
    'host::',
    'user::',
    'pass::',
    'saas-db::',
]);

if (empty($opcoes['empresa-id']) && empty($opcoes['slug'])) {
    saas_cli_fail('Informe --empresa-id ou --slug para atualizar assinatura.');
}

$mysqlHost = isset($opcoes['host']) ? $opcoes['host'] : '127.0.0.1';
$mysqlUser = isset($opcoes['user']) ? $opcoes['user'] : 'barbearia_app';
$mysqlPass = isset($opcoes['pass']) ? $opcoes['pass'] : '@Vito4747';
$saasDb = isset($opcoes['saas-db']) ? $opcoes['saas-db'] : 'barbearia_saas';

$planoSlug = isset($opcoes['plano']) ? $opcoes['plano'] : 'starter';
$status = isset($opcoes['status']) ? ucfirst(strtolower($opcoes['status'])) : 'Ativa';
$trialDias = isset($opcoes['trial-dias']) ? (int) $opcoes['trial-dias'] : null;

$pdoServer = saas_abrir_servidor($mysqlHost, $mysqlUser, $mysqlPass);
$pdoSaas = saas_garantir_estrutura($pdoServer, $saasDb, $mysqlHost, $mysqlUser, $mysqlPass);

$empresaId = 0;
if (!empty($opcoes['empresa-id'])) {
    $empresaId = (int) $opcoes['empresa-id'];
} else {
    $slug = saas_normalizar_slug($opcoes['slug']);
    $query = $pdoSaas->prepare('SELECT id FROM empresas WHERE slug = :slug LIMIT 1');
    $query->bindValue(':slug', $slug);
    $query->execute();
    $empresa = $query->fetch(PDO::FETCH_ASSOC);
    if (!$empresa) {
        saas_cli_fail('Empresa nao encontrada para o slug informado.');
    }
    $empresaId = (int) $empresa['id'];
}

if ($empresaId <= 0) {
    saas_cli_fail('Empresa invalida.');
}

saas_garantir_assinatura_empresa($pdoSaas, $empresaId, $planoSlug, $status, $trialDias);

echo '[OK] Assinatura atualizada com sucesso.' . PHP_EOL;
echo '[OK] Empresa ID: ' . $empresaId . PHP_EOL;
echo '[OK] Plano: ' . $planoSlug . PHP_EOL;
echo '[OK] Status: ' . $status . PHP_EOL;
