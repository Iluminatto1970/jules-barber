<?php

function saas_cli_fail($mensagem)
{
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "[ERRO] " . $mensagem . PHP_EOL);
        exit(1);
    }

    throw new Exception($mensagem);
}

function saas_normalizar_slug($texto)
{
    $slug = strtolower(trim((string) $texto));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    if ($slug == '') {
        saas_cli_fail('Slug invalido. Use apenas letras, numeros e hifen.');
    }

    return $slug;
}

function saas_normalizar_dominio($dominio)
{
    $dominio = strtolower(trim((string) $dominio));
    $dominio = preg_replace('/^https?:\/\//', '', $dominio);
    $dominio = preg_replace('/\/.*$/', '', $dominio);
    $dominio = preg_replace('/:\\d+$/', '', $dominio);

    if (strpos($dominio, 'www.') === 0) {
        $dominio = substr($dominio, 4);
    }

    if ($dominio == '') {
        saas_cli_fail('Dominio invalido.');
    }

    return $dominio;
}

function saas_dominio_publico($dominio)
{
    $dominio = saas_normalizar_dominio($dominio);

    if ($dominio === 'localhost') {
        return false;
    }

    if (filter_var($dominio, FILTER_VALIDATE_IP)) {
        return false;
    }

    if (strpos($dominio, '.') === false) {
        return false;
    }

    if (preg_match('/\.(local|localhost)$/', $dominio)) {
        return false;
    }

    return true;
}

function saas_validar_identificador($valor, $campo)
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $valor)) {
        saas_cli_fail($campo . ' invalido. Use apenas letras, numeros e underscore.');
    }

    return $valor;
}

function saas_abrir_servidor($host, $user, $pass)
{
    try {
        $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    } catch (Exception $e) {
        saas_cli_fail('Falha ao conectar no MySQL: ' . $e->getMessage());
    }
}

function saas_abrir_banco($host, $db, $user, $pass)
{
    try {
        $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    } catch (Exception $e) {
        saas_cli_fail('Falha ao conectar no banco ' . $db . ': ' . $e->getMessage());
    }
}

