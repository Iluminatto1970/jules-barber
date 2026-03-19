<?php

header('Content-Type: application/json; charset=utf-8');

require_once('../conexao.php');

function saas_json_fail($mensagem)
{
    echo json_encode([
        'ok' => false,
        'mensagem' => $mensagem,
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function saas_json_ok($dados)
{
    echo json_encode(array_merge(['ok' => true], $dados), JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    saas_json_fail('Metodo invalido.');
}

if (!$pdo_saas) {
    saas_json_fail('Servico SaaS indisponivel no momento.');
}

$nomeEmpresa = trim((string) @$_POST['empresa']);
$nomeResponsavel = trim((string) @$_POST['responsavel']);
$telefone = preg_replace('/[^0-9]/', '', (string) @$_POST['telefone']);
$email = saas_normalizar_email(@$_POST['email']);
$senha = (string) @$_POST['senha'];
$confirmarSenha = (string) @$_POST['confirmar_senha'];
$subdominio = trim((string) @$_POST['subdominio']);

if ($nomeEmpresa == '' || $nomeResponsavel == '' || $telefone == '' || $email == '' || $senha == '') {
    saas_json_fail('Preencha todos os campos obrigatorios.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    saas_json_fail('Informe um email valido.');
}

if (strlen($senha) < 6) {
    saas_json_fail('A senha deve ter pelo menos 6 caracteres.');
}

if ($senha !== $confirmarSenha) {
    saas_json_fail('A confirmacao da senha nao confere.');
}

if (saas_email_ja_cadastrado($pdo_saas, $email)) {
    saas_json_fail('Este email ja possui uma conta SaaS cadastrada.');
}

$baseSlug = $subdominio != '' ? $subdominio : $nomeEmpresa;
$slug = saas_slug_disponivel($pdo_saas, $baseSlug);
$bancoTenant = saas_nome_banco_disponivel($pdo_saas, $slug);

$hostAtual = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'barbearia.superzap.fun';
$dominioBase = saas_dominio_base_por_host($hostAtual);
$dominioPrincipal = $slug . '.' . $dominioBase;

if (!saas_cloudflared_disponivel()) {
    saas_json_fail('Cloudflared nao encontrado no servidor.');
}

try {
    $pdoServer = saas_abrir_servidor($saas_servidor, $saas_usuario, $saas_senha);
    $pdoSaasProvision = saas_garantir_estrutura($pdoServer, $saas_banco, $saas_servidor, $saas_usuario, $saas_senha);

    saas_criar_banco_tenant_modelo($pdoServer, $saas_servidor, $saas_usuario, $saas_senha, $bancoTenant);

    $empresaId = saas_registrar_empresa($pdoSaasProvision, [
        'nome' => $nomeEmpresa,
        'slug' => $slug,
        'banco' => $bancoTenant,
        'db_host' => $saas_servidor,
        'db_usuario' => $saas_usuario,
        'db_senha' => $saas_senha,
        'ativo' => 'Sim',
    ], [$dominioPrincipal]);

    saas_garantir_assinatura_empresa($pdoSaasProvision, $empresaId, 'starter', 'Trial', 14);
    saas_registrar_usuario_empresa($pdoSaasProvision, $empresaId, $nomeResponsavel, $email, $senha);

    saas_configurar_tenant_admin($saas_servidor, $bancoTenant, $saas_usuario, $saas_senha, $nomeEmpresa, $nomeResponsavel, $email, $senha);

    $tunnelNome = saas_normalizar_tunnel_nome('tenant-' . $slug);
    $tunnel = saas_criar_tunnel($tunnelNome);
    $tunnelId = $tunnel['id'];

    saas_criar_dns_tunnel($tunnelId, $dominioPrincipal);
    saas_registrar_tunnel_empresa($pdoSaasProvision, $empresaId, $tunnelNome, $tunnelId, $dominioPrincipal, 'http://127.0.0.1:8000');

    $configPath = saas_salvar_config_tunnel($slug, $tunnelId, [$dominioPrincipal], 'http://127.0.0.1:8000');
    $tunnelExecucao = saas_iniciar_tunnel_background($slug, $tunnelId, $configPath);

    $dominioUrl = 'https://' . $dominioPrincipal . '/sistema/';
    
    $mensagemWhatsApp = "Olá! Bem-vindo ao seu sistema de barbearia!%0A%0A";
    $mensagemWhatsApp .= "🔗 URL de acesso: " . $dominioUrl . "%0A";
    $mensagemWhatsApp .= "📧 Email: " . $email . "%0A";
    $mensagemWhatsApp .= "🔑 Senha: " . $senha . "%0A%0A";
    $mensagemWhatsApp .= "Atenciosamente,%0AEquipe Barbearia SaaS";

    $whatsappLink = "https://wa.me/55" . preg_replace('/[^0-9]/', '', $telefone) . "?text=" . $mensagemWhatsApp;

    saas_json_ok([
        'mensagem' => 'Conta criada com sucesso! Seu ambiente ja esta pronto.',
        'empresa_id' => $empresaId,
        'dominio' => $dominioUrl,
        'email' => $email,
        'senha' => $senha,
        'plano' => 'starter',
        'status' => 'Trial',
        'tunnel' => $tunnelNome,
        'tunnel_id' => $tunnelId,
        'tunnel_log' => $tunnelExecucao['log'],
        'whatsapp_link' => $whatsappLink,
    ]);
} catch (Exception $e) {
    saas_json_fail('Falha ao criar conta SaaS: ' . $e->getMessage());
}
