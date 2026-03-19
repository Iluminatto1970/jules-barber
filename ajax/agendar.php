<?php
require_once("../sistema/conexao.php");
header('Content-Type: text/plain; charset=utf-8');

function agendar_fail($mensagem)
{
	echo $mensagem;
	exit();
}

try {
	saas_plano_exigir_ativo($saas_plano_ctx, $pdo_saas, $empresa_id, 'site/agendar');
	saas_plano_exigir_recurso($saas_plano_ctx, 'menu_agendamentos', 'Agendamentos indisponiveis para o plano atual.', $pdo_saas, $empresa_id);

	$telefone = isset($_POST['telefone']) ? trim((string) $_POST['telefone']) : '';
	$telefone_limpo = preg_replace('/\D+/', '', $telefone);
	$nome = isset($_POST['nome']) ? trim((string) $_POST['nome']) : '';
	$funcionario = isset($_POST['funcionario']) ? (int) $_POST['funcionario'] : 0;
	$hora = isset($_POST['hora']) ? trim((string) $_POST['hora']) : '';
	$servico = isset($_POST['servico']) ? (int) $_POST['servico'] : 0;
	$obs = isset($_POST['obs']) ? trim((string) $_POST['obs']) : '';
	$data = isset($_POST['data']) ? trim((string) $_POST['data']) : '';
	$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

	if ($hora === '') {
		agendar_fail('Escolha um horario para agendar!');
	}

	if ($nome === '' || $telefone_limpo === '') {
		agendar_fail('Informe nome e telefone para continuar!');
	}

	if ($funcionario <= 0 || $servico <= 0 || $data === '') {
		agendar_fail('Preencha os dados obrigatorios do agendamento!');
	}

	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
		agendar_fail('Data invalida.');
	}

	if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
		agendar_fail('Horario invalido.');
	}

	if (strlen($obs) > 255) {
		if (function_exists('mb_substr')) {
			$obs = mb_substr($obs, 0, 255, 'UTF-8');
		} else {
			$obs = substr($obs, 0, 255);
		}
	}

	$query = $pdo->prepare("SELECT id FROM agendamentos WHERE data = :data AND hora = :hora AND funcionario = :funcionario LIMIT 1");
	$query->bindValue(':data', $data);
	$query->bindValue(':hora', $hora);
	$query->bindValue(':funcionario', $funcionario, PDO::PARAM_INT);
	$query->execute();
	$agendamento_existente = $query->fetch(PDO::FETCH_ASSOC);

	if ($agendamento_existente && (int) $agendamento_existente['id'] !== $id) {
		agendar_fail('Este horario nao esta disponivel!');
	}

	$query = $pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE telefone = :telefone OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), ' ', ''), '-', ''), '+', '') = :telefone_limpo LIMIT 1");
	$query->bindValue(':telefone', $telefone);
	$query->bindValue(':telefone_limpo', $telefone_limpo);
	$query->execute();
	$cliente = $query->fetch(PDO::FETCH_ASSOC);

	if (!$cliente) {
		$query = $pdo->prepare("INSERT INTO clientes (nome, telefone, data_cad, cartoes, alertado, data_nasc, ultimo_servico) VALUES (:nome, :telefone, CURDATE(), 0, 'Não', '2000-01-01', 0)");
		$query->bindValue(':nome', $nome);
		$query->bindValue(':telefone', $telefone);
		$query->execute();
		$id_cliente = (int) $pdo->lastInsertId();
	} else {
		$id_cliente = (int) $cliente['id'];
		$query = $pdo->prepare("UPDATE clientes SET nome = :nome, telefone = :telefone WHERE id = :id");
		$query->bindValue(':nome', $nome);
		$query->bindValue(':telefone', $telefone);
		$query->bindValue(':id', $id_cliente, PDO::PARAM_INT);
		$query->execute();
	}

	if ($id <= 0) {
		saas_plano_exigir_limite_mensal($pdo_saas, $saas_plano_ctx, $empresa_id, 'limite_agendamentos_mes', 'Limite mensal de agendamentos do plano atingido.', 1);

		$query = $pdo->prepare("INSERT INTO agendamentos (funcionario, cliente, hora, data, usuario, status, obs, data_lanc, servico) VALUES (:funcionario, :cliente, :hora, :data, 0, 'Agendado', :obs, CURDATE(), :servico)");
		$query->bindValue(':funcionario', $funcionario, PDO::PARAM_INT);
		$query->bindValue(':cliente', $id_cliente, PDO::PARAM_INT);
		$query->bindValue(':hora', $hora);
		$query->bindValue(':data', $data);
		$query->bindValue(':servico', $servico, PDO::PARAM_INT);
		$query->bindValue(':obs', $obs);
		$query->execute();

		saas_plano_incrementar_uso_mensal($pdo_saas, $empresa_id, 'limite_agendamentos_mes', 1);
		echo 'Agendado com Sucesso';
		exit();
	}

	$query = $pdo->prepare("UPDATE agendamentos SET funcionario = :funcionario, cliente = :cliente, hora = :hora, data = :data, usuario = 0, status = 'Agendado', obs = :obs, data_lanc = CURDATE(), servico = :servico WHERE id = :id");
	$query->bindValue(':funcionario', $funcionario, PDO::PARAM_INT);
	$query->bindValue(':cliente', $id_cliente, PDO::PARAM_INT);
	$query->bindValue(':hora', $hora);
	$query->bindValue(':data', $data);
	$query->bindValue(':servico', $servico, PDO::PARAM_INT);
	$query->bindValue(':obs', $obs);
	$query->bindValue(':id', $id, PDO::PARAM_INT);
	$query->execute();

	echo 'Editado com Sucesso';
} catch (Throwable $e) {
	error_log('[agendar.php] ' . $e->getMessage());
	agendar_fail('Nao foi possivel concluir o agendamento agora. Tente novamente.');
}

?>