function saas_garantir_estrutura($pdoServer, $saasDb, $host, $user, $pass)
{
    $saasDb = saas_validar_identificador($saasDb, 'Banco SaaS');

    $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `{$saasDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdoSaas = saas_abrir_banco($host, $saasDb, $user, $pass);

    $pdoSaas->exec("CREATE TABLE IF NOT EXISTS empresas (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        nome VARCHAR(120) NOT NULL,
        slug VARCHAR(120) NOT NULL,
        banco VARCHAR(120) NOT NULL,
        db_host VARCHAR(120) NOT NULL DEFAULT 'localhost',
        db_usuario VARCHAR(120) NOT NULL DEFAULT 'root',
        db_senha VARCHAR(190) NOT NULL,
        ativo ENUM('Sim','Nao') NOT NULL DEFAULT 'Sim',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_empresas_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdoSaas->exec("CREATE TABLE IF NOT EXISTS empresas_dominios (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id INT UNSIGNED NOT NULL,
        dominio VARCHAR(190) NOT NULL,
        principal TINYINT(1) NOT NULL DEFAULT 0,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_empresas_dominios_dominio (dominio),
        KEY idx_empresas_dominios_empresa (empresa_id),
        CONSTRAINT fk_empresas_dominios_empresa
            FOREIGN KEY (empresa_id)
            REFERENCES empresas(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdoSaas->exec("CREATE TABLE IF NOT EXISTS empresas_tunnels (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id INT UNSIGNED NOT NULL,
        tunnel_nome VARCHAR(120) NOT NULL,
        tunnel_id CHAR(36) NOT NULL,
        dominio VARCHAR(190) NOT NULL,
        service_url VARCHAR(255) NOT NULL,
        status ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_empresas_tunnels_id (tunnel_id),
        UNIQUE KEY uq_empresas_tunnels_empresa_dominio (empresa_id, dominio),
        KEY idx_empresas_tunnels_empresa (empresa_id),
        CONSTRAINT fk_empresas_tunnels_empresa
            FOREIGN KEY (empresa_id)
            REFERENCES empresas(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdoSaas->exec("CREATE TABLE IF NOT EXISTS empresas_usuarios (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id INT UNSIGNED NOT NULL,
        nome VARCHAR(120) NOT NULL,
        email VARCHAR(160) NOT NULL,
        senha_crip VARCHAR(100) NOT NULL,
        ativo ENUM('Sim','Nao') NOT NULL DEFAULT 'Sim',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_empresas_usuarios_email (email),
        KEY idx_empresas_usuarios_empresa (empresa_id),
        CONSTRAINT fk_empresas_usuarios_empresa
            FOREIGN KEY (empresa_id)
            REFERENCES empresas(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdoSaas->exec("CREATE TABLE IF NOT EXISTS planos (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        nome VARCHAR(120) NOT NULL,
        slug VARCHAR(120) NOT NULL,
        descricao TEXT NULL,
        trial_dias INT UNSIGNED NOT NULL DEFAULT 14,
        ativo ENUM('Sim','Nao') NOT NULL DEFAULT 'Sim',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_planos_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdoSaas->exec("CREATE TABLE IF NOT EXISTS planos_recursos (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        plano_id INT UNSIGNED NOT NULL,
        recurso VARCHAR(120) NOT NULL,
        permitido ENUM('Sim','Nao') NOT NULL DEFAULT 'Sim',
        limite INT NULL,
        periodo ENUM('mensal','total') NOT NULL DEFAULT 'total',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_planos_recursos (plano_id, recurso),
        KEY idx_planos_recursos_plano (plano_id),
        CONSTRAINT fk_planos_recursos_plano
            FOREIGN KEY (plano_id)
            REFERENCES planos(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdoSaas->exec("CREATE TABLE IF NOT EXISTS empresas_assinaturas (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id INT UNSIGNED NOT NULL,
        plano_id INT UNSIGNED NOT NULL,
        status ENUM('Trial','Ativa','Suspensa','Cancelada') NOT NULL DEFAULT 'Trial',
        inicio_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        trial_ate DATE NULL,
        ciclo_ate DATE NULL,
        suspensa_em DATETIME NULL,
        observacoes VARCHAR(255) NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_empresas_assinaturas_empresa (empresa_id),
        KEY idx_empresas_assinaturas_plano (plano_id),
        CONSTRAINT fk_empresas_assinaturas_empresa
            FOREIGN KEY (empresa_id)
            REFERENCES empresas(id)
            ON DELETE CASCADE,
        CONSTRAINT fk_empresas_assinaturas_plano
            FOREIGN KEY (plano_id)
            REFERENCES planos(id)
            ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdoSaas->exec("CREATE TABLE IF NOT EXISTS empresas_uso_mensal (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id INT UNSIGNED NOT NULL,
        recurso VARCHAR(120) NOT NULL,
        referencia CHAR(7) NOT NULL,
        quantidade INT UNSIGNED NOT NULL DEFAULT 0,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_empresas_uso_ref (empresa_id, recurso, referencia),
        KEY idx_empresas_uso_empresa (empresa_id),
        CONSTRAINT fk_empresas_uso_empresa
            FOREIGN KEY (empresa_id)
            REFERENCES empresas(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdoSaas->exec("CREATE TABLE IF NOT EXISTS empresas_eventos_billing (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id INT UNSIGNED NOT NULL,
        tipo VARCHAR(120) NOT NULL,
        recurso VARCHAR(120) NULL,
        detalhe TEXT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_empresas_eventos_empresa (empresa_id),
        CONSTRAINT fk_empresas_eventos_empresa
            FOREIGN KEY (empresa_id)
            REFERENCES empresas(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    saas_pagbank_garantir_estrutura($pdoSaas);
    saas_seed_planos($pdoSaas);

    return $pdoSaas;
}

function saas_registrar_empresa($pdoSaas, $dadosEmpresa, $dominios)
{
    $nome = trim((string) $dadosEmpresa['nome']);
    $slug = saas_normalizar_slug($dadosEmpresa['slug']);
    $banco = saas_validar_identificador($dadosEmpresa['banco'], 'Banco tenant');
    $dbHost = trim((string) $dadosEmpresa['db_host']);
    $dbUsuario = trim((string) $dadosEmpresa['db_usuario']);
    $dbSenha = (string) $dadosEmpresa['db_senha'];
    $ativo = $dadosEmpresa['ativo'] === 'Nao' ? 'Nao' : 'Sim';

    if ($nome == '') {
        saas_cli_fail('Nome da empresa nao pode ser vazio.');
    }

    $query = $pdoSaas->prepare('SELECT id FROM empresas WHERE slug = :slug LIMIT 1');
    $query->bindValue(':slug', $slug);
    $query->execute();
    $empresa = $query->fetch(PDO::FETCH_ASSOC);

    if ($empresa) {
        $empresaId = (int) $empresa['id'];
        $update = $pdoSaas->prepare('UPDATE empresas SET nome = :nome, banco = :banco, db_host = :db_host, db_usuario = :db_usuario, db_senha = :db_senha, ativo = :ativo WHERE id = :id');
        $update->execute([
            ':nome' => $nome,
            ':banco' => $banco,
            ':db_host' => $dbHost,
            ':db_usuario' => $dbUsuario,
            ':db_senha' => $dbSenha,
            ':ativo' => $ativo,
            ':id' => $empresaId,
        ]);
    } else {
        $insert = $pdoSaas->prepare('INSERT INTO empresas (nome, slug, banco, db_host, db_usuario, db_senha, ativo) VALUES (:nome, :slug, :banco, :db_host, :db_usuario, :db_senha, :ativo)');
        $insert->execute([
            ':nome' => $nome,
            ':slug' => $slug,
            ':banco' => $banco,
            ':db_host' => $dbHost,
            ':db_usuario' => $dbUsuario,
            ':db_senha' => $dbSenha,
            ':ativo' => $ativo,
        ]);

        $empresaId = (int) $pdoSaas->lastInsertId();
    }

    $principal = 1;
    foreach ($dominios as $dominio) {
        $dominio = saas_normalizar_dominio($dominio);

        $insertDominio = $pdoSaas->prepare('INSERT INTO empresas_dominios (empresa_id, dominio, principal) VALUES (:empresa_id, :dominio, :principal) ON DUPLICATE KEY UPDATE empresa_id = VALUES(empresa_id), principal = VALUES(principal)');
        $insertDominio->execute([
            ':empresa_id' => $empresaId,
            ':dominio' => $dominio,
            ':principal' => $principal,
        ]);

        $principal = 0;
    }

    return $empresaId;
}

function saas_seed_planos($pdoSaas)
{
    $insertPlano = $pdoSaas->prepare("INSERT INTO planos (nome, slug, descricao, trial_dias, valor_mensal, ativo)
        VALUES (:nome, :slug, :descricao, :trial_dias, :valor_mensal, 'Sim')
        ON DUPLICATE KEY UPDATE
            nome = VALUES(nome),
            descricao = VALUES(descricao),
            trial_dias = VALUES(trial_dias),
            valor_mensal = VALUES(valor_mensal),
            ativo = 'Sim'");

    $insertPlano->execute([
        ':nome' => 'Starter',
        ':slug' => 'starter',
        ':descricao' => 'Plano padrao inicial para operacao SaaS',
        ':trial_dias' => 14,
        ':valor_mensal' => 79.90,
    ]);

    $planoId = saas_obter_plano_id_por_slug($pdoSaas, 'starter');
    if (!$planoId) {
        saas_cli_fail('Nao foi possivel criar/obter o plano starter.');
    }

    $recursos = [
        ['recurso' => 'acesso_painel', 'permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        ['recurso' => 'menu_home', 'permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        ['recurso' => 'menu_configuracoes', 'permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        ['recurso' => 'menu_pessoas', 'permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        ['recurso' => 'menu_cadastros', 'permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        ['recurso' => 'menu_produtos', 'permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        ['recurso' => 'menu_financeiro', 'permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        ['recurso' => 'menu_agendamentos', 'permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        ['recurso' => 'menu_relatorios', 'permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        ['recurso' => 'menu_site', 'permitido' => 'Sim', 'limite' => null, 'periodo' => 'total'],
        ['recurso' => 'limite_usuarios', 'permitido' => 'Sim', 'limite' => 20, 'periodo' => 'total'],
        ['recurso' => 'limite_produtos', 'permitido' => 'Sim', 'limite' => 500, 'periodo' => 'total'],
        ['recurso' => 'limite_servicos', 'permitido' => 'Sim', 'limite' => 120, 'periodo' => 'total'],
        ['recurso' => 'limite_agendamentos_mes', 'permitido' => 'Sim', 'limite' => 2000, 'periodo' => 'mensal'],
    ];

    $insertRecurso = $pdoSaas->prepare("INSERT INTO planos_recursos (plano_id, recurso, permitido, limite, periodo)
        VALUES (:plano_id, :recurso, :permitido, :limite, :periodo)
        ON DUPLICATE KEY UPDATE
            permitido = VALUES(permitido),
            limite = VALUES(limite),
            periodo = VALUES(periodo)");

    foreach ($recursos as $recurso) {
        $insertRecurso->bindValue(':plano_id', $planoId, PDO::PARAM_INT);
        $insertRecurso->bindValue(':recurso', $recurso['recurso']);
        $insertRecurso->bindValue(':permitido', $recurso['permitido']);
        if ($recurso['limite'] === null) {
            $insertRecurso->bindValue(':limite', null, PDO::PARAM_NULL);
        } else {
            $insertRecurso->bindValue(':limite', (int) $recurso['limite'], PDO::PARAM_INT);
        }
        $insertRecurso->bindValue(':periodo', $recurso['periodo']);
        $insertRecurso->execute();
    }
}

function saas_obter_plano_id_por_slug($pdoSaas, $slug)
{
    $slug = saas_normalizar_slug($slug);
    $query = $pdoSaas->prepare("SELECT id FROM planos WHERE slug = :slug LIMIT 1");
    $query->bindValue(':slug', $slug);
    $query->execute();
    $plano = $query->fetch(PDO::FETCH_ASSOC);

    if (!$plano) {
        return null;
    }

    return (int) $plano['id'];
}

function saas_garantir_assinatura_empresa($pdoSaas, $empresaId, $planoSlug = 'starter', $status = 'Trial', $trialDias = null)
{
    $empresaId = (int) $empresaId;
    if ($empresaId <= 0) {
        saas_cli_fail('Empresa invalida para assinatura.');
    }

    $planoId = saas_obter_plano_id_por_slug($pdoSaas, $planoSlug);
    if (!$planoId) {
        saas_cli_fail('Plano nao encontrado: ' . $planoSlug);
    }

    $statusValidos = ['Trial', 'Ativa', 'Suspensa', 'Cancelada'];
    if (!in_array($status, $statusValidos, true)) {
        $status = 'Trial';
    }

    if ($trialDias === null) {
        $queryPlano = $pdoSaas->prepare("SELECT trial_dias FROM planos WHERE id = :id LIMIT 1");
        $queryPlano->bindValue(':id', $planoId, PDO::PARAM_INT);
        $queryPlano->execute();
        $plano = $queryPlano->fetch(PDO::FETCH_ASSOC);
        $trialDias = $plano ? (int) $plano['trial_dias'] : 14;
    } else {
        $trialDias = (int) $trialDias;
    }

    if ($trialDias < 0) {
        $trialDias = 0;
    }

    $trialAte = null;
    if ($status === 'Trial') {
        $trialAte = date('Y-m-d', strtotime('+' . $trialDias . ' days'));
    }

    $query = $pdoSaas->prepare("INSERT INTO empresas_assinaturas (empresa_id, plano_id, status, trial_ate)
        VALUES (:empresa_id, :plano_id, :status, :trial_ate)
        ON DUPLICATE KEY UPDATE
            plano_id = VALUES(plano_id),
            status = VALUES(status),
            trial_ate = VALUES(trial_ate),
            suspensa_em = CASE
                WHEN VALUES(status) = 'Suspensa' THEN NOW()
                ELSE NULL
            END");

    $query->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
    $query->bindValue(':plano_id', $planoId, PDO::PARAM_INT);
    $query->bindValue(':status', $status);
    if ($trialAte === null) {
        $query->bindValue(':trial_ate', null, PDO::PARAM_NULL);
    } else {
        $query->bindValue(':trial_ate', $trialAte);
    }
    $query->execute();
}

function saas_normalizar_email($email)
{
    return strtolower(trim((string) $email));
}

function saas_email_ja_cadastrado($pdoSaas, $email)
{
    $email = saas_normalizar_email($email);
    if ($email == '') {
        return false;
    }

    $query = $pdoSaas->prepare("SELECT id FROM empresas_usuarios WHERE email = :email LIMIT 1");
    $query->bindValue(':email', $email);
    $query->execute();

    return (bool) $query->fetch(PDO::FETCH_ASSOC);
}

function saas_dominio_base_por_host($host)
{
    $host = saas_normalizar_dominio($host);
    $partes = explode('.', $host);
    $total = count($partes);

    if ($total >= 2) {
        return $partes[$total - 2] . '.' . $partes[$total - 1];
    }

    return $host;
}

function saas_slug_disponivel($pdoSaas, $slugBase)
{
    $slugBase = saas_normalizar_slug($slugBase);
    $slug = $slugBase;
    $contador = 1;

    while (true) {
        $query = $pdoSaas->prepare("SELECT id FROM empresas WHERE slug = :slug LIMIT 1");
        $query->bindValue(':slug', $slug);
        $query->execute();
        $existe = $query->fetch(PDO::FETCH_ASSOC);

        if (!$existe) {
            return $slug;
        }

        $contador++;
        $slug = $slugBase . '-' . $contador;
    }
}

function saas_nome_banco_disponivel($pdoSaas, $slug)
{
    $base = 'barbearia_' . str_replace('-', '_', saas_normalizar_slug($slug));
    $base = preg_replace('/[^a-zA-Z0-9_]/', '', $base);

    if ($base == '' || preg_match('/^[0-9]/', $base)) {
        $base = 'barbearia_tenant';
    }

    $base = substr($base, 0, 55);
    $nome = $base;
    $contador = 1;

    while (true) {
        $query = $pdoSaas->prepare("SELECT id FROM empresas WHERE banco = :banco LIMIT 1");
        $query->bindValue(':banco', $nome);
        $query->execute();
        $existe = $query->fetch(PDO::FETCH_ASSOC);

        if (!$existe) {
            return $nome;
        }

        $contador++;
        $sufixo = '_' . $contador;
        $nome = substr($base, 0, 64 - strlen($sufixo)) . $sufixo;
    }
}

function saas_raiz_projeto()
{
    return dirname(dirname(dirname(dirname(__DIR__))));
}

function saas_arquivo_sql_modelo()
{
    return saas_raiz_projeto() . '/sql/sql/barbearia_vazio.sql';
}

function saas_importar_sql_banco($host, $user, $pass, $banco, $arquivoSql)
{
    if (!file_exists($arquivoSql)) {
        saas_cli_fail('Arquivo SQL de modelo nao encontrado: ' . $arquivoSql);
    }

    $comando = 'mysql -h ' . escapeshellarg($host)
        . ' -u ' . escapeshellarg($user)
        . ' -p' . escapeshellarg($pass)
        . ' ' . escapeshellarg($banco)
        . ' < ' . escapeshellarg($arquivoSql);

    $resultado = saas_executar_comando($comando);
    if ($resultado['codigo'] !== 0) {
        saas_cli_fail('Falha ao importar SQL no banco tenant: ' . $resultado['saida']);
    }
}

function saas_copiar_imagens_modelo($host, $user, $pass, $bancoNovo, $bancoModelo = 'barbearia')
{
    $tabelasComImagens = ['config', 'usuarios', 'servicos', 'produtos', 'clientes', 'comentarios'];
    
    $pdoNovo = saas_abrir_banco($host, $bancoNovo, $user, $pass);
    $pdoModelo = saas_abrir_banco($host, $bancoModelo, $user, $pass);
    
    foreach ($tabelasComImagens as $tabela) {
        $query = $pdoModelo->query("SELECT * FROM {$tabela} WHERE 1=1");
        $registros = $query->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($registros as $registro) {
            $campos = array_keys($registro);
            $valores = array_values($registro);
            
            $placeholders = array_fill(0, count($campos), '?');
            $sql = "INSERT IGNORE INTO {$tabela} (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $pdoNovo->prepare($sql);
            $stmt->execute($valores);
        }
    }
}

function saas_criar_banco_tenant_modelo($pdoServer, $host, $user, $pass, $banco)
{
    $banco = saas_validar_identificador($banco, 'Banco tenant');
    $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `{$banco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdoTenant = saas_abrir_banco($host, $banco, $user, $pass);
    $tabelas = $pdoTenant->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);

    if (count($tabelas) > 0) {
        saas_cli_fail('Banco tenant ja possui estrutura. Use outro nome de banco.');
    }

    $arquivoSql = saas_arquivo_sql_modelo();
    saas_importar_sql_banco($host, $user, $pass, $banco, $arquivoSql);
    
    saas_copiar_imagens_modelo($host, $user, $pass, $banco, 'barbearia');
}

