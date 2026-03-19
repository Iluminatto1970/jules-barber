<?php

date_default_timezone_set('America/Sao_Paulo');

// Credenciais do banco dos tenants (fallback single-tenant)
$servidor = '127.0.0.1';
$banco = 'barbearia';
$usuario = 'barbearia_app';
$senha = '@Vito4747';

// Credenciais do banco de controle SaaS
$saas_servidor = '127.0.0.1';
$saas_banco = 'barbearia_saas';
$saas_usuario = 'barbearia_app';
$saas_senha = '@Vito4747';

require_once(__DIR__ . '/saas/lib.php');
require_once(__DIR__ . '/saas/planos_guard.php');

function saas_normalizar_host($host)
{
	$host = strtolower(trim((string) $host));
	$host = preg_replace('/:\\d+$/', '', $host);

	if (strpos($host, 'www.') === 0) {
		$host = substr($host, 4);
	}

	if ($host == '') {
		$host = 'localhost';
	}

	return $host;
}

function saas_base_path()
{
	$script_name = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';

	if ($script_name == '') {
		return '';
	}

	if (strpos($script_name, '/sistema/') !== false) {
		return substr($script_name, 0, strpos($script_name, '/sistema/'));
	}

	if (strpos($script_name, '/ajax/') !== false) {
		return substr($script_name, 0, strpos($script_name, '/ajax/'));
	}

	$dir = dirname($script_name);

	if ($dir == '/' || $dir == '\\' || $dir == '.') {
		return '';
	}

	return rtrim($dir, '/');
}

function saas_mysql_conectar($banco, $host, $usuario, $senha, &$detalhesErro = '')
{
	$detalhesErro = '';
	$banco = trim((string) $banco);
	$host = trim((string) $host);

	if ($banco == '') {
		$detalhesErro = 'Banco de dados nao informado.';
		return null;
	}

	if ($host == '') {
		$host = '127.0.0.1';
	}

	$hosts = [$host];
	$host_sem_porta = preg_replace('/:\d+$/', '', $host);
	if ($host_sem_porta == '127.0.0.1') {
		$hosts[] = 'localhost';
	} elseif ($host_sem_porta == 'localhost') {
		$hosts[] = '127.0.0.1';
	}

	$hosts = array_values(array_unique(array_filter($hosts)));
	$erros = [];

	foreach ($hosts as $host_teste) {
		$host_mysql = $host_teste;
		$porta_mysql = '';

		if (preg_match('/^([^:]+):(\d+)$/', $host_teste, $matches)) {
			$host_mysql = $matches[1];
			$porta_mysql = $matches[2];
		}

		$dsn = "mysql:dbname={$banco};host={$host_mysql};charset=utf8mb4";
		if ($porta_mysql != '') {
			$dsn .= ";port={$porta_mysql}";
		}

		try {
			$pdo = new PDO($dsn, "$usuario", "$senha");
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $pdo;
		} catch (Exception $e) {
			$erros[] = '[' . $host_teste . '] ' . $e->getMessage();
		}
	}

	if (in_array('127.0.0.1', $hosts) || in_array('localhost', $hosts)) {
		$socket_path = '/run/mysqld/mysqld.sock';
		if (file_exists($socket_path)) {
			try {
				$pdo = new PDO("mysql:dbname={$banco};unix_socket={$socket_path};charset=utf8mb4", "$usuario", "$senha");
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				return $pdo;
			} catch (Exception $e) {
				$erros[] = '[unix_socket] ' . $e->getMessage();
			}
		}
	}

	$detalhesErro = count($erros) > 0 ? implode(' | ', $erros) : 'Falha ao conectar no banco.';
	return null;
}

$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$host_lookup = saas_normalizar_host($http_host);

$is_super_admin_host = strpos($host_lookup, 'superadm') !== false || strpos($host_lookup, 'superadmin') !== false;
$is_saas_admin_path = strpos($request_uri, '/saas/admin') !== false;

if ($is_super_admin_host && !$is_saas_admin_path) {
    header('Location: /sistema/saas/admin/');
    exit;
}

