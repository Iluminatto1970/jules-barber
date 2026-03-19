<?php

function saas_plano_recursos_padrao()
{
    return [
        'acesso_painel' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'menu_home' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'menu_configuracoes' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'menu_pessoas' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'menu_cadastros' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'menu_produtos' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'menu_financeiro' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'menu_agendamentos' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'menu_relatorios' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'menu_site' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'limite_usuarios' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'limite_produtos' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'limite_servicos' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        'limite_agendamentos_mes' => ['permitido' => 'Sim', 'limite' => null, 'periodo' => 'mensal'],
    ];
}

function saas_plano_contexto_padrao()
{
    return [
        'plano_id' => 0,
        'plano_nome' => 'Starter',
        'plano_slug' => 'starter',
        'assinatura_status' => 'Ativa',
        'trial_ate' => null,
        'ciclo_ate' => null,
        'bloqueada' => false,
        'motivo_bloqueio' => '',
        'recursos' => saas_plano_recursos_padrao(),
    ];
}

function saas_plano_registrar_evento($pdoSaas, $empresaId, $tipo, $recurso = '', $detalhe = '')
{
    if (!$pdoSaas || (int) $empresaId <= 0) {
        return;
    }

    try {
        $query = $pdoSaas->prepare("INSERT INTO empresas_eventos_billing (empresa_id, tipo, recurso, detalhe)
            VALUES (:empresa_id, :tipo, :recurso, :detalhe)");
        $query->bindValue(':empresa_id', (int) $empresaId, PDO::PARAM_INT);
        $query->bindValue(':tipo', (string) $tipo);
        $query->bindValue(':recurso', (string) $recurso);
        $query->bindValue(':detalhe', (string) $detalhe);
        $query->execute();
    } catch (Exception $e) {
        // Falha de log nao deve quebrar o fluxo.
    }
}

function saas_plano_carregar_contexto($pdoSaas, $empresaId)
{
    $contexto = saas_plano_contexto_padrao();

    if (!$pdoSaas || (int) $empresaId <= 0) {
        return $contexto;
    }

    $empresaId = (int) $empresaId;

    try {
        $query = $pdoSaas->prepare("SELECT ea.plano_id, ea.status, ea.trial_ate, ea.ciclo_ate, p.nome AS plano_nome, p.slug AS plano_slug
            FROM empresas_assinaturas ea
            INNER JOIN planos p ON p.id = ea.plano_id
            WHERE ea.empresa_id = :empresa_id
            LIMIT 1");
        $query->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
        $query->execute();
        $assinatura = $query->fetch(PDO::FETCH_ASSOC);

        if (!$assinatura && function_exists('saas_garantir_assinatura_empresa')) {
            saas_garantir_assinatura_empresa($pdoSaas, $empresaId, 'starter', 'Trial');
            $query->execute();
            $assinatura = $query->fetch(PDO::FETCH_ASSOC);
        }

        if (!$assinatura) {
            return $contexto;
        }

        $contexto['plano_id'] = (int) $assinatura['plano_id'];
        $contexto['plano_nome'] = $assinatura['plano_nome'];
        $contexto['plano_slug'] = $assinatura['plano_slug'];
        $contexto['assinatura_status'] = $assinatura['status'];
        $contexto['trial_ate'] = $assinatura['trial_ate'];
        $contexto['ciclo_ate'] = $assinatura['ciclo_ate'];

        $queryRec = $pdoSaas->prepare("SELECT recurso, permitido, limite, periodo
            FROM planos_recursos
            WHERE plano_id = :plano_id");
        $queryRec->bindValue(':plano_id', $contexto['plano_id'], PDO::PARAM_INT);
        $queryRec->execute();
        $recursos = $queryRec->fetchAll(PDO::FETCH_ASSOC);

        $contexto['recursos'] = saas_plano_recursos_padrao();
        foreach ($recursos as $recurso) {
            $nome = $recurso['recurso'];
            $contexto['recursos'][$nome] = [
                'permitido' => $recurso['permitido'],
                'limite' => $recurso['limite'] === null ? null : (int) $recurso['limite'],
                'periodo' => $recurso['periodo'],
            ];
        }

        $hoje = date('Y-m-d');
        if ($contexto['assinatura_status'] === 'Suspensa' || $contexto['assinatura_status'] === 'Cancelada') {
            $contexto['bloqueada'] = true;
            $contexto['motivo_bloqueio'] = 'Sua assinatura esta suspensa. Entre em contato com o suporte.';
        } elseif ($contexto['assinatura_status'] === 'Trial' && !empty($contexto['trial_ate']) && $hoje > $contexto['trial_ate']) {
            $contexto['bloqueada'] = true;
            $contexto['motivo_bloqueio'] = 'Seu periodo de teste expirou. Regularize o plano para continuar.';
        } elseif ($contexto['assinatura_status'] === 'Ativa' && !empty($contexto['ciclo_ate']) && $hoje > $contexto['ciclo_ate']) {
            $contexto['bloqueada'] = true;
            $contexto['motivo_bloqueio'] = 'Seu ciclo esta vencido. Regularize o plano para continuar.';
        }

        if (!saas_plano_recurso_permitido($contexto, 'acesso_painel', true)) {
            $contexto['bloqueada'] = true;
            $contexto['motivo_bloqueio'] = 'Seu plano atual nao permite acesso ao painel.';
        }
    } catch (Exception $e) {
        return saas_plano_contexto_padrao();
    }

    return $contexto;
}

function saas_plano_recurso_permitido($contexto, $recurso, $padrao = true)
{
    if (!isset($contexto['recursos']) || !is_array($contexto['recursos'])) {
        return $padrao;
    }

    if (!isset($contexto['recursos'][$recurso])) {
        return $padrao;
    }

    return $contexto['recursos'][$recurso]['permitido'] === 'Sim';
}

function saas_plano_limite_recurso($contexto, $recurso, $padrao = null)
{
    if (!isset($contexto['recursos']) || !is_array($contexto['recursos'])) {
        return $padrao;
    }

    if (!isset($contexto['recursos'][$recurso])) {
        return $padrao;
    }

    $limite = $contexto['recursos'][$recurso]['limite'];
    if ($limite === null) {
        return null;
    }

    return (int) $limite;
}

function saas_plano_bloquear_saida($mensagem, $redirect = '')
{
    $mensagem = trim((string) $mensagem);
    if ($mensagem === '') {
        $mensagem = 'Acesso bloqueado pelo plano.';
    }

    $mensagemJs = addslashes($mensagem);

    $isAjax = false;
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        $isAjax = true;
    }

    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    if (strpos($script, '/ajax/') !== false || strpos($script, '/salvar.php') !== false || strpos($script, '/inserir') !== false) {
        $isAjax = true;
    }

    if ($isAjax) {
        echo $mensagem;
        exit();
    }

    if ($redirect !== '') {
        $redirectJs = addslashes($redirect);
        echo "<script>window.alert('{$mensagemJs}');window.location='{$redirectJs}'</script>";
        exit();
    }

    echo "<script>window.alert('{$mensagemJs}');</script>";
    exit();
}

function saas_plano_exigir_ativo($contexto, $pdoSaas = null, $empresaId = 0, $origem = '')
{
    if (!empty($contexto['bloqueada'])) {
        $mensagem = !empty($contexto['motivo_bloqueio']) ? $contexto['motivo_bloqueio'] : 'Acesso bloqueado pelo plano.';
        saas_plano_registrar_evento($pdoSaas, $empresaId, 'assinatura_bloqueada', (string) $origem, $mensagem);
        saas_plano_bloquear_saida($mensagem, '../index.php');
    }
}

function saas_plano_exigir_recurso($contexto, $recurso, $mensagem, $pdoSaas = null, $empresaId = 0)
{
    if (!saas_plano_recurso_permitido($contexto, $recurso, true)) {
        saas_plano_registrar_evento($pdoSaas, $empresaId, 'recurso_bloqueado', $recurso, $mensagem);
        saas_plano_bloquear_saida($mensagem, '../index.php');
    }
}

function saas_plano_contar_registros($pdo, $tabela, $where = '1=1')
{
    $query = $pdo->query("SELECT COUNT(*) AS total FROM {$tabela} WHERE {$where}");
    $res = $query->fetch(PDO::FETCH_ASSOC);

    return isset($res['total']) ? (int) $res['total'] : 0;
}

function saas_plano_exigir_limite_total_tabela($pdo, $contexto, $recurso, $tabela, $mensagem, $where = '1=1', $pdoSaas = null, $empresaId = 0)
{
    $limite = saas_plano_limite_recurso($contexto, $recurso, null);
    if ($limite === null || $limite <= 0) {
        return;
    }

    $total = saas_plano_contar_registros($pdo, $tabela, $where);
    if ($total >= $limite) {
        $detalhe = $mensagem . ' Limite: ' . $limite . '. Atual: ' . $total . '.';
        saas_plano_registrar_evento($pdoSaas, $empresaId, 'limite_total_excedido', $recurso, $detalhe);
        saas_plano_bloquear_saida($mensagem);
    }
}

function saas_plano_uso_mensal_atual($pdoSaas, $empresaId, $recurso, $referencia = null)
{
    if (!$pdoSaas || (int) $empresaId <= 0) {
        return 0;
    }

    if ($referencia === null) {
        $referencia = date('Y-m');
    }

    $query = $pdoSaas->prepare("SELECT quantidade FROM empresas_uso_mensal
        WHERE empresa_id = :empresa_id AND recurso = :recurso AND referencia = :referencia
        LIMIT 1");
    $query->bindValue(':empresa_id', (int) $empresaId, PDO::PARAM_INT);
    $query->bindValue(':recurso', (string) $recurso);
    $query->bindValue(':referencia', (string) $referencia);
    $query->execute();
    $res = $query->fetch(PDO::FETCH_ASSOC);

    if (!$res) {
        return 0;
    }

    return (int) $res['quantidade'];
}

function saas_plano_exigir_limite_mensal($pdoSaas, $contexto, $empresaId, $recurso, $mensagem, $incremento = 1)
{
    if (!$pdoSaas || (int) $empresaId <= 0) {
        return;
    }

    $limite = saas_plano_limite_recurso($contexto, $recurso, null);
    if ($limite === null || $limite <= 0) {
        return;
    }

    $uso = saas_plano_uso_mensal_atual($pdoSaas, $empresaId, $recurso);
    if (($uso + (int) $incremento) > $limite) {
        $detalhe = $mensagem . ' Limite mensal: ' . $limite . '. Uso atual: ' . $uso . '.';
        saas_plano_registrar_evento($pdoSaas, $empresaId, 'limite_mensal_excedido', $recurso, $detalhe);
        saas_plano_bloquear_saida($mensagem);
    }
}

function saas_plano_incrementar_uso_mensal($pdoSaas, $empresaId, $recurso, $incremento = 1)
{
    if (!$pdoSaas || (int) $empresaId <= 0) {
        return;
    }

    $referencia = date('Y-m');
    $query = $pdoSaas->prepare("INSERT INTO empresas_uso_mensal (empresa_id, recurso, referencia, quantidade)
        VALUES (:empresa_id, :recurso, :referencia, :quantidade)
        ON DUPLICATE KEY UPDATE quantidade = quantidade + VALUES(quantidade)");
    $query->bindValue(':empresa_id', (int) $empresaId, PDO::PARAM_INT);
    $query->bindValue(':recurso', (string) $recurso);
    $query->bindValue(':referencia', $referencia);
    $query->bindValue(':quantidade', max(1, (int) $incremento), PDO::PARAM_INT);
    $query->execute();
}

function saas_plano_mapear_pagina_recurso($pag)
{
    $pag = trim((string) $pag);

    $mapa = [
        'home' => 'menu_home',
        'usuarios' => 'menu_pessoas',
        'funcionarios' => 'menu_pessoas',
        'clientes' => 'menu_pessoas',
        'clientes_retorno' => 'menu_pessoas',
        'fornecedores' => 'menu_pessoas',
        'servicos' => 'menu_cadastros',
        'cargos' => 'menu_cadastros',
        'cat_servicos' => 'menu_cadastros',
        'grupos' => 'menu_cadastros',
        'acessos' => 'menu_cadastros',
        'produtos' => 'menu_produtos',
        'cat_produtos' => 'menu_produtos',
        'estoque' => 'menu_produtos',
        'saidas' => 'menu_produtos',
        'entradas' => 'menu_produtos',
        'vendas' => 'menu_financeiro',
        'compras' => 'menu_financeiro',
        'pagar' => 'menu_financeiro',
        'receber' => 'menu_financeiro',
        'comissoes' => 'menu_financeiro',
        'minhas_comissoes' => 'menu_financeiro',
        'meus_servicos' => 'menu_financeiro',
        'agendamentos' => 'menu_agendamentos',
        'agenda' => 'menu_agendamentos',
        'servicos_agenda' => 'menu_agendamentos',
        'horarios' => 'menu_agendamentos',
        'dias' => 'menu_agendamentos',
        'textos_index' => 'menu_site',
        'comentarios' => 'menu_site',
    ];

    return isset($mapa[$pag]) ? $mapa[$pag] : '';
}