function saas_configurar_tenant_admin($host, $banco, $user, $pass, $empresaNome, $responsavelNome, $email, $senha)
{
    $pdoTenant = saas_abrir_banco($host, $banco, $user, $pass);

    $empresaNome = trim((string) $empresaNome);
    $responsavelNome = trim((string) $responsavelNome);
    $email = saas_normalizar_email($email);
    $senha = (string) $senha;

    if ($responsavelNome == '') {
        $responsavelNome = 'Admin';
    }

    $senhaCrip = md5($senha);

    $queryConfig = $pdoTenant->prepare("UPDATE config SET nome = :nome, email = :email LIMIT 1");
    $queryConfig->bindValue(':nome', $empresaNome);
    $queryConfig->bindValue(':email', $email);
    $queryConfig->execute();

    $queryAdmin = $pdoTenant->query("SELECT id FROM usuarios WHERE nivel = 'Administrador' ORDER BY id ASC LIMIT 1");
    $admin = $queryAdmin->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $update = $pdoTenant->prepare("UPDATE usuarios SET nome = :nome, email = :email, senha = :senha, senha_crip = :senha_crip, ativo = 'Sim', atendimento = 'Sim' WHERE id = :id");
        $update->bindValue(':nome', $responsavelNome);
        $update->bindValue(':email', $email);
        $update->bindValue(':senha', $senha);
        $update->bindValue(':senha_crip', $senhaCrip);
        $update->bindValue(':id', (int) $admin['id'], PDO::PARAM_INT);
        $update->execute();
    } else {
        $insert = $pdoTenant->prepare("INSERT INTO usuarios SET nome = :nome, email = :email, cpf = '000.000.000-00', senha = :senha, senha_crip = :senha_crip, nivel = 'Administrador', data = curDate(), ativo = 'Sim', telefone = '', endereco = '', foto = 'sem-foto.jpg', atendimento = 'Sim'");
        $insert->bindValue(':nome', $responsavelNome);
        $insert->bindValue(':email', $email);
        $insert->bindValue(':senha', $senha);
        $insert->bindValue(':senha_crip', $senhaCrip);
        $insert->execute();
    }
}