if ($is_saas_admin_path) {
    define('SAAS_ADMIN_APP', true);
    $saas_servidor = '127.0.0.1';
    $saas_banco = 'barbearia_saas';
    $saas_usuario = 'barbearia_app';
    $saas_senha = '@Vito4747';
    
    $pdo_saas = saas_mysql_conectar($saas_banco, $saas_servidor, $saas_usuario, $saas_senha, $erro);
    if (!$pdo_saas) {
        $pdo_saas = null;
    }
    $pdo = null;
} else {
    $protocolo = 'http';
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')) {
    	$protocolo = 'https';
    }

    $base_path = saas_base_path();
    $url_sistema = $protocolo . '://' . $http_host;
    if ($base_path != '') {
    	$url_sistema .= $base_path;
    }
    $url_sistema = rtrim($url_sistema, '/') . '/';

    $empresa_id = 0;
    $empresa_nome = 'Tenant Padrao';
    $empresa_slug = 'padrao';
    $empresa_dominio = $host_lookup;

    $saas_plano_ctx = saas_plano_contexto_padrao();
    $empresa_plano_id = 0;
    $empresa_plano_nome = $saas_plano_ctx['plano_nome'];
    $empresa_plano_slug = $saas_plano_ctx['plano_slug'];
    $empresa_assinatura_status = $saas_plano_ctx['assinatura_status'];
    $empresa_assinatura_trial_ate = null;
    $empresa_assinatura_ciclo_ate = null;
    $empresa_assinatura_bloqueada = false;
    $empresa_assinatura_motivo = '';
    $saas_plano_recursos = $saas_plano_ctx['recursos'];

    try {
    	$erro_conexao_saas = '';
    	$pdo_saas = saas_mysql_conectar($saas_banco, $saas_servidor, $saas_usuario, $saas_senha, $erro_conexao_saas);
    	if (!$pdo_saas) {
    		throw new Exception($erro_conexao_saas);
    	}

    	$query_tenant = $pdo_saas->prepare("SELECT e.id, e.nome, e.slug, e.banco, e.db_host, e.db_usuario, e.db_senha, d.dominio
    		FROM empresas e
    		INNER JOIN empresas_dominios d ON d.empresa_id = e.id
    		WHERE e.ativo = 'Sim' AND d.dominio = :dominio
    		ORDER BY d.principal DESC, d.id ASC
    		LIMIT 1");
    	$query_tenant->bindValue(':dominio', $host_lookup);
    	$query_tenant->execute();
    	$empresa = $query_tenant->fetch(PDO::FETCH_ASSOC);

    	if (!$empresa) {
    		$query_tenant = $pdo_saas->query("SELECT e.id, e.nome, e.slug, e.banco, e.db_host, e.db_usuario, e.db_senha,
    			(SELECT dominio FROM empresas_dominios WHERE empresa_id = e.id ORDER BY principal DESC, id ASC LIMIT 1) AS dominio
    			FROM empresas e
    			WHERE e.ativo = 'Sim'
    			ORDER BY e.id ASC
    			LIMIT 1");
    		$empresa = $query_tenant->fetch(PDO::FETCH_ASSOC);
    	}

    	if ($empresa) {
    		$empresa_id = (int) $empresa['id'];
    		$empresa_nome = $empresa['nome'];
    		$empresa_slug = $empresa['slug'];
    		$empresa_dominio = $empresa['dominio'];

    		$banco = $empresa['banco'];
    		$servidor = $empresa['db_host'];
    		$usuario = $empresa['db_usuario'];
    		$senha = $empresa['db_senha'];
    	}
    } catch (Exception $e) {
    	$pdo_saas = null;
    }

    try {
    	$erro_conexao_tenant = '';
    	$pdo = saas_mysql_conectar($banco, $servidor, $usuario, $senha, $erro_conexao_tenant);
    	if (!$pdo) {
    		throw new Exception($erro_conexao_tenant);
    	}
    } catch (Exception $e) {
    	echo 'Nao conectado ao Banco de Dados! <br><br>' . $e;
    	exit();
    }
}

if (session_status() == PHP_SESSION_NONE) {
    @session_start();
}

if ($pdo_saas && $empresa_id > 0) {
	$saas_plano_ctx = saas_plano_carregar_contexto($pdo_saas, $empresa_id);
	$empresa_plano_id = $saas_plano_ctx['plano_id'];
	$empresa_plano_nome = $saas_plano_ctx['plano_nome'];
	$empresa_plano_slug = $saas_plano_ctx['plano_slug'];
	$empresa_assinatura_status = $saas_plano_ctx['assinatura_status'];
	$empresa_assinatura_trial_ate = $saas_plano_ctx['trial_ate'];
	$empresa_assinatura_ciclo_ate = $saas_plano_ctx['ciclo_ate'];
	$empresa_assinatura_bloqueada = (bool) $saas_plano_ctx['bloqueada'];
	$empresa_assinatura_motivo = $saas_plano_ctx['motivo_bloqueio'];
	$saas_plano_recursos = $saas_plano_ctx['recursos'];
}

if (session_status() == PHP_SESSION_ACTIVE) {
	$_SESSION['empresa_id'] = $empresa_id;
	$_SESSION['empresa_nome'] = $empresa_nome;
	$_SESSION['empresa_slug'] = $empresa_slug;
	$_SESSION['empresa_dominio'] = $empresa_dominio;
	$_SESSION['empresa_plano_id'] = $empresa_plano_id;
	$_SESSION['empresa_plano_nome'] = $empresa_plano_nome;
	$_SESSION['empresa_plano_slug'] = $empresa_plano_slug;
	$_SESSION['empresa_assinatura_status'] = $empresa_assinatura_status;
	$_SESSION['empresa_assinatura_trial_ate'] = $empresa_assinatura_trial_ate;
	$_SESSION['empresa_assinatura_ciclo_ate'] = $empresa_assinatura_ciclo_ate;
	$_SESSION['empresa_assinatura_bloqueada'] = $empresa_assinatura_bloqueada ? 'Sim' : 'Nao';
}

// VARIAVEIS DO SISTEMA
$nome_sistema = 'Barbearia Teste';
$email_sistema = 'teste@teste.com';
$whatsapp_sistema = '(11) 9999-9999';
$tipo_rel = 'pdf';
$telefone_fixo_sistema = '';
$endereco_sistema = '';
$logo_rel = 'logo_rel.jpg';
$logo_sistema = 'logo.png';
$icone_sistema = 'favicon.ico';
$instagram_sistema = '#';
$tipo_comissao = 'Porcentagem';
$texto_rodape = '';
$img_banner_index = 'hero-bg.jpg';
$icone_site = 'favicon.ico';
$texto_sobre = '';
$imagem_sobre = '';
$mapa = '';
$quantidade_cartoes = 10;
$texto_fidelidade = '';
$texto_agendamento = 'Selecionar Prestador de Servico';
$msg_agendamento = 'Sim';

if (!defined('SAAS_ADMIN_APP') && $pdo) {
    $query = $pdo->query("SELECT * from config");
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total_reg = @count($res);
    if ($total_reg == 0) {
        $pdo->query("INSERT INTO config SET nome = '$nome_sistema', email = '$email_sistema', telefone_whatsapp = '$whatsapp_sistema', logo = 'logo.png', icone = 'favicon.ico', logo_rel = 'logo_rel.jpg', tipo_rel = 'pdf', tipo_comissao = 'Porcentagem', texto_rodape = 'Edite este texto nas configuracoes do painel administrador', img_banner_index = 'hero-bg.jpg', quantidade_cartoes = 10, texto_agendamento = 'Selecionar Prestador de Servico', msg_agendamento = 'Sim'");
    } else {
        $nome_sistema = $res[0]['nome'];
        $email_sistema = $res[0]['email'];
        $whatsapp_sistema = $res[0]['telefone_whatsapp'];
        $tipo_rel = $res[0]['tipo_rel'];
        $telefone_fixo_sistema = $res[0]['telefone_fixo'];
        $endereco_sistema = $res[0]['endereco'];
        $logo_rel = $res[0]['logo_rel'];
        $logo_sistema = $res[0]['logo'];
        $icone_sistema = $res[0]['icone'];
        $instagram_sistema = $res[0]['instagram'];
        $tipo_comissao = $res[0]['tipo_comissao'];
        $texto_rodape = $res[0]['texto_rodape'];
        $img_banner_index = $res[0]['img_banner_index'];
        $icone_site = $res[0]['icone_site'];
        $texto_sobre = $res[0]['texto_sobre'];
        $imagem_sobre = $res[0]['imagem_sobre'];
        $mapa = $res[0]['mapa'];
        $quantidade_cartoes = $res[0]['quantidade_cartoes'];
        $texto_fidelidade = $res[0]['texto_fidelidade'];
        $texto_agendamento = $res[0]['texto_agendamento'];
        $msg_agendamento = $res[0]['msg_agendamento'];
    }
}

$tel_whatsapp = '55' . preg_replace('/[ ()-]+/', '', $whatsapp_sistema);

?>
