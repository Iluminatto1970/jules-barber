<?php
if (!defined('SAAS_ADMIN_APP')) {
    exit;
}

function empresas_badge($ativo, $assinatura)
{
    if ($ativo !== 'Sim') {
        return ['status-danger', 'Inativa'];
    }

    if ($assinatura === 'Ativa') {
        return ['status-success', 'Ativa'];
    }

    if ($assinatura === 'Trial') {
        return ['status-warning', 'Trial'];
    }

    if ($assinatura === 'Suspensa') {
        return ['status-danger', 'Suspensa'];
    }

    if ($assinatura === 'Cancelada') {
        return ['status-muted', 'Cancelada'];
    }

    return ['status-muted', $ativo === 'Sim' ? 'Sem assinatura' : 'Inativa'];
}

function empresas_form_bool($campo)
{
    return isset($_POST[$campo]) && (string) $_POST[$campo] === '1';
}

function empresas_normalizar_data($valor, $campo)
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return null;
    }

    $timestamp = strtotime($valor);
    if ($timestamp === false) {
        throw new Exception('Data invalida no campo: ' . $campo);
    }

    return date('Y-m-d', $timestamp);
}

function empresas_input_data($valor)
{
    if (!$valor) {
        return '';
    }

    $timestamp = strtotime((string) $valor);
    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d', $timestamp);
}

$defaultDbHost = isset($saas_servidor) ? $saas_servidor : '127.0.0.1';
$defaultDbUser = isset($saas_usuario) ? $saas_usuario : 'root';
$defaultDbPass = isset($saas_senha) ? $saas_senha : '';
$defaultServiceUrl = 'http://127.0.0.1:8000';