function saas_registrar_usuario_empresa($pdoSaas, $empresaId, $nome, $email, $senha)
{
    $query = $pdoSaas->prepare("INSERT INTO empresas_usuarios (empresa_id, nome, email, senha_crip, ativo)
        VALUES (:empresa_id, :nome, :email, :senha_crip, 'Sim')
        ON DUPLICATE KEY UPDATE
            empresa_id = VALUES(empresa_id),
            nome = VALUES(nome),
            senha_crip = VALUES(senha_crip),
            ativo = 'Sim'");

    $query->bindValue(':empresa_id', (int) $empresaId, PDO::PARAM_INT);
    $query->bindValue(':nome', trim((string) $nome));
    $query->bindValue(':email', saas_normalizar_email($email));
    $query->bindValue(':senha_crip', md5((string) $senha));
    $query->execute();
}

function saas_normalizar_tunnel_nome($nome)
{
    $nome = strtolower(trim((string) $nome));
    $nome = preg_replace('/[^a-z0-9-]+/', '-', $nome);
    $nome = trim($nome, '-');

    if ($nome == '') {
        saas_cli_fail('Nome do tunnel invalido.');
    }

    if (strlen($nome) > 60) {
        $nome = substr($nome, 0, 60);
        $nome = rtrim($nome, '-');
    }

    return $nome;
}

function saas_executar_comando($comando)
{
    $saida = [];
    $codigo = 0;
    exec($comando . ' 2>&1', $saida, $codigo);

    return [
        'codigo' => $codigo,
        'saida' => trim(implode(PHP_EOL, $saida)),
    ];
}

function saas_cloudflared_disponivel()
{
    $resultado = saas_executar_comando('cloudflared --version');

    return $resultado['codigo'] === 0;
}

function saas_obter_tunnels_cloudflared()
{
    $resultado = saas_executar_comando('cloudflared tunnel list');
    if ($resultado['codigo'] !== 0) {
        saas_cli_fail('Falha ao listar tunnels do cloudflared: ' . $resultado['saida']);
    }

    $linhas = explode(PHP_EOL, $resultado['saida']);
    $tunnels = [];

    foreach ($linhas as $linha) {
        if (preg_match('/^([0-9a-f-]{36})\s+(.+?)\s+\d{4}-\d{2}-\d{2}T/', trim($linha), $matches)) {
            $tunnels[] = [
                'id' => $matches[1],
                'nome' => trim($matches[2]),
            ];
        }
    }

    return $tunnels;
}

function saas_obter_tunnel_id_por_nome($nome)
{
    $nome = saas_normalizar_tunnel_nome($nome);
    $tunnels = saas_obter_tunnels_cloudflared();

    foreach ($tunnels as $tunnel) {
        if ($tunnel['nome'] === $nome) {
            return $tunnel['id'];
        }
    }

    return null;
}

function saas_criar_tunnel($nome)
{
    $nome = saas_normalizar_tunnel_nome($nome);

    $existente = saas_obter_tunnel_id_por_nome($nome);
    if ($existente) {
        return [
            'nome' => $nome,
            'id' => $existente,
            'criado' => false,
        ];
    }

    $comando = 'cloudflared tunnel create ' . escapeshellarg($nome);
    $resultado = saas_executar_comando($comando);

    if ($resultado['codigo'] !== 0) {
        saas_cli_fail('Falha ao criar tunnel: ' . $resultado['saida']);
    }

    $tunnelId = null;
    if (preg_match('/([0-9a-f-]{36})/', $resultado['saida'], $matches)) {
        $tunnelId = $matches[1];
    }

    if (!$tunnelId) {
        $tunnelId = saas_obter_tunnel_id_por_nome($nome);
    }

    if (!$tunnelId) {
        saas_cli_fail('Tunnel criado, mas nao foi possivel identificar o ID.');
    }

    return [
        'nome' => $nome,
        'id' => $tunnelId,
        'criado' => true,
    ];
}

function saas_criar_dns_tunnel($tunnelId, $dominio)
{
    $dominio = saas_normalizar_dominio($dominio);
    $comando = 'cloudflared tunnel route dns ' . escapeshellarg($tunnelId) . ' ' . escapeshellarg($dominio);
    $resultado = saas_executar_comando($comando);

    if ($resultado['codigo'] !== 0) {
        $saida = strtolower($resultado['saida']);
        if (strpos($saida, 'already') === false && strpos($saida, 'exist') === false) {
            saas_cli_fail('Falha ao criar DNS no Cloudflare: ' . $resultado['saida']);
        }
    }

    return true;
}

function saas_salvar_config_tunnel($slug, $tunnelId, $dominios, $serviceUrl)
{
    $slug = saas_normalizar_slug($slug);

    if (!is_array($dominios)) {
        $dominios = [$dominios];
    }

    $dominiosNormalizados = [];
    foreach ($dominios as $dominio) {
        $dominioNormalizado = saas_normalizar_dominio($dominio);
        if (!in_array($dominioNormalizado, $dominiosNormalizados, true)) {
            $dominiosNormalizados[] = $dominioNormalizado;
        }
    }

    if (count($dominiosNormalizados) === 0) {
        saas_cli_fail('Nenhum dominio valido para gerar configuracao de tunnel.');
    }

    $home = getenv('HOME') ?: '/home/iluminatto';
    $credentialsFile = rtrim($home, '/') . '/.cloudflared/' . $tunnelId . '.json';

    $dir = __DIR__ . '/tunnels';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $configPath = $dir . '/' . $slug . '.yml';
    $conteudo = "tunnel: {$tunnelId}\n";
    $conteudo .= "credentials-file: {$credentialsFile}\n\n";
    $conteudo .= "ingress:\n";
    foreach ($dominiosNormalizados as $dominio) {
        $conteudo .= "  - hostname: {$dominio}\n";
        $conteudo .= "    service: {$serviceUrl}\n";
    }
    $conteudo .= "  - service: http_status:404\n";

    file_put_contents($configPath, $conteudo);

    return $configPath;
}

function saas_registrar_tunnel_empresa($pdoSaas, $empresaId, $tunnelNome, $tunnelId, $dominio, $serviceUrl)
{
    $query = $pdoSaas->prepare("INSERT INTO empresas_tunnels (empresa_id, tunnel_nome, tunnel_id, dominio, service_url, status)
        VALUES (:empresa_id, :tunnel_nome, :tunnel_id, :dominio, :service_url, 'Ativo')
        ON DUPLICATE KEY UPDATE
            tunnel_nome = VALUES(tunnel_nome),
            tunnel_id = VALUES(tunnel_id),
            service_url = VALUES(service_url),
            status = 'Ativo'");

    $query->execute([
        ':empresa_id' => $empresaId,
        ':tunnel_nome' => $tunnelNome,
        ':tunnel_id' => $tunnelId,
        ':dominio' => saas_normalizar_dominio($dominio),
        ':service_url' => trim((string) $serviceUrl),
    ]);
}

function saas_tunnel_em_execucao($tunnelId)
{
    $padrao = '^cloudflared .*tunnel run .*' . preg_quote((string) $tunnelId, '/') . '$';
    $comando = 'pgrep -f ' . escapeshellarg($padrao);
    $resultado = saas_executar_comando($comando);

    return $resultado['codigo'] === 0;
}

function saas_iniciar_tunnel_background($slug, $tunnelId, $configPath)
{
    $slug = saas_normalizar_slug($slug);

    if (!file_exists($configPath)) {
        saas_cli_fail('Arquivo de configuracao do tunnel nao encontrado: ' . $configPath);
    }

    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $logPath = $logDir . '/' . $slug . '.log';

    if (saas_tunnel_em_execucao($tunnelId)) {
        return [
            'log' => $logPath,
            'iniciado' => false,
        ];
    }

    $comando = 'nohup cloudflared --config ' . escapeshellarg($configPath)
        . ' tunnel run ' . escapeshellarg($tunnelId)
        . ' > ' . escapeshellarg($logPath)
        . ' 2>&1 &';

    saas_executar_comando($comando);
    usleep(1200000);

    if (!saas_tunnel_em_execucao($tunnelId)) {
        saas_cli_fail('Tunnel criado, mas nao iniciou em background. Verifique log: ' . $logPath);
    }

    return [
        'log' => $logPath,
        'iniciado' => true,
    ];
}

function saas_pagbank_env($chave, $padrao = '')
{
    $valor = getenv((string) $chave);
    if ($valor === false) {
        return $padrao;
    }

    return is_string($valor) ? trim($valor) : $padrao;
}

function saas_pagbank_executar_ddl($pdoSaas, $sql, $errosIgnorados = [])
{
    try {
        $pdoSaas->exec($sql);
        return true;
    } catch (Exception $e) {
        $mensagem = strtolower((string) $e->getMessage());
        foreach ($errosIgnorados as $erro) {
            if ($erro !== '' && strpos($mensagem, strtolower((string) $erro)) !== false) {
                return false;
            }
        }

        throw $e;
    }
}

function saas_pagbank_coluna_existe($pdoSaas, $tabela, $coluna)
{
    if (!$pdoSaas instanceof PDO) {
        return false;
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $tabela) || !preg_match('/^[a-zA-Z0-9_]+$/', (string) $coluna)) {
        return false;
    }

    try {
        $query = $pdoSaas->query("SHOW COLUMNS FROM `{$tabela}` LIKE " . $pdoSaas->quote((string) $coluna));
        $col = $query ? $query->fetch(PDO::FETCH_ASSOC) : false;

        return (bool) $col;
    } catch (Exception $e) {
        return false;
    }
}

function saas_pagbank_indice_existe($pdoSaas, $tabela, $indice)
{
    if (!$pdoSaas instanceof PDO) {
        return false;
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $tabela) || !preg_match('/^[a-zA-Z0-9_]+$/', (string) $indice)) {
        return false;
    }

    try {
        $query = $pdoSaas->query("SHOW INDEX FROM `{$tabela}`");
        $indices = $query ? $query->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($indices as $item) {
            if (isset($item['Key_name']) && (string) $item['Key_name'] === (string) $indice) {
                return true;
            }
        }

        return false;
    } catch (Exception $e) {
        return false;
    }
}

function saas_pagbank_indice_colunas($pdoSaas, $tabela, $indice)
{
    if (!$pdoSaas instanceof PDO) {
        return [];
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $tabela) || !preg_match('/^[a-zA-Z0-9_]+$/', (string) $indice)) {
        return [];
    }

    try {
        $query = $pdoSaas->query("SHOW INDEX FROM `{$tabela}`");
        $indices = $query ? $query->fetchAll(PDO::FETCH_ASSOC) : [];
        $colunas = [];

        foreach ($indices as $item) {
            if (!isset($item['Key_name']) || (string) $item['Key_name'] !== (string) $indice) {
                continue;
            }

            $ordem = isset($item['Seq_in_index']) ? (int) $item['Seq_in_index'] : 0;
            $coluna = isset($item['Column_name']) ? strtolower(trim((string) $item['Column_name'])) : '';
            if ($ordem > 0 && $coluna !== '') {
                $colunas[$ordem] = $coluna;
            }
        }

        if (empty($colunas)) {
            return [];
        }

        ksort($colunas);
        return array_values($colunas);
    } catch (Exception $e) {
        return [];
    }
}

function saas_pagbank_garantir_estrutura($pdoSaas)
{
    static $executado = false;

    if ($executado || !$pdoSaas instanceof PDO) {
        return;
    }

    $executado = true;

    try {
        if (!saas_pagbank_coluna_existe($pdoSaas, 'planos', 'valor_mensal')) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE planos ADD COLUMN valor_mensal DECIMAL(10,2) NOT NULL DEFAULT 79.90 AFTER trial_dias",
                ['duplicate column']
            );
        }

        if (!saas_pagbank_coluna_existe($pdoSaas, 'planos', 'pagbank_referencia')) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE planos ADD COLUMN pagbank_referencia VARCHAR(120) NULL AFTER valor_mensal",
                ['duplicate column']
            );
        }

        if (!saas_pagbank_coluna_existe($pdoSaas, 'empresas_assinaturas', 'ultimo_pagamento_em')) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_assinaturas ADD COLUMN ultimo_pagamento_em DATETIME NULL AFTER ciclo_ate",
                ['duplicate column']
            );
        }

        $pdoSaas->exec("CREATE TABLE IF NOT EXISTS empresas_pagamentos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            empresa_id INT UNSIGNED NOT NULL,
            assinatura_id INT UNSIGNED NULL,
            plano_id INT UNSIGNED NULL,
            gateway VARCHAR(40) NOT NULL DEFAULT 'pagbank',
            metodo_pagamento ENUM('PIX','Cartao') NOT NULL DEFAULT 'PIX',
            pedido_referencia VARCHAR(120) NOT NULL,
            idempotency_key VARCHAR(120) NULL,
            pagbank_order_id VARCHAR(120) NULL,
            webhook_evento_id VARCHAR(120) NULL,
            status ENUM('Pendente','Pago','Cancelado','Expirado','Falha') NOT NULL DEFAULT 'Pendente',
            status_detalhe VARCHAR(80) NULL,
            valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            moeda CHAR(3) NOT NULL DEFAULT 'BRL',
            qr_code_text LONGTEXT NULL,
            qr_code_link VARCHAR(255) NULL,
            expiracao_em DATETIME NULL,
            payload_criacao LONGTEXT NULL,
            payload_status LONGTEXT NULL,
            payload_webhook LONGTEXT NULL,
            tentativas_consulta INT UNSIGNED NOT NULL DEFAULT 0,
            pago_em DATETIME NULL,
            cancelado_em DATETIME NULL,
            cancel_reason VARCHAR(255) NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_empresas_pagamentos_referencia (pedido_referencia),
            UNIQUE KEY uq_empresas_pagamentos_idempotency (empresa_id, idempotency_key),
            KEY idx_empresas_pagamentos_empresa (empresa_id),
            KEY idx_empresas_pagamentos_empresa_status (empresa_id, status),
            KEY idx_empresas_pagamentos_pagbank_order (pagbank_order_id),
            KEY idx_empresas_pagamentos_status_exp (status, expiracao_em),
            CONSTRAINT fk_empresas_pagamentos_empresa
                FOREIGN KEY (empresa_id)
                REFERENCES empresas(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        if (!saas_pagbank_coluna_existe($pdoSaas, 'empresas_pagamentos', 'metodo_pagamento')) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_pagamentos ADD COLUMN metodo_pagamento ENUM('PIX','Cartao') NOT NULL DEFAULT 'PIX' AFTER gateway",
                ['duplicate column']
            );
        }

        if (!saas_pagbank_coluna_existe($pdoSaas, 'empresas_pagamentos', 'idempotency_key')) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_pagamentos ADD COLUMN idempotency_key VARCHAR(120) NULL AFTER pedido_referencia",
                ['duplicate column']
            );
        }

        if (!saas_pagbank_coluna_existe($pdoSaas, 'empresas_pagamentos', 'webhook_evento_id')) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_pagamentos ADD COLUMN webhook_evento_id VARCHAR(120) NULL AFTER pagbank_order_id",
                ['duplicate column']
            );
        }

        if (!saas_pagbank_coluna_existe($pdoSaas, 'empresas_pagamentos', 'status_detalhe')) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_pagamentos ADD COLUMN status_detalhe VARCHAR(80) NULL AFTER status",
                ['duplicate column']
            );
        }

        if (!saas_pagbank_coluna_existe($pdoSaas, 'empresas_pagamentos', 'tentativas_consulta')) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_pagamentos ADD COLUMN tentativas_consulta INT UNSIGNED NOT NULL DEFAULT 0 AFTER payload_webhook",
                ['duplicate column']
            );
        }

        if (!saas_pagbank_coluna_existe($pdoSaas, 'empresas_pagamentos', 'cancelado_em')) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_pagamentos ADD COLUMN cancelado_em DATETIME NULL AFTER pago_em",
                ['duplicate column']
            );
        }

        if (!saas_pagbank_coluna_existe($pdoSaas, 'empresas_pagamentos', 'cancel_reason')) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_pagamentos ADD COLUMN cancel_reason VARCHAR(255) NULL AFTER cancelado_em",
                ['duplicate column']
            );
        }

        $colunasIdempotency = saas_pagbank_indice_colunas($pdoSaas, 'empresas_pagamentos', 'uq_empresas_pagamentos_idempotency');
        $colunasEsperadasIdempotency = ['empresa_id', 'idempotency_key'];
        if (empty($colunasIdempotency)) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_pagamentos ADD UNIQUE KEY uq_empresas_pagamentos_idempotency (empresa_id, idempotency_key)",
                ['duplicate key name']
            );
        } elseif ($colunasIdempotency !== $colunasEsperadasIdempotency) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_pagamentos DROP INDEX uq_empresas_pagamentos_idempotency",
                ['check that column/key exists', 'cant drop', 'cannot drop']
            );
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_pagamentos ADD UNIQUE KEY uq_empresas_pagamentos_idempotency (empresa_id, idempotency_key)",
                ['duplicate key name']
            );
        }

        if (!saas_pagbank_indice_existe($pdoSaas, 'empresas_pagamentos', 'idx_empresas_pagamentos_status_exp')) {
            saas_pagbank_executar_ddl(
                $pdoSaas,
                "ALTER TABLE empresas_pagamentos ADD KEY idx_empresas_pagamentos_status_exp (status, expiracao_em)",
                ['duplicate key name']
            );
        }

        $pdoSaas->exec("UPDATE planos SET valor_mensal = 79.90 WHERE valor_mensal <= 0");
    } catch (Exception $e) {
        // Falha de migracao nao deve derrubar a aplicacao inteira.
    }
}