$planos = [];
try {
    $planos = $pdo_saas->query("SELECT id, nome, slug, trial_dias, ativo FROM planos ORDER BY ativo DESC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    admin_set_flash('warning', 'Planos nao puderam ser carregados.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirectParams = [];

    if (!empty($_POST['return_q'])) {
        $redirectParams['q'] = trim((string) $_POST['return_q']);
    }

    if (!empty($_POST['return_id'])) {
        $redirectParams['id'] = (int) $_POST['return_id'];
    }

    try {
        admin_csrf_validate();
        $action = admin_request_action();

        if ($action === 'create_empresa') {
            $nome = trim((string) ($_POST['nome'] ?? ''));
            $slugInput = trim((string) ($_POST['slug'] ?? ''));
            $slug = saas_normalizar_slug($slugInput !== '' ? $slugInput : $nome);

            if ($nome === '') {
                throw new Exception('Informe o nome da empresa.');
            }

            $query = $pdo_saas->prepare("SELECT id FROM empresas WHERE slug = :slug LIMIT 1");
            $query->bindValue(':slug', $slug);
            $query->execute();
            if ($query->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception('Ja existe uma empresa com esse slug.');
            }

            $bancoInput = trim((string) ($_POST['banco'] ?? ''));
            $banco = $bancoInput !== ''
                ? saas_validar_identificador($bancoInput, 'Banco tenant')
                : saas_nome_banco_disponivel($pdo_saas, $slug);

            $dbHost = trim((string) ($_POST['db_host'] ?? $defaultDbHost));
            $dbUsuario = trim((string) ($_POST['db_usuario'] ?? $defaultDbUser));
            $dbSenha = (string) ($_POST['db_senha'] ?? '');
            if ($dbSenha === '') {
                $dbSenha = (string) $defaultDbPass;
            }

            if ($dbHost === '' || $dbUsuario === '' || $dbSenha === '') {
                throw new Exception('Informe host, usuario e senha do banco tenant.');
            }

            $dominioPrincipal = trim((string) ($_POST['dominio_principal'] ?? ''));
            if ($dominioPrincipal === '') {
                throw new Exception('Informe o dominio principal da empresa.');
            }

            $dominios = [$dominioPrincipal];
            $extrasRaw = trim((string) ($_POST['dominios_extras'] ?? ''));
            if ($extrasRaw !== '') {
                $extras = explode(',', $extrasRaw);
                foreach ($extras as $extra) {
                    $extra = trim($extra);
                    if ($extra !== '') {
                        $dominios[] = $extra;
                    }
                }
            }

            $dominiosNormalizados = [];
            foreach ($dominios as $dominio) {
                $dominioNormalizado = saas_normalizar_dominio($dominio);
                if (!in_array($dominioNormalizado, $dominiosNormalizados, true)) {
                    $dominiosNormalizados[] = $dominioNormalizado;
                }
            }

            $placeholders = implode(',', array_fill(0, count($dominiosNormalizados), '?'));
            $check = $pdo_saas->prepare("SELECT dominio FROM empresas_dominios WHERE dominio IN ($placeholders) LIMIT 1");
            $check->execute($dominiosNormalizados);
            $duplicado = $check->fetch(PDO::FETCH_ASSOC);
            if ($duplicado) {
                throw new Exception('Dominio ja cadastrado em outra empresa: ' . $duplicado['dominio']);
            }

            $ativo = (isset($_POST['ativo']) && $_POST['ativo'] === 'Nao') ? 'Nao' : 'Sim';
            $planoSlug = trim((string) ($_POST['plano_slug'] ?? 'starter'));
            if ($planoSlug === '') {
                $planoSlug = 'starter';
            }

            $statusAssinatura = trim((string) ($_POST['status_assinatura'] ?? 'Trial'));
            $statusValidos = ['Trial', 'Ativa', 'Suspensa', 'Cancelada'];
            if (!in_array($statusAssinatura, $statusValidos, true)) {
                $statusAssinatura = 'Trial';
            }

            $trialDias = isset($_POST['trial_dias']) && $_POST['trial_dias'] !== '' ? max(0, (int) $_POST['trial_dias']) : null;
            $cicloAte = empresas_normalizar_data($_POST['ciclo_ate'] ?? '', 'Expiracao do plano');

            $provisionarBanco = empresas_form_bool('provisionar_banco');
            $provisionarAdmin = empresas_form_bool('provisionar_admin');
            $provisionarTunnel = empresas_form_bool('provisionar_tunnel');

            $responsavelNome = trim((string) ($_POST['responsavel_nome'] ?? ''));
            $adminEmail = saas_normalizar_email((string) ($_POST['admin_email'] ?? ''));
            $adminSenha = (string) ($_POST['admin_senha'] ?? '');

            if ($provisionarAdmin) {
                if ($responsavelNome === '' || $adminEmail === '' || $adminSenha === '') {
                    throw new Exception('Informe nome, email e senha do administrador para provisionamento automatico.');
                }

                if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Email do administrador invalido.');
                }

                if (strlen($adminSenha) < 6) {
                    throw new Exception('A senha do administrador deve ter no minimo 6 caracteres.');
                }

                if (saas_email_ja_cadastrado($pdo_saas, $adminEmail)) {
                    throw new Exception('Este email ja esta vinculado a outro usuario SaaS.');
                }
            }

            $serviceUrl = trim((string) ($_POST['service_url'] ?? $defaultServiceUrl));
            if ($serviceUrl === '') {
                $serviceUrl = $defaultServiceUrl;
            }

            $tunnelNomeInput = trim((string) ($_POST['tunnel_nome'] ?? ''));
            $tunnelNome = saas_normalizar_tunnel_nome($tunnelNomeInput !== '' ? $tunnelNomeInput : 'tenant-' . $slug);

            if ($provisionarBanco) {
                $pdoServer = saas_abrir_servidor($dbHost, $dbUsuario, $dbSenha);
                saas_criar_banco_tenant_modelo($pdoServer, $dbHost, $dbUsuario, $dbSenha, $banco);
            }

            $empresaId = saas_registrar_empresa($pdo_saas, [
                'nome' => $nome,
                'slug' => $slug,
                'banco' => $banco,
                'db_host' => $dbHost,
                'db_usuario' => $dbUsuario,
                'db_senha' => $dbSenha,
                'ativo' => $ativo,
            ], $dominiosNormalizados);

            saas_garantir_assinatura_empresa($pdo_saas, $empresaId, $planoSlug, $statusAssinatura, $trialDias);

            $queryAssinaturaDatas = $pdo_saas->prepare("UPDATE empresas_assinaturas SET ciclo_ate = :ciclo_ate WHERE empresa_id = :empresa_id");
            if ($cicloAte === null) {
                $queryAssinaturaDatas->bindValue(':ciclo_ate', null, PDO::PARAM_NULL);
            } else {
                $queryAssinaturaDatas->bindValue(':ciclo_ate', $cicloAte);
            }
            $queryAssinaturaDatas->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $queryAssinaturaDatas->execute();

            $dominioPrincipalNormalizado = $dominiosNormalizados[0];
            $painelUrl = (saas_dominio_publico($dominioPrincipalNormalizado) ? 'https://' : 'http://')
                . $dominioPrincipalNormalizado
                . '/sistema/';

            $resumoProvisionamento = [];
            $avisosProvisionamento = [];

            if ($provisionarBanco) {
                $resumoProvisionamento[] = 'banco tenant provisionado';
            }

            if ($provisionarAdmin) {
                try {
                    saas_registrar_usuario_empresa($pdo_saas, $empresaId, $responsavelNome, $adminEmail, $adminSenha);
                    saas_configurar_tenant_admin($dbHost, $banco, $dbUsuario, $dbSenha, $nome, $responsavelNome, $adminEmail, $adminSenha);
                    $resumoProvisionamento[] = 'admin configurado';
                } catch (Exception $e) {
                    $avisosProvisionamento[] = 'Empresa criada, mas houve falha ao configurar admin: ' . $e->getMessage();
                }
            }

            if ($provisionarTunnel) {
                try {
                    if (!saas_cloudflared_disponivel()) {
                        throw new Exception('cloudflared nao encontrado no servidor.');
                    }

                    $tunnel = saas_criar_tunnel($tunnelNome);
                    $tunnelId = $tunnel['id'];

                    $dominiosDnsCriados = [];
                    $dominiosDnsIgnorados = [];
                    foreach ($dominiosNormalizados as $dominio) {
                        if (!saas_dominio_publico($dominio)) {
                            $dominiosDnsIgnorados[] = $dominio;
                            continue;
                        }

                        saas_criar_dns_tunnel($tunnelId, $dominio);
                        $dominiosDnsCriados[] = $dominio;
                    }

                    saas_registrar_tunnel_empresa($pdo_saas, $empresaId, $tunnelNome, $tunnelId, $dominioPrincipalNormalizado, $serviceUrl);
                    $configPath = saas_salvar_config_tunnel($slug, $tunnelId, $dominiosNormalizados, $serviceUrl);
                    saas_iniciar_tunnel_background($slug, $tunnelId, $configPath);

                    $resumoProvisionamento[] = 'tunnel iniciado';

                    if (!empty($dominiosDnsCriados)) {
                        $avisosProvisionamento[] = 'DNS configurado para: ' . implode(', ', $dominiosDnsCriados);
                    }

                    if (!empty($dominiosDnsIgnorados)) {
                        $avisosProvisionamento[] = 'Dominios locais sem DNS automatico: ' . implode(', ', $dominiosDnsIgnorados);
                    }
                } catch (Exception $e) {
                    $avisosProvisionamento[] = 'Empresa criada, mas houve falha no tunnel/dns: ' . $e->getMessage();
                }
            }

            $mensagemSucesso = 'Empresa criada com sucesso. Acesso sugerido: ' . $painelUrl;
            if (!empty($resumoProvisionamento)) {
                $mensagemSucesso .= ' (' . implode(' | ', $resumoProvisionamento) . ')';
            }

            if ($cicloAte !== null) {
                $mensagemSucesso .= ' | Expira em: ' . date('d/m/Y', strtotime($cicloAte));
            }

            admin_set_flash('success', $mensagemSucesso);
            foreach ($avisosProvisionamento as $aviso) {
                admin_set_flash('warning', $aviso);
            }

            $redirectParams['id'] = $empresaId;
        }

        if ($action === 'update_empresa') {
            $empresaId = (int) ($_POST['empresa_id'] ?? 0);
            if ($empresaId <= 0) {
                throw new Exception('Empresa invalida para edicao.');
            }

            $nome = trim((string) ($_POST['nome'] ?? ''));
            $slug = saas_normalizar_slug((string) ($_POST['slug'] ?? ''));
            $banco = saas_validar_identificador((string) ($_POST['banco'] ?? ''), 'Banco tenant');
            $dbHost = trim((string) ($_POST['db_host'] ?? ''));
            $dbUsuario = trim((string) ($_POST['db_usuario'] ?? ''));
            $dbSenha = (string) ($_POST['db_senha'] ?? '');
            $ativo = (isset($_POST['ativo']) && $_POST['ativo'] === 'Nao') ? 'Nao' : 'Sim';

            if ($nome === '' || $dbHost === '' || $dbUsuario === '') {
                throw new Exception('Preencha os campos obrigatorios da empresa.');
            }

            $checkSlug = $pdo_saas->prepare("SELECT id FROM empresas WHERE slug = :slug AND id <> :id LIMIT 1");
            $checkSlug->bindValue(':slug', $slug);
            $checkSlug->bindValue(':id', $empresaId, PDO::PARAM_INT);
            $checkSlug->execute();
            if ($checkSlug->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception('Slug ja utilizado por outra empresa.');
            }

            $sql = "UPDATE empresas SET
                nome = :nome,
                slug = :slug,
                banco = :banco,
                db_host = :db_host,
                db_usuario = :db_usuario,
                ativo = :ativo";

            if ($dbSenha !== '') {
                $sql .= ", db_senha = :db_senha";
            }

            $sql .= " WHERE id = :id";
            $query = $pdo_saas->prepare($sql);
            $query->bindValue(':nome', $nome);
            $query->bindValue(':slug', $slug);
            $query->bindValue(':banco', $banco);
            $query->bindValue(':db_host', $dbHost);
            $query->bindValue(':db_usuario', $dbUsuario);
            $query->bindValue(':ativo', $ativo);
            $query->bindValue(':id', $empresaId, PDO::PARAM_INT);
            if ($dbSenha !== '') {
                $query->bindValue(':db_senha', $dbSenha);
            }
            $query->execute();

            admin_set_flash('success', 'Empresa atualizada com sucesso.');
            $redirectParams['id'] = $empresaId;
        }

        if ($action === 'toggle_empresa') {
            $empresaId = (int) ($_POST['empresa_id'] ?? 0);
            if ($empresaId <= 0) {
                throw new Exception('Empresa invalida.');
            }

            $query = $pdo_saas->prepare("SELECT ativo FROM empresas WHERE id = :id LIMIT 1");
            $query->bindValue(':id', $empresaId, PDO::PARAM_INT);
            $query->execute();
            $empresa = $query->fetch(PDO::FETCH_ASSOC);

            if (!$empresa) {
                throw new Exception('Empresa nao encontrada.');
            }

            $novoStatus = $empresa['ativo'] === 'Sim' ? 'Nao' : 'Sim';
            $update = $pdo_saas->prepare("UPDATE empresas SET ativo = :status WHERE id = :id");
            $update->bindValue(':status', $novoStatus);
            $update->bindValue(':id', $empresaId, PDO::PARAM_INT);
            $update->execute();

            admin_set_flash('success', 'Status da empresa alterado para ' . $novoStatus . '.');
            $redirectParams['id'] = $empresaId;
        }

        if ($action === 'add_dominio') {
            $empresaId = (int) ($_POST['empresa_id'] ?? 0);
            $dominio = saas_normalizar_dominio((string) ($_POST['dominio'] ?? ''));
            $principal = isset($_POST['principal']) && (string) $_POST['principal'] === '1' ? 1 : 0;

            if ($empresaId <= 0) {
                throw new Exception('Empresa invalida para adicionar dominio.');
            }

            $check = $pdo_saas->prepare("SELECT empresa_id FROM empresas_dominios WHERE dominio = :dominio LIMIT 1");
            $check->bindValue(':dominio', $dominio);
            $check->execute();
            $dominioExistente = $check->fetch(PDO::FETCH_ASSOC);
            if ($dominioExistente && (int) $dominioExistente['empresa_id'] !== $empresaId) {
                throw new Exception('Dominio ja pertence a outra empresa.');
            }

            if ($principal === 1) {
                $reset = $pdo_saas->prepare("UPDATE empresas_dominios SET principal = 0 WHERE empresa_id = :empresa_id");
                $reset->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
                $reset->execute();
            }

            $insert = $pdo_saas->prepare("INSERT INTO empresas_dominios (empresa_id, dominio, principal)
                VALUES (:empresa_id, :dominio, :principal)
                ON DUPLICATE KEY UPDATE principal = VALUES(principal)");
            $insert->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $insert->bindValue(':dominio', $dominio);
            $insert->bindValue(':principal', $principal, PDO::PARAM_INT);
            $insert->execute();

            $checkPrincipal = $pdo_saas->prepare("SELECT id FROM empresas_dominios WHERE empresa_id = :empresa_id AND principal = 1 LIMIT 1");
            $checkPrincipal->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $checkPrincipal->execute();
            if (!$checkPrincipal->fetch(PDO::FETCH_ASSOC)) {
                $fixPrincipal = $pdo_saas->prepare("UPDATE empresas_dominios SET principal = 1 WHERE empresa_id = :empresa_id ORDER BY id ASC LIMIT 1");
                $fixPrincipal->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
                $fixPrincipal->execute();
            }

            admin_set_flash('success', 'Dominio vinculado com sucesso.');
            $redirectParams['id'] = $empresaId;
        }

        if ($action === 'set_principal_dominio') {
            $empresaId = (int) ($_POST['empresa_id'] ?? 0);
            $dominioId = (int) ($_POST['dominio_id'] ?? 0);
            if ($empresaId <= 0 || $dominioId <= 0) {
                throw new Exception('Dominio invalido para definir como principal.');
            }

            $reset = $pdo_saas->prepare("UPDATE empresas_dominios SET principal = 0 WHERE empresa_id = :empresa_id");
            $reset->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $reset->execute();

            $set = $pdo_saas->prepare("UPDATE empresas_dominios SET principal = 1 WHERE id = :id AND empresa_id = :empresa_id");
            $set->bindValue(':id', $dominioId, PDO::PARAM_INT);
            $set->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $set->execute();

            admin_set_flash('success', 'Dominio principal atualizado.');
            $redirectParams['id'] = $empresaId;
        }

        if ($action === 'remove_dominio') {
            $empresaId = (int) ($_POST['empresa_id'] ?? 0);
            $dominioId = (int) ($_POST['dominio_id'] ?? 0);
            if ($empresaId <= 0 || $dominioId <= 0) {
                throw new Exception('Dominio invalido para remocao.');
            }

            $totalDominios = $pdo_saas->prepare("SELECT COUNT(*) FROM empresas_dominios WHERE empresa_id = :empresa_id");
            $totalDominios->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $totalDominios->execute();

            if ((int) $totalDominios->fetchColumn() <= 1) {
                throw new Exception('A empresa precisa ter ao menos um dominio cadastrado.');
            }

            $delete = $pdo_saas->prepare("DELETE FROM empresas_dominios WHERE id = :id AND empresa_id = :empresa_id");
            $delete->bindValue(':id', $dominioId, PDO::PARAM_INT);
            $delete->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $delete->execute();

            $checkPrincipal = $pdo_saas->prepare("SELECT id FROM empresas_dominios WHERE empresa_id = :empresa_id AND principal = 1 LIMIT 1");
            $checkPrincipal->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $checkPrincipal->execute();
            if (!$checkPrincipal->fetch(PDO::FETCH_ASSOC)) {
                $fixPrincipal = $pdo_saas->prepare("UPDATE empresas_dominios SET principal = 1 WHERE empresa_id = :empresa_id ORDER BY id ASC LIMIT 1");
                $fixPrincipal->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
                $fixPrincipal->execute();
            }

            admin_set_flash('success', 'Dominio removido com sucesso.');
            $redirectParams['id'] = $empresaId;
        }

        if ($action === 'save_assinatura') {
            $empresaId = (int) ($_POST['empresa_id'] ?? 0);
            $planoId = (int) ($_POST['plano_id'] ?? 0);
            $status = trim((string) ($_POST['status_assinatura'] ?? 'Trial'));
            $trialDias = isset($_POST['trial_dias']) && $_POST['trial_dias'] !== '' ? max(0, (int) $_POST['trial_dias']) : null;
            $cicloAte = empresas_normalizar_data($_POST['ciclo_ate'] ?? '', 'Expiracao do plano');

            $statusValidos = ['Trial', 'Ativa', 'Suspensa', 'Cancelada'];
            if (!in_array($status, $statusValidos, true)) {
                throw new Exception('Status de assinatura invalido.');
            }

            if ($empresaId <= 0 || $planoId <= 0) {
                throw new Exception('Assinatura invalida para atualizacao.');
            }

            $planoQuery = $pdo_saas->prepare("SELECT slug FROM planos WHERE id = :id LIMIT 1");
            $planoQuery->bindValue(':id', $planoId, PDO::PARAM_INT);
            $planoQuery->execute();
            $plano = $planoQuery->fetch(PDO::FETCH_ASSOC);
            if (!$plano) {
                throw new Exception('Plano informado nao existe.');
            }

            saas_garantir_assinatura_empresa($pdo_saas, $empresaId, $plano['slug'], $status, $trialDias);

            $queryAssinaturaDatas = $pdo_saas->prepare("UPDATE empresas_assinaturas
                SET ciclo_ate = :ciclo_ate,
                    suspensa_em = CASE WHEN :status = 'Suspensa' THEN NOW() ELSE NULL END
                WHERE empresa_id = :empresa_id");
            if ($cicloAte === null) {
                $queryAssinaturaDatas->bindValue(':ciclo_ate', null, PDO::PARAM_NULL);
            } else {
                $queryAssinaturaDatas->bindValue(':ciclo_ate', $cicloAte);
            }
            $queryAssinaturaDatas->bindValue(':status', $status);
            $queryAssinaturaDatas->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $queryAssinaturaDatas->execute();

            admin_set_flash('success', 'Assinatura atualizada com sucesso.');
            $redirectParams['id'] = $empresaId;
        }
    } catch (Exception $e) {
        admin_set_flash('danger', $e->getMessage());
    }

    admin_redirect('empresas', $redirectParams);
}

$search = trim((string) ($_GET['q'] ?? ''));
$selectedEmpresaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$empresas = [];
$selectedEmpresa = null;
$selectedDominios = [];
$selectedUsuarios = [];
$selectedTuneis = [];

try {
    $sql = "SELECT
            e.id,
            e.nome,
            e.slug,
            e.banco,
            e.ativo,
            e.criado_em,
            d.dominio AS dominio_principal,
            a.status AS assinatura_status,
            a.trial_ate,
            a.ciclo_ate,
            p.nome AS plano_nome
        FROM empresas e
        LEFT JOIN empresas_dominios d ON d.empresa_id = e.id AND d.principal = 1
        LEFT JOIN empresas_assinaturas a ON a.empresa_id = e.id
        LEFT JOIN planos p ON p.id = a.plano_id
        WHERE 1 = 1";

    $params = [];
    if ($search !== '') {
        $sql .= " AND (
            e.nome LIKE :termo
            OR e.slug LIKE :termo
            OR e.banco LIKE :termo
            OR d.dominio LIKE :termo
        )";
        $params[':termo'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY e.id DESC LIMIT 220";

    $query = $pdo_saas->prepare($sql);
    foreach ($params as $key => $value) {
        $query->bindValue($key, $value);
    }
    $query->execute();
    $empresas = $query->fetchAll(PDO::FETCH_ASSOC);

    if ($selectedEmpresaId > 0) {
        $queryEmpresa = $pdo_saas->prepare("SELECT
                e.*,
                d.dominio AS dominio_principal,
                a.plano_id,
                a.status AS assinatura_status,
                a.trial_ate,
                a.ciclo_ate,
                a.inicio_em,
                p.nome AS plano_nome
            FROM empresas e
            LEFT JOIN empresas_dominios d ON d.empresa_id = e.id AND d.principal = 1
            LEFT JOIN empresas_assinaturas a ON a.empresa_id = e.id
            LEFT JOIN planos p ON p.id = a.plano_id
            WHERE e.id = :id
            LIMIT 1");
        $queryEmpresa->bindValue(':id', $selectedEmpresaId, PDO::PARAM_INT);
        $queryEmpresa->execute();
        $selectedEmpresa = $queryEmpresa->fetch(PDO::FETCH_ASSOC);

        if ($selectedEmpresa) {
            $queryDominios = $pdo_saas->prepare("SELECT id, dominio, principal, criado_em FROM empresas_dominios WHERE empresa_id = :empresa_id ORDER BY principal DESC, id ASC");
            $queryDominios->bindValue(':empresa_id', $selectedEmpresaId, PDO::PARAM_INT);
            $queryDominios->execute();
            $selectedDominios = $queryDominios->fetchAll(PDO::FETCH_ASSOC);

            $queryUsuarios = $pdo_saas->prepare("SELECT id, nome, email, ativo, criado_em FROM empresas_usuarios WHERE empresa_id = :empresa_id ORDER BY id DESC LIMIT 25");
            $queryUsuarios->bindValue(':empresa_id', $selectedEmpresaId, PDO::PARAM_INT);
            $queryUsuarios->execute();
            $selectedUsuarios = $queryUsuarios->fetchAll(PDO::FETCH_ASSOC);

            $queryTuneis = $pdo_saas->prepare("SELECT id, tunnel_nome, tunnel_id, dominio, service_url, status, criado_em FROM empresas_tunnels WHERE empresa_id = :empresa_id ORDER BY id DESC");
            $queryTuneis->bindValue(':empresa_id', $selectedEmpresaId, PDO::PARAM_INT);
            $queryTuneis->execute();
            $selectedTuneis = $queryTuneis->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    admin_set_flash('danger', 'Falha ao carregar empresas: ' . $e->getMessage());
}
?>

<style>
    .toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .toolbar .search-form {
        display: flex;
        gap: 8px;
        flex: 1;
        min-width: 250px;
    }

    .toolbar .search-form input {
        min-width: 200px;
    }

    .empresa-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
        gap: 14px;
    }

    .empresa-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
    }

    .empresa-panel-head h3 {
        margin: 0;
        font-size: 1.02rem;
    }

    .empresa-card-body {
        padding: 15px;
    }

    .domain-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        background: #fff;
    }

    .domain-actions {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .mini-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .mini-list li {
        border-bottom: 1px solid #edf2f7;
        padding: 8px 0;
        font-size: 0.9rem;
    }

    .mini-list li:last-child {
        border-bottom: 0;
    }

    .empty-box {
        text-align: center;
        color: #64748b;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 20px 14px;
        background: #f8fafc;
    }

    @media (max-width: 1200px) {
        .empresa-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        .toolbar .search-form {
            min-width: 0;
            width: 100%;
            flex-direction: column;
        }

        .toolbar .search-form input {
            min-width: 0;
            width: 100%;
        }

        .toolbar .btn-brand {
            width: 100%;
        }
    }
</style>

<div class="toolbar">
    <form class="search-form" method="get">
        <input type="hidden" name="page" value="empresas">
        <input type="text" class="form-control" name="q" value="<?= admin_h($search) ?>" placeholder="Buscar por nome, slug, banco ou dominio">
        <button type="submit" class="btn btn-soft"><i class="fa fa-search mr-1"></i> Buscar</button>
        <?php if ($search !== ''): ?>
            <a href="?page=empresas" class="btn btn-soft">Limpar</a>
        <?php endif; ?>
    </form>

    <button type="button" class="btn btn-brand" data-toggle="modal" data-target="#modalNovaEmpresa">
        <i class="fa fa-plus mr-1"></i> Nova empresa
    </button>
</div>

<script>
    (function () {
        var nomeInput = document.querySelector('#modalNovaEmpresa input[name="nome"]');
        var slugInput = document.querySelector('#modalNovaEmpresa input[name="slug"]');
        var adminToggle = document.getElementById('provisionar_admin');
        var adminNome = document.querySelector('#modalNovaEmpresa input[name="responsavel_nome"]');
        var adminEmail = document.querySelector('#modalNovaEmpresa input[name="admin_email"]');
        var adminSenha = document.querySelector('#modalNovaEmpresa input[name="admin_senha"]');

        function slugify(text) {
            return (text || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        if (nomeInput && slugInput) {
            nomeInput.addEventListener('blur', function () {
                if (slugInput.value.trim() === '') {
                    slugInput.value = slugify(nomeInput.value);
                }
            });
        }

        function updateAdminRequired() {
            var required = !!(adminToggle && adminToggle.checked);
            if (adminNome) adminNome.required = required;
            if (adminEmail) adminEmail.required = required;
            if (adminSenha) adminSenha.required = required;
        }

        if (adminToggle) {
            adminToggle.addEventListener('change', updateAdminRequired);
            updateAdminRequired();
        }
    })();
</script>

<div class="empresa-grid">
    <section class="panel-card">
        <header class="empresa-panel-head">
            <h3><i class="fa fa-building mr-2"></i>Lista de empresas (<?= count($empresas) ?>)</h3>
            <small class="text-muted">Selecione uma linha para ver os detalhes</small>
        </header>
        <div class="table-responsive">
            <table class="table table-modern table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>Dominio principal</th>
                        <th>Plano</th>
                        <th>Status</th>
                        <th>Expira em</th>
                        <th>Criada em</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($empresas)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Nenhuma empresa encontrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($empresas as $empresa):
                        $badge = empresas_badge($empresa['ativo'], $empresa['assinatura_status']);
                        $isSelected = (int) $empresa['id'] === $selectedEmpresaId;
                    ?>
                        <tr class="<?= $isSelected ? 'table-primary' : '' ?>">
                            <td>#<?= (int) $empresa['id'] ?></td>
                            <td>
                                <strong><?= admin_h($empresa['nome']) ?></strong><br>
                                <small class="text-muted"><?= admin_h($empresa['slug']) ?></small>
                            </td>
                            <td>
                                <?php if (!empty($empresa['dominio_principal'])): ?>
                                    <small><?= admin_h($empresa['dominio_principal']) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">Nao informado</small>
                                <?php endif; ?>
                            </td>
                            <td><?= admin_h($empresa['plano_nome'] ?: 'Sem plano') ?></td>
                            <td><span class="status-pill <?= admin_h($badge[0]) ?>"><?= admin_h($badge[1]) ?></span></td>
                            <td>
                                <?php if (!empty($empresa['ciclo_ate'])): ?>
                                    <?php
                                    $vencida = ($empresa['assinatura_status'] === 'Ativa' && date('Y-m-d') > $empresa['ciclo_ate']);
                                    ?>
                                    <small><?= admin_h(admin_format_date($empresa['ciclo_ate'])) ?></small>
                                    <?php if ($vencida): ?>
                                        <span class="status-pill status-danger mt-1">Vencido</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <small class="text-muted">Nao definido</small>
                                <?php endif; ?>
                            </td>
                            <td><small><?= admin_h(admin_format_date($empresa['criado_em'])) ?></small></td>
                            <td class="text-right">
                                <a href="?page=empresas&id=<?= (int) $empresa['id'] ?>&q=<?= urlencode($search) ?>" class="btn btn-sm btn-soft" title="Detalhes">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <form method="post" class="d-inline">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="toggle_empresa">
                                    <input type="hidden" name="empresa_id" value="<?= (int) $empresa['id'] ?>">
                                    <input type="hidden" name="return_id" value="<?= (int) $empresa['id'] ?>">
                                    <input type="hidden" name="return_q" value="<?= admin_h($search) ?>">
                                    <button class="btn btn-sm <?= $empresa['ativo'] === 'Sim' ? 'btn-danger' : 'btn-success' ?>" type="submit" title="<?= $empresa['ativo'] === 'Sim' ? 'Desativar' : 'Ativar' ?>">
                                        <i class="fa <?= $empresa['ativo'] === 'Sim' ? 'fa-ban' : 'fa-check' ?>"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel-card">
        <?php if (!$selectedEmpresa): ?>
            <div class="empresa-card-body">
                <div class="empty-box">
                    <i class="fa fa-building fa-2x mb-2"></i>
                    <p class="mb-1"><strong>Selecione uma empresa</strong></p>
                    <p class="mb-0">Clique em <em>detalhes</em> para gerenciar dominios, assinatura e parametros da empresa.</p>
                </div>
            </div>
        <?php else:
            $badge = empresas_badge($selectedEmpresa['ativo'], $selectedEmpresa['assinatura_status']);
        ?>
            <header class="empresa-panel-head">
                <h3><i class="fa fa-city mr-2"></i><?= admin_h($selectedEmpresa['nome']) ?></h3>
                <div class="d-flex align-items-center" style="gap:8px;">
                    <span class="status-pill <?= admin_h($badge[0]) ?>"><?= admin_h($badge[1]) ?></span>
                    <?php if (!empty($selectedEmpresa['dominio_principal'])): ?>
                        <a href="https://<?= admin_h($selectedEmpresa['dominio_principal']) ?>/sistema/" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-soft" title="Abrir painel da empresa">
                            <i class="fa fa-external-link-alt"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </header>

            <div class="empresa-card-body">
                <form method="post" class="mb-3">
                    <?= admin_csrf_input() ?>
                    <input type="hidden" name="action" value="update_empresa">
                    <input type="hidden" name="empresa_id" value="<?= (int) $selectedEmpresa['id'] ?>">
                    <input type="hidden" name="return_id" value="<?= (int) $selectedEmpresa['id'] ?>">
                    <input type="hidden" name="return_q" value="<?= admin_h($search) ?>">

                    <div class="form-group mb-2">
                        <label class="mb-1">Nome</label>
                        <input type="text" name="nome" class="form-control" required value="<?= admin_h($selectedEmpresa['nome']) ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-sm-6 mb-2">
                            <label class="mb-1">Slug</label>
                            <input type="text" name="slug" class="form-control" required value="<?= admin_h($selectedEmpresa['slug']) ?>">
                        </div>
                        <div class="form-group col-sm-6 mb-2">
                            <label class="mb-1">Banco</label>
                            <input type="text" name="banco" class="form-control" required value="<?= admin_h($selectedEmpresa['banco']) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-sm-6 mb-2">
                            <label class="mb-1">DB host</label>
                            <input type="text" name="db_host" class="form-control" required value="<?= admin_h($selectedEmpresa['db_host']) ?>">
                        </div>
                        <div class="form-group col-sm-6 mb-2">
                            <label class="mb-1">DB usuario</label>
                            <input type="text" name="db_usuario" class="form-control" required value="<?= admin_h($selectedEmpresa['db_usuario']) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-sm-7 mb-2">
                            <label class="mb-1">Nova senha DB (opcional)</label>
                            <input type="password" name="db_senha" class="form-control" placeholder="Manter senha atual">
                        </div>
                        <div class="form-group col-sm-5 mb-2">
                            <label class="mb-1">Status</label>
                            <select class="form-control" name="ativo">
                                <option value="Sim" <?= $selectedEmpresa['ativo'] === 'Sim' ? 'selected' : '' ?>>Sim</option>
                                <option value="Nao" <?= $selectedEmpresa['ativo'] === 'Nao' ? 'selected' : '' ?>>Nao</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-brand btn-sm"><i class="fa fa-save mr-1"></i>Salvar empresa</button>
                </form>

                <form method="post" class="mb-3 p-3" style="border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                    <?= admin_csrf_input() ?>
                    <input type="hidden" name="action" value="save_assinatura">
                    <input type="hidden" name="empresa_id" value="<?= (int) $selectedEmpresa['id'] ?>">
                    <input type="hidden" name="return_id" value="<?= (int) $selectedEmpresa['id'] ?>">
                    <input type="hidden" name="return_q" value="<?= admin_h($search) ?>">

                    <h6 class="mb-2"><i class="fa fa-credit-card mr-1"></i>Assinatura</h6>
                    <div class="form-row">
                        <div class="form-group col-sm-6 mb-2">
                            <label class="mb-1">Plano</label>
                            <select name="plano_id" class="form-control" required>
                                <option value="">Selecione</option>
                                <?php foreach ($planos as $plano): ?>
                                    <option value="<?= (int) $plano['id'] ?>" <?= (int) $selectedEmpresa['plano_id'] === (int) $plano['id'] ? 'selected' : '' ?>>
                                        <?= admin_h($plano['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-sm-6 mb-2">
                            <label class="mb-1">Status</label>
                            <select name="status_assinatura" class="form-control">
                                <?php
                                $statusAss = ['Trial', 'Ativa', 'Suspensa', 'Cancelada'];
                                $currentStatus = $selectedEmpresa['assinatura_status'] ?: 'Trial';
                                foreach ($statusAss as $status):
                                ?>
                                    <option value="<?= admin_h($status) ?>" <?= $currentStatus === $status ? 'selected' : '' ?>><?= admin_h($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-sm-6 mb-2">
                            <label class="mb-1">Trial (dias)</label>
                            <input type="number" min="0" name="trial_dias" class="form-control" value="14">
                        </div>
                        <div class="form-group col-sm-6 mb-2">
                            <label class="mb-1">Trial ate</label>
                            <input type="text" class="form-control" readonly value="<?= admin_h(admin_format_date($selectedEmpresa['trial_ate'])) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-sm-6 mb-2">
                            <label class="mb-1">Expiracao do plano</label>
                            <input type="date" name="ciclo_ate" class="form-control" value="<?= admin_h(empresas_input_data($selectedEmpresa['ciclo_ate'])) ?>">
                        </div>
                        <div class="form-group col-sm-6 mb-2">
                            <label class="mb-1">Status da expiracao</label>
                            <?php
                            $expirado = !empty($selectedEmpresa['ciclo_ate'])
                                && ($selectedEmpresa['assinatura_status'] === 'Ativa')
                                && date('Y-m-d') > $selectedEmpresa['ciclo_ate'];
                            ?>
                            <input type="text" class="form-control" readonly value="<?= $expirado ? 'Plano vencido' : 'Dentro do prazo' ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-soft btn-sm"><i class="fa fa-refresh mr-1"></i>Atualizar assinatura</button>
                </form>

                <div class="mb-3 p-3" style="border:1px solid #e2e8f0;border-radius:12px;background:#fff;">
                    <h6 class="mb-2"><i class="fa fa-globe mr-1"></i>Dominios</h6>

                    <?php if (empty($selectedDominios)): ?>
                        <div class="text-muted">Nenhum dominio cadastrado.</div>
                    <?php else: ?>
                        <?php foreach ($selectedDominios as $dominio): ?>
                            <div class="domain-item">
                                <div>
                                    <strong><?= admin_h($dominio['dominio']) ?></strong>
                                    <?php if ((int) $dominio['principal'] === 1): ?>
                                        <span class="status-pill status-success ml-1">Principal</span>
                                    <?php endif; ?>
                                </div>
                                <div class="domain-actions">
                                    <?php if ((int) $dominio['principal'] !== 1): ?>
                                        <form method="post" class="d-inline">
                                            <?= admin_csrf_input() ?>
                                            <input type="hidden" name="action" value="set_principal_dominio">
                                            <input type="hidden" name="empresa_id" value="<?= (int) $selectedEmpresa['id'] ?>">
                                            <input type="hidden" name="dominio_id" value="<?= (int) $dominio['id'] ?>">
                                            <input type="hidden" name="return_id" value="<?= (int) $selectedEmpresa['id'] ?>">
                                            <input type="hidden" name="return_q" value="<?= admin_h($search) ?>">
                                            <button type="submit" class="btn btn-sm btn-soft" title="Definir principal"><i class="fa fa-star"></i></button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" class="d-inline" onsubmit="return confirm('Remover este dominio?');">
                                        <?= admin_csrf_input() ?>
                                        <input type="hidden" name="action" value="remove_dominio">
                                        <input type="hidden" name="empresa_id" value="<?= (int) $selectedEmpresa['id'] ?>">
                                        <input type="hidden" name="dominio_id" value="<?= (int) $dominio['id'] ?>">
                                        <input type="hidden" name="return_id" value="<?= (int) $selectedEmpresa['id'] ?>">
                                        <input type="hidden" name="return_q" value="<?= admin_h($search) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Remover"><i class="fa fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <form method="post" class="mt-2">
                        <?= admin_csrf_input() ?>
                        <input type="hidden" name="action" value="add_dominio">
                        <input type="hidden" name="empresa_id" value="<?= (int) $selectedEmpresa['id'] ?>">
                        <input type="hidden" name="return_id" value="<?= (int) $selectedEmpresa['id'] ?>">
                        <input type="hidden" name="return_q" value="<?= admin_h($search) ?>">

                        <div class="form-row">
                            <div class="form-group col-sm-8 mb-2">
                                <input type="text" name="dominio" class="form-control" placeholder="novo-dominio.seudominio.com" required>
                            </div>
                            <div class="form-group col-sm-4 mb-2">
                                <select name="principal" class="form-control">
                                    <option value="0">Secundario</option>
                                    <option value="1">Principal</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-soft btn-sm"><i class="fa fa-plus mr-1"></i>Adicionar dominio</button>
                    </form>
                </div>

                <div class="mb-3 p-3" style="border:1px solid #e2e8f0;border-radius:12px;background:#fff;">
                    <h6 class="mb-2"><i class="fa fa-network-wired mr-1"></i>Tuneis (<?= count($selectedTuneis) ?>)</h6>
                    <?php if (empty($selectedTuneis)): ?>
                        <div class="text-muted">Nenhum tunel registrado para esta empresa.</div>
                    <?php else: ?>
                        <ul class="mini-list">
                            <?php foreach ($selectedTuneis as $tunel): ?>
                                <li>
                                    <strong><?= admin_h($tunel['tunnel_nome']) ?></strong>
                                    <span class="status-pill <?= $tunel['status'] === 'Ativo' ? 'status-success' : 'status-muted' ?> ml-1"><?= admin_h($tunel['status']) ?></span><br>
                                    <small class="text-muted"><?= admin_h($tunel['dominio']) ?> - <?= admin_h($tunel['service_url']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="p-3" style="border:1px solid #e2e8f0;border-radius:12px;background:#fff;">
                    <h6 class="mb-2"><i class="fa fa-users mr-1"></i>Usuarios SaaS (<?= count($selectedUsuarios) ?>)</h6>
                    <?php if (empty($selectedUsuarios)): ?>
                        <div class="text-muted">Nenhum usuario SaaS vinculado.</div>
                    <?php else: ?>
                        <ul class="mini-list">
                            <?php foreach ($selectedUsuarios as $usuario): ?>
                                <li>
                                    <strong><?= admin_h($usuario['nome']) ?></strong>
                                    <span class="status-pill <?= $usuario['ativo'] === 'Sim' ? 'status-success' : 'status-muted' ?> ml-1"><?= admin_h($usuario['ativo']) ?></span><br>
                                    <small class="text-muted"><?= admin_h($usuario['email']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="modal fade" id="modalNovaEmpresa" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="create_empresa">
                <input type="hidden" name="return_q" value="<?= admin_h($search) ?>">

                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-plus-circle mr-2"></i>Cadastrar nova empresa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Nome da empresa</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Slug (opcional)</label>
                            <input type="text" name="slug" class="form-control" placeholder="gerado automaticamente se vazio">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Responsavel</label>
                            <input type="text" name="responsavel_nome" class="form-control" placeholder="Nome do administrador">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Email admin</label>
                            <input type="email" name="admin_email" class="form-control" placeholder="admin@empresa.com">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Senha admin</label>
                            <input type="password" name="admin_senha" class="form-control" placeholder="minimo 6 caracteres">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Dominio principal</label>
                            <input type="text" name="dominio_principal" class="form-control" required placeholder="empresa.seudominio.com">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Status da empresa</label>
                            <select name="ativo" class="form-control">
                                <option value="Sim">Ativa</option>
                                <option value="Nao">Inativa</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Dominios extras (separe por virgula)</label>
                        <input type="text" name="dominios_extras" class="form-control" placeholder="www.empresa.com, app.empresa.com">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Banco tenant</label>
                            <input type="text" name="banco" class="form-control" placeholder="gerado automaticamente se vazio">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Plano inicial</label>
                            <select name="plano_slug" class="form-control">
                                <?php foreach ($planos as $plano): ?>
                                    <option value="<?= admin_h($plano['slug']) ?>" <?= $plano['slug'] === 'starter' ? 'selected' : '' ?>>
                                        <?= admin_h($plano['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>DB host</label>
                            <input type="text" name="db_host" class="form-control" value="<?= admin_h($defaultDbHost) ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>DB usuario</label>
                            <input type="text" name="db_usuario" class="form-control" value="<?= admin_h($defaultDbUser) ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>DB senha</label>
                            <input type="password" name="db_senha" class="form-control" placeholder="deixe vazio para usar a senha padrao do servidor">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label>Service URL do tunel</label>
                            <input type="text" name="service_url" class="form-control" value="<?= admin_h($defaultServiceUrl) ?>" placeholder="http://127.0.0.1:8000">
                        </div>
                        <div class="form-group col-md-5">
                            <label>Nome do tunel (opcional)</label>
                            <input type="text" name="tunnel_nome" class="form-control" placeholder="tenant-nome-da-empresa">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Status da assinatura</label>
                            <select name="status_assinatura" class="form-control">
                                <option value="Trial" selected>Trial</option>
                                <option value="Ativa">Ativa</option>
                                <option value="Suspensa">Suspensa</option>
                                <option value="Cancelada">Cancelada</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Trial (dias)</label>
                            <input type="number" min="0" name="trial_dias" class="form-control" value="14">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Data de expiracao do plano</label>
                            <input type="date" name="ciclo_ate" class="form-control">
                        </div>
                        <div class="form-group col-md-6 d-flex align-items-end">
                            <small class="form-text text-muted mb-2">
                                Opcional. Se preencher, o sistema bloqueia acesso apos essa data quando a assinatura estiver Ativa.
                            </small>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-block mb-2">Provisionamento automatico</label>
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="provisionar_banco" name="provisionar_banco" value="1" checked>
                            <label class="custom-control-label" for="provisionar_banco">Criar banco tenant automaticamente a partir do modelo</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="provisionar_admin" name="provisionar_admin" value="1" checked>
                            <label class="custom-control-label" for="provisionar_admin">Criar usuario SaaS e configurar administrador no tenant</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="provisionar_tunnel" name="provisionar_tunnel" value="1" checked>
                            <label class="custom-control-label" for="provisionar_tunnel">Criar tunnel + DNS + iniciar cloudflared automaticamente</label>
                        </div>
                        <small class="form-text text-muted mt-2">
                            Com os tres itens marcados, o cadastro ja sai pronto para uso no dominio informado.
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand"><i class="fa fa-save mr-1"></i>Criar empresa</button>
                </div>
            </form>
        </div>
    </div>
</div>