function saas_pagbank_config()
{
    $modo = strtolower((string) saas_pagbank_env('PAGBANK_MODE', 'sandbox'));
    if ($modo !== 'production') {
        $modo = 'sandbox';
    }

    return [
        'client_id' => (string) saas_pagbank_env('PAGBANK_CLIENT_ID', ''),
        'client_secret' => (string) saas_pagbank_env('PAGBANK_CLIENT_SECRET', ''),
        'email' => (string) saas_pagbank_env('PAGBANK_EMAIL', ''),
        'token' => (string) saas_pagbank_env('PAGBANK_TOKEN', ''),
        'modo' => $modo,
        'simulation' => strtolower((string) saas_pagbank_env('PAGBANK_SIMULATION', 'false')) === 'true',
        'webhook_token' => (string) saas_pagbank_env('PAGBANK_WEBHOOK_TOKEN', ''),
        'webhook_require_token' => strtolower((string) saas_pagbank_env('PAGBANK_WEBHOOK_REQUIRE_TOKEN', 'true')) !== 'false',
        'webhook_hmac_secret' => (string) saas_pagbank_env('PAGBANK_WEBHOOK_HMAC_SECRET', ''),
        'require_card_token' => strtolower((string) saas_pagbank_env('PAGBANK_REQUIRE_CARD_TOKEN', 'true')) !== 'false',
        'allow_plain_card' => strtolower((string) saas_pagbank_env('PAGBANK_ALLOW_PLAIN_CARD', 'false')) === 'true',
        'auto_refund' => strtolower((string) saas_pagbank_env('PAGBANK_ENABLE_AUTO_REFUND', 'false')) === 'true',
        'notify_email_from' => (string) saas_pagbank_env('PAGBANK_NOTIFY_EMAIL_FROM', ''),
        'notify_email_reply_to' => (string) saas_pagbank_env('PAGBANK_NOTIFY_EMAIL_REPLY_TO', ''),
        'expire_pending_minutes' => max(30, (int) saas_pagbank_env('PAGBANK_EXPIRE_PENDING_MINUTES', '1440')),
        'reconciliacao_limit' => max(10, (int) saas_pagbank_env('PAGBANK_RECONCILE_LIMIT', '200')),
        'dias_renovacao' => max(1, (int) saas_pagbank_env('PAGBANK_RENEW_DAYS', '30')),
    ];
}

function saas_pagbank_api_base($modo = 'sandbox')
{
    return $modo === 'production'
        ? 'https://api.pagbank.com.br'
        : 'https://sandbox.api.pagseguro.com';
}

function saas_pagbank_csrf_token($chave = 'saas_checkout_csrf')
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    $chaveSessao = trim((string) $chave);
    if ($chaveSessao === '') {
        $chaveSessao = 'saas_checkout_csrf';
    }

    if (empty($_SESSION[$chaveSessao]) || !is_string($_SESSION[$chaveSessao])) {
        $_SESSION[$chaveSessao] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION[$chaveSessao];
}

function saas_pagbank_csrf_validar($token, $chave = 'saas_checkout_csrf')
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $chaveSessao = trim((string) $chave);
    if ($chaveSessao === '') {
        $chaveSessao = 'saas_checkout_csrf';
    }

    if (!isset($_SESSION[$chaveSessao]) || !is_string($_SESSION[$chaveSessao])) {
        return false;
    }

    $token = trim((string) $token);
    if ($token === '') {
        return false;
    }

    return hash_equals((string) $_SESSION[$chaveSessao], $token);
}

function saas_pagbank_normalizar_cpf($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', (string) $cpf);
    return strlen($cpf) === 11 ? $cpf : '';
}

function saas_pagbank_status_local($statusApi)
{
    $statusApi = strtolower(trim((string) $statusApi));

    if ($statusApi === '') {
        return 'Pendente';
    }

    if (saas_pagbank_status_pago($statusApi)) {
        return 'Pago';
    }

    if (in_array($statusApi, ['expired', 'overdue', 'timed_out', 'timeout'], true)) {
        return 'Expirado';
    }

    if (in_array($statusApi, ['canceled', 'cancelled', 'voided', 'refunded', 'reversed'], true)) {
        return 'Cancelado';
    }

    if (in_array($statusApi, ['denied', 'declined', 'failed', 'error', 'chargeback', 'disputed'], true)) {
        return 'Falha';
    }

    return 'Pendente';
}

function saas_pagbank_header($nome)
{
    $nome = trim((string) $nome);
    if ($nome === '') {
        return '';
    }

    $chaveServidor = 'HTTP_' . strtoupper(str_replace('-', '_', $nome));
    if (!empty($_SERVER[$chaveServidor])) {
        return trim((string) $_SERVER[$chaveServidor]);
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $headerNome => $valor) {
                if (strcasecmp((string) $headerNome, $nome) === 0) {
                    return trim((string) $valor);
                }
            }
        }
    }

    return '';
}

function saas_pagbank_validar_assinatura_webhook($payloadRaw, $assinaturaRecebida, $secret)
{
    $assinaturaRecebida = trim((string) $assinaturaRecebida);
    $secret = trim((string) $secret);
    if ($assinaturaRecebida === '' || $secret === '') {
        return false;
    }

    $esperado = hash_hmac('sha256', (string) $payloadRaw, $secret);
    $candidatos = [
        $assinaturaRecebida,
        strtolower($assinaturaRecebida),
    ];

    if (strpos($assinaturaRecebida, 'sha256=') === 0) {
        $candidatos[] = substr($assinaturaRecebida, 7);
    } else {
        $candidatos[] = 'sha256=' . $assinaturaRecebida;
    }

    foreach ($candidatos as $candidate) {
        $candidate = trim((string) $candidate);
        if ($candidate === '') {
            continue;
        }

        if (hash_equals($esperado, str_replace('sha256=', '', strtolower($candidate)))) {
            return true;
        }
    }

    return false;
}

function saas_pagbank_extrair_evento_id($payload)
{
    if (!is_array($payload)) {
        return '';
    }

    $candidatos = [
        $payload['event_id'] ?? '',
        $payload['notification_id'] ?? '',
        $payload['id'] ?? '',
        $payload['data']['id'] ?? '',
        $payload['event']['id'] ?? '',
    ];

    foreach ($candidatos as $valor) {
        $valor = trim((string) $valor);
        if ($valor !== '') {
            return $valor;
        }
    }

    return '';
}

function saas_pagbank_registrar_evento_billing($pdoSaas, $empresaId, $tipo, $recurso = 'pagamento', $detalhe = '')
{
    if (!$pdoSaas instanceof PDO || (int) $empresaId <= 0 || trim((string) $tipo) === '') {
        return false;
    }

    try {
        $query = $pdoSaas->prepare("INSERT INTO empresas_eventos_billing (empresa_id, tipo, recurso, detalhe)
            VALUES (:empresa_id, :tipo, :recurso, :detalhe)");
        $query->bindValue(':empresa_id', (int) $empresaId, PDO::PARAM_INT);
        $query->bindValue(':tipo', (string) $tipo);
        $query->bindValue(':recurso', $recurso !== '' ? (string) $recurso : null, $recurso !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $query->bindValue(':detalhe', $detalhe !== '' ? (string) $detalhe : null, $detalhe !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $query->execute();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function saas_pagbank_notificar_email_empresa($pdoSaas, $empresaId, $assunto, $mensagem)
{
    if (!$pdoSaas instanceof PDO || (int) $empresaId <= 0) {
        return false;
    }

    $config = saas_pagbank_config();
    $from = trim((string) ($config['notify_email_from'] ?? ''));
    if ($from === '') {
        return false;
    }

    try {
        $query = $pdoSaas->prepare("SELECT email FROM empresas_usuarios WHERE empresa_id = :empresa_id AND ativo = 'Sim' ORDER BY id ASC LIMIT 1");
        $query->bindValue(':empresa_id', (int) $empresaId, PDO::PARAM_INT);
        $query->execute();
        $usuario = $query->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }

    $destinatario = isset($usuario['email']) ? trim((string) $usuario['email']) : '';
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $from,
    ];

    $replyTo = trim((string) ($config['notify_email_reply_to'] ?? ''));
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    return (bool) @mail($destinatario, (string) $assunto, (string) $mensagem, implode("\r\n", $headers));
}

function saas_pagbank_incrementar_tentativa_consulta($pdoSaas, $pagamentoId)
{
    if (!$pdoSaas instanceof PDO || (int) $pagamentoId <= 0) {
        return;
    }

    try {
        $update = $pdoSaas->prepare("UPDATE empresas_pagamentos SET tentativas_consulta = tentativas_consulta + 1 WHERE id = :id");
        $update->bindValue(':id', (int) $pagamentoId, PDO::PARAM_INT);
        $update->execute();
    } catch (Exception $e) {
        // ignorar
    }
}

function saas_pagbank_consultar_order($config, $authHeader, $orderId)
{
    $orderId = trim((string) $orderId);
    if ($orderId === '' || !is_array($config) || trim((string) $authHeader) === '') {
        return [
            'ok' => false,
            'message' => 'Dados insuficientes para consulta de pedido.',
        ];
    }

    $api = saas_pagbank_api_base((string) ($config['modo'] ?? 'sandbox'));

    return saas_pagbank_request(
        'GET',
        $api . '/orders/' . rawurlencode($orderId),
        [
            'Authorization: ' . $authHeader,
            'Accept: application/json',
        ],
        null,
        30
    );
}

function saas_pagbank_cancelar_order($config, $authHeader, $orderId)
{
    $orderId = trim((string) $orderId);
    if ($orderId === '' || !is_array($config) || trim((string) $authHeader) === '') {
        return [
            'ok' => false,
            'message' => 'Dados insuficientes para cancelamento.',
        ];
    }

    $api = saas_pagbank_api_base((string) ($config['modo'] ?? 'sandbox'));
    $endpoints = [
        '/orders/' . rawurlencode($orderId) . '/cancel',
        '/orders/' . rawurlencode($orderId) . '/void',
    ];

    foreach ($endpoints as $endpoint) {
        $resposta = saas_pagbank_request(
            'POST',
            $api . $endpoint,
            [
                'Authorization: ' . $authHeader,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            json_encode(new stdClass()),
            30
        );

        if ($resposta['ok']) {
            return $resposta;
        }
    }

    return [
        'ok' => false,
        'message' => 'PagBank nao confirmou cancelamento automatico.',
    ];
}

function saas_pagbank_solicitar_estorno_order($config, $authHeader, $orderId, $valorCentavos = null)
{
    $orderId = trim((string) $orderId);
    if ($orderId === '' || !is_array($config) || trim((string) $authHeader) === '') {
        return [
            'ok' => false,
            'message' => 'Dados insuficientes para estorno.',
        ];
    }

    $api = saas_pagbank_api_base((string) ($config['modo'] ?? 'sandbox'));
    $payload = [];
    if ($valorCentavos !== null) {
        $valorCentavos = (int) $valorCentavos;
        if ($valorCentavos > 0) {
            $payload['amount'] = [
                'value' => $valorCentavos,
            ];
        }
    }

    $endpoints = [
        '/orders/' . rawurlencode($orderId) . '/refunds',
        '/orders/' . rawurlencode($orderId) . '/refund',
    ];

    foreach ($endpoints as $endpoint) {
        $resposta = saas_pagbank_request(
            'POST',
            $api . $endpoint,
            [
                'Authorization: ' . $authHeader,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            30
        );

        if ($resposta['ok']) {
            return $resposta;
        }
    }

    return [
        'ok' => false,
        'message' => 'PagBank nao confirmou estorno automatico.',
    ];
}

function saas_pagbank_request($metodo, $url, $headers = [], $payload = null, $timeout = 30)
{
    $curl = curl_init($url);

    $opcoes = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper((string) $metodo),
        CURLOPT_TIMEOUT => (int) $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
    ];

    if (!empty($headers)) {
        $opcoes[CURLOPT_HTTPHEADER] = $headers;
    }

    if ($payload !== null) {
        $opcoes[CURLOPT_POSTFIELDS] = $payload;
    }

    curl_setopt_array($curl, $opcoes);

    $respostaBruta = curl_exec($curl);
    $erro = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $corpo = null;
    if (is_string($respostaBruta) && $respostaBruta !== '') {
        $json = json_decode($respostaBruta, true);
        $corpo = json_last_error() === JSON_ERROR_NONE ? $json : $respostaBruta;
    }

    return [
        'ok' => $erro === '' && $status >= 200 && $status < 300,
        'status' => $status,
        'body' => $corpo,
        'raw' => $respostaBruta,
        'error' => $erro,
    ];
}

function saas_pagbank_obter_auth_header($config = null)
{
    if ($config === null) {
        $config = saas_pagbank_config();
    }

    $tokenDireto = trim((string) ($config['token'] ?? ''));
    if ($tokenDireto !== '') {
        return [
            'ok' => true,
            'header' => $tokenDireto,
            'type' => 'token',
        ];
    }

    $clientId = trim((string) ($config['client_id'] ?? ''));
    $clientSecret = trim((string) ($config['client_secret'] ?? ''));
    if ($clientId === '' || $clientSecret === '') {
        return [
            'ok' => false,
            'message' => 'Credenciais do PagBank nao configuradas no ambiente.',
        ];
    }

    $url = saas_pagbank_api_base((string) $config['modo']) . '/oauth2/token';
    $payload = http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ]);

    $resposta = saas_pagbank_request(
        'POST',
        $url,
        [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ],
        $payload,
        25
    );

    if (!$resposta['ok'] || !is_array($resposta['body']) || empty($resposta['body']['access_token'])) {
        return [
            'ok' => false,
            'message' => 'Falha ao autenticar no PagBank.',
            'details' => $resposta,
        ];
    }

    return [
        'ok' => true,
        'header' => 'Bearer ' . $resposta['body']['access_token'],
        'type' => 'bearer',
    ];
}

function saas_pagbank_status_pago($status)
{
    $status = strtolower(trim((string) $status));
    return in_array($status, ['paid', 'approved', 'succeeded', 'completed', 'captured', 'pago'], true);
}

function saas_pagbank_status_cancelado_ou_expirado($status)
{
    $status = strtolower(trim((string) $status));
    return in_array($status, ['canceled', 'cancelled', 'expired', 'voided', 'denied', 'refunded', 'chargeback', 'disputed'], true);
}

function saas_pagbank_extrair_status_ordem($payload)
{
    if (!is_array($payload)) {
        return '';
    }

    $candidatos = [
        $payload['status'] ?? '',
        $payload['order']['status'] ?? '',
        $payload['data']['status'] ?? '',
    ];

    if (!empty($payload['charges']) && is_array($payload['charges'])) {
        foreach ($payload['charges'] as $charge) {
            if (!is_array($charge)) {
                continue;
            }

            $candidatos[] = $charge['status'] ?? '';
            if (!empty($charge['last_transaction']) && is_array($charge['last_transaction'])) {
                $candidatos[] = $charge['last_transaction']['status'] ?? '';
            }
        }
    }

    foreach ($candidatos as $valor) {
        $valor = strtolower(trim((string) $valor));
        if ($valor !== '') {
            return $valor;
        }
    }

    return '';
}

function saas_pagbank_renovar_assinatura($pdoSaas, $empresaId, $dias = 30)
{
    $empresaId = (int) $empresaId;
    $dias = max(1, (int) $dias);
    if (!$pdoSaas instanceof PDO || $empresaId <= 0) {
        return [
            'ok' => false,
            'message' => 'Empresa invalida para renovacao.',
        ];
    }

    $query = $pdoSaas->prepare("SELECT id, status, trial_ate, ciclo_ate FROM empresas_assinaturas WHERE empresa_id = :empresa_id LIMIT 1");
    $query->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
    $query->execute();
    $assinatura = $query->fetch(PDO::FETCH_ASSOC);

    if (!$assinatura) {
        saas_garantir_assinatura_empresa($pdoSaas, $empresaId, 'starter', 'Ativa', 0);
        $query->execute();
        $assinatura = $query->fetch(PDO::FETCH_ASSOC);
    }

    if (!$assinatura) {
        return [
            'ok' => false,
            'message' => 'Nao foi possivel localizar a assinatura da empresa.',
        ];
    }

    $hoje = date('Y-m-d');
    $baseData = $hoje;

    if (!empty($assinatura['ciclo_ate']) && $assinatura['ciclo_ate'] >= $baseData) {
        $baseData = $assinatura['ciclo_ate'];
    }

    if ($assinatura['status'] === 'Trial' && !empty($assinatura['trial_ate']) && $assinatura['trial_ate'] >= $baseData) {
        $baseData = $assinatura['trial_ate'];
    }

    $novoCiclo = date('Y-m-d', strtotime($baseData . ' +' . $dias . ' days'));

    $update = $pdoSaas->prepare("UPDATE empresas_assinaturas
        SET status = 'Ativa',
            trial_ate = NULL,
            ciclo_ate = :ciclo_ate,
            suspensa_em = NULL,
            observacoes = :observacoes,
            ultimo_pagamento_em = NOW()
        WHERE empresa_id = :empresa_id");
    $update->bindValue(':ciclo_ate', $novoCiclo);
    $update->bindValue(':observacoes', 'Renovada automaticamente por pagamento confirmado no PagBank');
    $update->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
    $update->execute();

    $evento = $pdoSaas->prepare("INSERT INTO empresas_eventos_billing (empresa_id, tipo, recurso, detalhe)
        VALUES (:empresa_id, 'pagamento_confirmado', 'assinatura', :detalhe)");
    $evento->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
    $evento->bindValue(':detalhe', 'Assinatura renovada ate ' . $novoCiclo);
    $evento->execute();

    return [
        'ok' => true,
        'ciclo_ate' => $novoCiclo,
    ];
}

function saas_pagbank_marcar_pagamento_pago($pdoSaas, $pagamentoId, $payload = [], $origem = 'webhook')
{
    $pagamentoId = (int) $pagamentoId;
    if (!$pdoSaas instanceof PDO || $pagamentoId <= 0) {
        return [
            'ok' => false,
            'message' => 'Pagamento invalido para confirmacao.',
        ];
    }

    saas_pagbank_garantir_estrutura($pdoSaas);

    $payloadJson = null;
    if (!empty($payload)) {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    try {
        $pdoSaas->beginTransaction();

        $queryPagamento = $pdoSaas->prepare("SELECT id, empresa_id, status, valor, pedido_referencia FROM empresas_pagamentos WHERE id = :id LIMIT 1 FOR UPDATE");
        $queryPagamento->bindValue(':id', $pagamentoId, PDO::PARAM_INT);
        $queryPagamento->execute();
        $pagamento = $queryPagamento->fetch(PDO::FETCH_ASSOC);

        if (!$pagamento) {
            $pdoSaas->rollBack();
            return [
                'ok' => false,
                'message' => 'Pagamento nao encontrado.',
            ];
        }

        if ($pagamento['status'] === 'Pago') {
            $queryAssinatura = $pdoSaas->prepare("SELECT ciclo_ate FROM empresas_assinaturas WHERE empresa_id = :empresa_id LIMIT 1");
            $queryAssinatura->bindValue(':empresa_id', (int) $pagamento['empresa_id'], PDO::PARAM_INT);
            $queryAssinatura->execute();
            $assinaturaAtual = $queryAssinatura->fetch(PDO::FETCH_ASSOC);
            $pdoSaas->commit();

            return [
                'ok' => true,
                'empresa_id' => (int) $pagamento['empresa_id'],
                'ciclo_ate' => isset($assinaturaAtual['ciclo_ate']) ? (string) $assinaturaAtual['ciclo_ate'] : null,
                'idempotente' => true,
            ];
        }

        $statusDetalhe = saas_pagbank_extrair_status_ordem($payload);
        $update = $pdoSaas->prepare("UPDATE empresas_pagamentos
            SET status = 'Pago',
                status_detalhe = :status_detalhe,
                pago_em = NOW(),
                cancelado_em = NULL,
                cancel_reason = NULL,
                payload_status = CASE WHEN :origem = 'consulta_status' THEN :payload_status ELSE payload_status END,
                payload_webhook = CASE WHEN :origem <> 'consulta_status' THEN :payload_webhook ELSE payload_webhook END
            WHERE id = :id");
        $update->bindValue(':origem', (string) $origem);
        $update->bindValue(':status_detalhe', $statusDetalhe !== '' ? $statusDetalhe : null, $statusDetalhe !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        if ($payloadJson === null) {
            $update->bindValue(':payload_status', null, PDO::PARAM_NULL);
            $update->bindValue(':payload_webhook', null, PDO::PARAM_NULL);
        } else {
            $update->bindValue(':payload_status', $payloadJson);
            $update->bindValue(':payload_webhook', $payloadJson);
        }
        $update->bindValue(':id', $pagamentoId, PDO::PARAM_INT);
        $update->execute();

        $renovacao = saas_pagbank_renovar_assinatura(
            $pdoSaas,
            (int) $pagamento['empresa_id'],
            (int) (saas_pagbank_config()['dias_renovacao'] ?? 30)
        );

        if (!$renovacao['ok']) {
            $pdoSaas->rollBack();
            return $renovacao;
        }

        $pdoSaas->commit();

        $valor = number_format((float) ($pagamento['valor'] ?? 0), 2, ',', '.');
        $referencia = (string) ($pagamento['pedido_referencia'] ?? '');
        saas_pagbank_registrar_evento_billing(
            $pdoSaas,
            (int) $pagamento['empresa_id'],
            'pagamento_confirmado',
            'pagamento',
            'Pagamento confirmado no valor de R$ ' . $valor . ' (ref. ' . $referencia . ')'
        );

        saas_pagbank_notificar_email_empresa(
            $pdoSaas,
            (int) $pagamento['empresa_id'],
            'Pagamento confirmado - Barbearia SaaS',
            "Seu pagamento foi confirmado com sucesso.\n\nReferencia: " . $referencia . "\nValor: R$ " . $valor . "\nValidade da assinatura: " . (string) $renovacao['ciclo_ate']
        );

        return [
            'ok' => true,
            'empresa_id' => (int) $pagamento['empresa_id'],
            'ciclo_ate' => $renovacao['ciclo_ate'],
            'idempotente' => false,
        ];
    } catch (Exception $e) {
        if ($pdoSaas->inTransaction()) {
            $pdoSaas->rollBack();
        }

        return [
            'ok' => false,
            'message' => 'Falha ao confirmar pagamento: ' . $e->getMessage(),
        ];
    }
}

function saas_pagbank_atualizar_status_pagamento($pdoSaas, $pagamentoId, $statusLocal, $statusDetalhe = '', $payload = [], $origem = 'consulta_status', $cancelReason = '')
{
    if (!$pdoSaas instanceof PDO || (int) $pagamentoId <= 0) {
        return [
            'ok' => false,
            'message' => 'Pagamento invalido para atualizar status.',
        ];
    }

    $statusLocal = trim((string) $statusLocal);
    $statusValidos = ['Pendente', 'Pago', 'Cancelado', 'Expirado', 'Falha'];
    if (!in_array($statusLocal, $statusValidos, true)) {
        $statusLocal = 'Pendente';
    }

    if ($statusLocal === 'Pago') {
        return saas_pagbank_marcar_pagamento_pago($pdoSaas, (int) $pagamentoId, $payload, $origem);
    }

    $payloadJson = null;
    if (!empty($payload)) {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    try {
        $query = $pdoSaas->prepare("SELECT empresa_id, status FROM empresas_pagamentos WHERE id = :id LIMIT 1");
        $query->bindValue(':id', (int) $pagamentoId, PDO::PARAM_INT);
        $query->execute();
        $pagamento = $query->fetch(PDO::FETCH_ASSOC);
        if (!$pagamento) {
            return [
                'ok' => false,
                'message' => 'Pagamento nao encontrado para atualizar status.',
            ];
        }

        if ((string) $pagamento['status'] === 'Pago') {
            return [
                'ok' => true,
                'status' => 'Pago',
                'inalterado' => true,
            ];
        }

        $deveCancelar = in_array($statusLocal, ['Cancelado', 'Expirado', 'Falha'], true);

        $update = $pdoSaas->prepare("UPDATE empresas_pagamentos
            SET status = :status,
                status_detalhe = :status_detalhe,
                cancelado_em = CASE WHEN :deve_cancelar = 1 THEN NOW() ELSE cancelado_em END,
                cancel_reason = CASE WHEN :deve_cancelar = 1 THEN :cancel_reason ELSE cancel_reason END,
                payload_status = CASE WHEN :origem = 'consulta_status' THEN :payload_status ELSE payload_status END,
                payload_webhook = CASE WHEN :origem <> 'consulta_status' THEN :payload_webhook ELSE payload_webhook END
            WHERE id = :id AND status <> 'Pago'");
        $update->bindValue(':status', $statusLocal);
        $update->bindValue(':status_detalhe', $statusDetalhe !== '' ? $statusDetalhe : null, $statusDetalhe !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $update->bindValue(':deve_cancelar', $deveCancelar ? 1 : 0, PDO::PARAM_INT);
        $update->bindValue(':cancel_reason', $cancelReason !== '' ? $cancelReason : null, $cancelReason !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $update->bindValue(':origem', (string) $origem);
        if ($payloadJson === null) {
            $update->bindValue(':payload_status', null, PDO::PARAM_NULL);
            $update->bindValue(':payload_webhook', null, PDO::PARAM_NULL);
        } else {
            $update->bindValue(':payload_status', $payloadJson);
            $update->bindValue(':payload_webhook', $payloadJson);
        }
        $update->bindValue(':id', (int) $pagamentoId, PDO::PARAM_INT);
        $update->execute();

        if ($deveCancelar) {
            saas_pagbank_registrar_evento_billing(
                $pdoSaas,
                (int) $pagamento['empresa_id'],
                'pagamento_' . strtolower($statusLocal),
                'pagamento',
                'Pagamento #' . (int) $pagamentoId . ' atualizado para ' . $statusLocal
            );
        }

        return [
            'ok' => true,
            'status' => $statusLocal,
        ];
    } catch (Exception $e) {
        return [
            'ok' => false,
            'message' => 'Falha ao atualizar status do pagamento: ' . $e->getMessage(),
        ];
    }
}
