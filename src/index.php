<?php
require_once 'db.php';

$mensagem = '';
$active_tab = $_GET['tab'] ?? 'pets'; // Padrão: Pets e Tutores

// Processamento de Upload de Foto Helper
function processarUploadFoto($fileInputName) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extensao = strtolower(pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION));
        
        if (in_array($extensao, $extensoes_permitidas)) {
            $nome_arquivo = 'pet_' . uniqid() . '.' . $extensao;
            $destino_dir = __DIR__ . '/uploads/';
            if (!is_dir($destino_dir)) {
                mkdir($destino_dir, 0777, true);
            }
            $destino = $destino_dir . $nome_arquivo;
            if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $destino)) {
                return 'uploads/' . $nome_arquivo;
            }
        }
    }
    return null;
}

// Helper para exibição segura e consistente de ícones de serviços
function resolverIconeServico($icone, $nomeServico = '') {
    $icone = trim((string)($icone ?? ''));
    // Se for um caractere/emoji válido e não tiver sido corrompido por mojibake (como ð, Ÿ, â)
    if ($icone !== '' && mb_check_encoding($icone, 'UTF-8') && !preg_match('/[Ãðâ]/', $icone)) {
        return $icone;
    }
    $nomeLower = mb_strtolower((string)$nomeServico);
    if (str_contains($nomeLower, 'banho') && str_contains($nomeLower, 'tosa')) return '🧼';
    if (str_contains($nomeLower, 'banho')) return '🛁';
    if (str_contains($nomeLower, 'tosa')) return '✂️';
    if (str_contains($nomeLower, 'consulta') || str_contains($nomeLower, 'veterin')) return '🩺';
    if (str_contains($nomeLower, 'vacin')) return '💉';
    if (str_contains($nomeLower, 'hidrata')) return '✨';
    if (str_contains($nomeLower, 'unha') || str_contains($nomeLower, 'auricular') || str_contains($nomeLower, 'ouvido')) return '🐾';
    if (str_contains($nomeLower, 'táxi') || str_contains($nomeLower, 'taxi') || str_contains($nomeLower, 'transporte')) return '🚗';
    if (str_contains($nomeLower, 'hotel') || str_contains($nomeLower, 'adestram')) return '🦴';
    return '🐾';
}

// ==========================================
// 1. LÓGICA DE CADASTRO (INSERT)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cadastrar') {
    $nome_cliente = trim($_POST['nome_cliente'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    $nome_pet = trim($_POST['nome_pet'] ?? '');
    $especie = trim($_POST['especie'] ?? '');
    $raca = trim($_POST['raca'] ?? '');
    $idade = trim($_POST['idade'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    $foto_path = processarUploadFoto('foto');

    try {
        $pdo->beginTransaction();

        // 1. Insere Cliente
        $stmt_cliente = $pdo->prepare("INSERT INTO clientes (nome, telefone, email) VALUES (?, ?, ?)");
        $stmt_cliente->execute([$nome_cliente, $telefone, $email]);
        $cliente_id = $pdo->lastInsertId();

        // 2. Insere Pet com os novos campos (idade, foto, observações)
        $stmt_pet = $pdo->prepare("INSERT INTO pets (cliente_id, nome, especie, raca, idade, foto, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_pet->execute([$cliente_id, $nome_pet, $especie, $raca, $idade, $foto_path, $observacoes]);

        $pdo->commit();
        $mensagem = "<div class='alert alert-success'><span>🐾</span> <strong>Sucesso!</strong> Tutor e Pet cadastrados com sucesso!</div>";
        $active_tab = 'pets';
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro:</strong> Não foi possível cadastrar: " . htmlspecialchars($e->getMessage()) . "</div>";
        $active_tab = 'cadastro';
    }
}

// ==========================================
// 2. LÓGICA DE ATUALIZAÇÃO (UPDATE - VIA MODAL)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar') {
    $cliente_id = $_POST['cliente_id'] ?? null;
    $pet_id = $_POST['pet_id'] ?? null;

    $nome_cliente = trim($_POST['nome_cliente'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $nome_pet = trim($_POST['nome_pet'] ?? '');
    $especie = trim($_POST['especie'] ?? '');
    $raca = trim($_POST['raca'] ?? '');
    $idade = trim($_POST['idade'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    $nova_foto = processarUploadFoto('foto_update');

    if ($cliente_id) {
        try {
            $pdo->beginTransaction();

            // Atualiza Cliente
            $stmt_cli = $pdo->prepare("UPDATE clientes SET nome = ?, telefone = ?, email = ? WHERE id = ?");
            $stmt_cli->execute([$nome_cliente, $telefone, $email, $cliente_id]);

            // Atualiza Pet
            if ($nova_foto) {
                $stmt_pet = $pdo->prepare("UPDATE pets SET nome = ?, especie = ?, raca = ?, idade = ?, observacoes = ?, foto = ? WHERE cliente_id = ?");
                $stmt_pet->execute([$nome_pet, $especie, $raca, $idade, $observacoes, $nova_foto, $cliente_id]);
            } else {
                $stmt_pet = $pdo->prepare("UPDATE pets SET nome = ?, especie = ?, raca = ?, idade = ?, observacoes = ? WHERE cliente_id = ?");
                $stmt_pet->execute([$nome_pet, $especie, $raca, $idade, $observacoes, $cliente_id]);
            }

            $pdo->commit();
            $mensagem = "<div class='alert alert-success'><span>✅</span> <strong>Atualizado!</strong> Os dados do Tutor e Pet foram atualizados com sucesso!</div>";
            $active_tab = 'pets';
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// ==========================================
// 3. LÓGICA DE SERVIÇOS E AGENDAMENTOS (AC3)
// ==========================================
// Cadastro de Novo Serviço no Catálogo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cadastrar_servico') {
    $nome = trim($_POST['nome_servico'] ?? '');
    $preco = floatval(str_replace(',', '.', $_POST['preco_servico'] ?? '0.00'));
    $descricao = trim($_POST['descricao_servico'] ?? '');
    $icone = trim($_POST['icone_servico'] ?? '🐾');
    $duracao = (int)($_POST['duracao_minutos'] ?? 30);

    if (!empty($nome) && $preco >= 0) {
        try {
            $stmt_serv = $pdo->prepare("INSERT INTO servicos (nome, preco, descricao, icone, duracao_minutos) VALUES (?, ?, ?, ?, ?)");
            $stmt_serv->execute([$nome, $preco, $descricao, $icone, $duracao]);
            $mensagem = "<div class='alert alert-success'><span>✨</span> <strong>Serviço Cadastrado!</strong> O serviço '<strong>" . htmlspecialchars($nome) . "</strong>' foi adicionado com sucesso!</div>";
            $active_tab = 'servicos';
        } catch (Exception $e) {
            $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro ao cadastrar serviço:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            $active_tab = 'servicos';
        }
    } else {
        $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Atenção:</strong> Informe o nome e valor do serviço.</div>";
        $active_tab = 'servicos';
    }
}

// Cadastro de Agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cadastrar_agendamento') {
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    $servico_id = !empty($_POST['servico_id']) ? (int)$_POST['servico_id'] : null;
    $servico = trim($_POST['servico'] ?? '');

    // Se selecionou pelo ID do serviço, busca o nome correspondente
    if ($servico_id) {
        $stmt_s = $pdo->prepare("SELECT nome, preco FROM servicos WHERE id = ?");
        $stmt_s->execute([$servico_id]);
        $s_info = $stmt_s->fetch();
        if ($s_info) {
            $servico = $s_info['nome'];
        }
    }

    $data_hora = trim($_POST['data_hora'] ?? '');
    $status = trim($_POST['status'] ?? 'Agendado');
    $valor = floatval(str_replace(',', '.', $_POST['valor'] ?? '0.00'));
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($pet_id > 0 && !empty($servico) && !empty($data_hora)) {
        try {
            $stmt_ag = $pdo->prepare("INSERT INTO agendamentos (pet_id, servico_id, servico, data_hora, status, valor, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_ag->execute([$pet_id, $servico_id, $servico, $data_hora, $status, $valor, $observacoes]);
            $mensagem = "<div class='alert alert-success'><span>📅</span> <strong>Agendamento Realizado!</strong> O serviço foi agendado com sucesso!</div>";
            $active_tab = 'agendamentos';
        } catch (Exception $e) {
            $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro ao agendar:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            $active_tab = 'agendamentos';
        }
    } else {
        $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Atenção:</strong> Selecione o Pet e preencha a Data/Hora do agendamento.</div>";
        $active_tab = 'agendamentos';
    }
}

// Atualização de Agendamento (Edição Completa)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_agendamento') {
    $agendamento_id = (int)($_POST['agendamento_id'] ?? 0);
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    $servico_id = !empty($_POST['servico_id']) ? (int)$_POST['servico_id'] : null;
    $servico = trim($_POST['servico'] ?? '');

    if ($servico_id) {
        $stmt_s = $pdo->prepare("SELECT nome FROM servicos WHERE id = ?");
        $stmt_s->execute([$servico_id]);
        $s_info = $stmt_s->fetch();
        if ($s_info) {
            $servico = $s_info['nome'];
        }
    }

    $data_hora = trim($_POST['data_hora'] ?? '');
    $status = trim($_POST['status'] ?? 'Agendado');
    $valor = floatval(str_replace(',', '.', $_POST['valor'] ?? '0.00'));
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($agendamento_id > 0 && $pet_id > 0 && !empty($servico) && !empty($data_hora)) {
        try {
            $stmt_up_ag = $pdo->prepare("UPDATE agendamentos SET pet_id = ?, servico_id = ?, servico = ?, data_hora = ?, status = ?, valor = ?, observacoes = ? WHERE id = ?");
            $stmt_up_ag->execute([$pet_id, $servico_id, $servico, $data_hora, $status, $valor, $observacoes, $agendamento_id]);
            $mensagem = "<div class='alert alert-success'><span>✅</span> <strong>Agendamento Atualizado!</strong> Dados do serviço alterados com sucesso!</div>";
            $active_tab = 'agendamentos';
        } catch (Exception $e) {
            $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            $active_tab = 'agendamentos';
        }
    }
}

// Mudança Interativa de Status (Conforme Atendimento)
if (isset($_GET['mudar_status']) && isset($_GET['agendamento_id'])) {
    $agendamento_id = (int)$_GET['agendamento_id'];
    $novo_status = trim($_GET['mudar_status']);
    $status_permitidos = ['Agendado', 'Em Andamento', 'Concluído', 'Cancelado'];

    if ($agendamento_id > 0 && in_array($novo_status, $status_permitidos)) {
        try {
            $stmt_status = $pdo->prepare("UPDATE agendamentos SET status = ? WHERE id = ?");
            $stmt_status->execute([$novo_status, $agendamento_id]);
            
            if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'status' => $novo_status]);
                exit;
            }
            $mensagem = "<div class='alert alert-success'><span>🔄</span> Status do agendamento atualizado para <strong>$novo_status</strong>!</div>";
            $active_tab = 'agendamentos';
        } catch (Exception $e) {
            if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
            $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            $active_tab = 'agendamentos';
        }
    }
}

// ==========================================
// 4. ESTATÍSTICAS PARA OS CARDS DO TOPO
// ==========================================
$total_pets = (int)$pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn();
$total_clientes = (int)$pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$total_caes = (int)$pdo->query("SELECT COUNT(*) FROM pets WHERE LOWER(especie) LIKE '%cão%' OR LOWER(especie) LIKE '%cao%' OR LOWER(especie) LIKE '%cachorro%'")->fetchColumn();
$total_gatos = (int)$pdo->query("SELECT COUNT(*) FROM pets WHERE LOWER(especie) LIKE '%gato%'")->fetchColumn();

// Métricas de Serviços e Agendamentos (AC3)
$stmt_servicos = $pdo->query("SELECT * FROM servicos ORDER BY nome ASC");
$lista_servicos = $stmt_servicos->fetchAll();
$total_servicos = count($lista_servicos);

$total_agendamentos = (int)$pdo->query("SELECT COUNT(*) FROM agendamentos")->fetchColumn();
$total_agendamentos_pendentes = (int)$pdo->query("SELECT COUNT(*) FROM agendamentos WHERE status IN ('Agendado', 'Em Andamento')")->fetchColumn();
$total_agendamentos_concluidos = (int)$pdo->query("SELECT COUNT(*) FROM agendamentos WHERE status = 'Concluído'")->fetchColumn();
$faturamento_servicos = (float)$pdo->query("SELECT COALESCE(SUM(valor), 0) FROM agendamentos WHERE status = 'Concluído'")->fetchColumn();

// Lista de pets para o select do modal de agendamento
$stmt_pets_select = $pdo->query("SELECT p.id as pet_id, p.nome as pet_nome, p.especie, p.raca, c.nome as tutor_nome 
                                  FROM pets p 
                                  JOIN clientes c ON p.cliente_id = c.id 
                                  ORDER BY p.nome ASC");
$lista_pets_select = $stmt_pets_select->fetchAll();

// ==========================================
// 5. LISTAGEM COM FILTRO DE BUSCA (SELECT)
// ==========================================
$busca = trim($_GET['busca'] ?? '');
$query = "SELECT c.id as cliente_id, c.nome as cliente_nome, c.telefone, c.email, c.data_cadastro as cliente_data,
                 p.id as pet_id, p.nome as pet_nome, p.especie, p.raca, p.idade, p.foto, p.observacoes, p.data_cadastro as pet_data
          FROM clientes c 
          LEFT JOIN pets p ON c.id = p.cliente_id";

if ($busca !== '') {
    $query .= " WHERE c.nome LIKE :busca1 OR p.nome LIKE :busca2 OR p.raca LIKE :busca3 OR p.especie LIKE :busca4";
    $active_tab = 'pets';
}
$query .= " ORDER BY c.data_cadastro DESC";

$stmt_listagem = $pdo->prepare($query);
if ($busca !== '') {
    $termo = "%$busca%";
    $stmt_listagem->execute([
        'busca1' => $termo,
        'busca2' => $termo,
        'busca3' => $termo,
        'busca4' => $termo
    ]);
} else {
    $stmt_listagem->execute();
}
$registros = $stmt_listagem->fetchAll();

// ==========================================
// 6. LISTAGEM DE AGENDAMENTOS (AC3)
// ==========================================
$filtro_status = trim($_GET['status_filtro'] ?? '');
$busca_ag = trim($_GET['busca_ag'] ?? '');

$query_ag = "SELECT a.*, p.nome as pet_nome, p.especie, p.raca, p.foto as pet_foto,
                    c.id as tutor_id, c.nome as tutor_nome, c.telefone as tutor_telefone, c.email as tutor_email,
                    s.icone as servico_icone
             FROM agendamentos a
             JOIN pets p ON a.pet_id = p.id
             JOIN clientes c ON p.cliente_id = c.id
             LEFT JOIN servicos s ON a.servico_id = s.id
             WHERE 1=1";

$params_ag = [];
if ($filtro_status !== '') {
    $query_ag .= " AND a.status = :status";
    $params_ag['status'] = $filtro_status;
    $active_tab = 'agendamentos';
}
if ($busca_ag !== '') {
    $query_ag .= " AND (p.nome LIKE :busca_ag1 OR c.nome LIKE :busca_ag2 OR a.servico LIKE :busca_ag3)";
    $termo_ag = "%$busca_ag%";
    $params_ag['busca_ag1'] = $termo_ag;
    $params_ag['busca_ag2'] = $termo_ag;
    $params_ag['busca_ag3'] = $termo_ag;
    $active_tab = 'agendamentos';
}
$query_ag .= " ORDER BY a.data_hora ASC";

$stmt_ag = $pdo->prepare($query_ag);
$stmt_ag->execute($params_ag);
$agendamentos = $stmt_ag->fetchAll();

// Agrupamento para as 4 Colunas (Agendados, Em Andamento, Concluídos, Cancelados)
$colunas_status = [
    'Agendado' => [
        'titulo' => 'Agendados',
        'classe' => 'kanban-col-agendado',
        'icone' => '🔵',
        'itens' => []
    ],
    'Em Andamento' => [
        'titulo' => 'Em Andamento',
        'classe' => 'kanban-col-andamento',
        'icone' => '🟡',
        'itens' => []
    ],
    'Concluído' => [
        'titulo' => 'Concluídos',
        'classe' => 'kanban-col-concluido',
        'icone' => '🟢',
        'itens' => []
    ],
    'Cancelado' => [
        'titulo' => 'Cancelados',
        'classe' => 'kanban-col-cancelado',
        'icone' => '🔴',
        'itens' => []
    ]
];

foreach ($agendamentos as $ag) {
    $st = $ag['status'];
    if (isset($colunas_status[$st])) {
        $colunas_status[$st]['itens'][] = $ag;
    } else {
        $colunas_status['Agendado']['itens'][] = $ag;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare - Sistema de Gestão para PetShop</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
    <div class="app-layout">
        <!-- ===============================
             SIDEBAR (MENU LATERAL DASHBOARD)
             =============================== -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">🐾</div>
                <div class="brand-title">pet<span>care</span></div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-label">Gerenciamento</div>
                
                <!-- Item 1: Novo Cadastro -->
                <button type="button" class="nav-item <?= $active_tab === 'cadastro' ? 'active' : '' ?>" id="btnNavCadastro" onclick="switchTab('cadastro')">
                    <span class="icon">✨</span>
                    <span>Novo Cadastro</span>
                </button>

                <!-- Item 2: Pets e Tutores -->
                <button type="button" class="nav-item <?= $active_tab === 'pets' ? 'active' : '' ?>" id="btnNavPets" onclick="switchTab('pets')">
                    <span class="icon">🐶</span>
                    <span>Pets e Tutores</span>
                    <span class="nav-badge"><?= $total_pets ?></span>
                </button>

                <div class="nav-label" style="margin-top: 1rem;">Módulos Adicionais</div>
                
                <button type="button" class="nav-item" onclick="switchTab('pets')">
                    <span class="icon">📊</span>
                    <span>Dashboard</span>
                </button>

                <button type="button" class="nav-item <?= $active_tab === 'agendamentos' ? 'active' : '' ?>" id="btnNavAgendamentos" onclick="switchTab('agendamentos')">
                    <span class="icon">📅</span>
                    <span>Agendamentos</span>
                    <span class="nav-badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;"><?= $total_agendamentos_pendentes ?></span>
                </button>

                <button type="button" class="nav-item <?= $active_tab === 'servicos' ? 'active' : '' ?>" id="btnNavServicos" onclick="switchTab('servicos')">
                    <span class="icon">✂️</span>
                    <span>Serviços & Preços</span>
                    <span class="nav-badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;"><?= $total_servicos ?></span>
                </button>
            </nav>
        </aside>

        <!-- ===============================
             ÁREA PRINCIPAL
             =============================== -->
        <div class="main-wrapper">
            <!-- Barra Superior (Topbar) -->
            <header class="topbar">
                <div class="topbar-left">
                    <div class="topbar-clinic-select">
                        <span>🏥 Unidade Central</span>
                        <span style="font-size: 0.75rem; color: #94a3b8;">▾</span>
                    </div>
                </div>

                <div class="topbar-right">
                    <button type="button" class="btn btn-navy btn-sm" onclick="abrirModalNovoAgendamento()">
                        <span>📅</span> + Agendar Serviço
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="switchTab('servicos')">
                        <span>✂️</span> Serviços
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="switchTab('cadastro')">
                        <span>+</span> Novo Cadastro
                    </button>
                    <div class="user-profile-chip" title="Aluno Willian Medeiros">
                        <div class="user-avatar">WM</div>
                        <span class="user-name">Olá, Willian</span>
                    </div>
                </div>
            </header>

            <!-- Conteúdo da Página -->
            <main class="content-body">
                <?= $mensagem ?>

                <!-- CARDS DE ESTATÍSTICAS (ESTILO DASHBOARD DO PRINT) -->
                <section class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon-wrapper icon-blue">🐶</div>
                        <div class="stat-info">
                            <span class="stat-label">Total de Pets</span>
                            <span class="stat-value"><?= $total_pets ?></span>
                            <span class="stat-subtext"><?= $total_caes ?> cães • <?= $total_gatos ?> gatos</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon-wrapper icon-amber">👤</div>
                        <div class="stat-info">
                            <span class="stat-label">Tutores</span>
                            <span class="stat-value"><?= $total_clientes ?></span>
                            <span class="stat-subtext">Clientes registrados</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon-wrapper icon-purple">📅</div>
                        <div class="stat-info">
                            <span class="stat-label">Agendamentos</span>
                            <span class="stat-value"><?= $total_agendamentos_pendentes ?></span>
                            <span class="stat-subtext">Pendentes / Em andamento</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon-wrapper icon-emerald">💰</div>
                        <div class="stat-info">
                            <span class="stat-label">Faturamento Concluído</span>
                            <span class="stat-value">R$ <?= number_format($faturamento_servicos, 2, ',', '.') ?></span>
                            <span class="stat-subtext"><?= $total_agendamentos_concluidos ?> serviços concluídos</span>
                        </div>
                    </div>
                </section>

                <!-- ==========================================
                     ABA 1: PETS E TUTORES (TABELA & LISTAGEM)
                     ========================================== -->
                <section id="section-pets" class="tab-content <?= $active_tab === 'pets' ? 'active' : '' ?>">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <div>
                                <h2 class="dashboard-card-title">
                                    <span>📋</span> Pets e Tutores Cadastrados
                                </h2>
                                <p class="dashboard-card-subtitle">Listagem de clientes e animais com acesso a detalhes rápidos e edição.</p>
                            </div>
                            <div style="display: flex; gap: 0.8rem; align-items: center;">
                                <?php if ($busca): ?>
                                    <a href="index.php?tab=pets" class="btn btn-secondary btn-sm">Limpar Filtro</a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-primary btn-sm" onclick="switchTab('cadastro')">
                                    <span>+</span> Cadastrar Novo
                                </button>
                            </div>
                        </div>

                        <!-- Barra de Busca Rápida -->
                        <div class="table-filter-bar">
                            <form method="GET" action="index.php" class="search-box">
                                <input type="hidden" name="tab" value="pets">
                                <input type="text" name="busca" placeholder="Filtrar por nome do pet, tutor ou raça..." value="<?= htmlspecialchars($busca) ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">Filtrar</button>
                            </form>
                            <span style="font-size: 0.85rem; color: #64748b;">
                                Total exibido: <strong><?= count($registros) ?></strong> registros
                            </span>
                        </div>

                        <!-- Tabela Estilizada -->
                        <div class="table-responsive">
                            <table class="pet-table">
                                <thead>
                                    <tr>
                                        <th>Pet</th>
                                        <th>Espécie</th>
                                        <th>Tutor Responsável</th>
                                        <th>Contato</th>
                                        <th>Idade / Obs</th>
                                        <th style="text-align: right;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($registros) > 0): ?>
                                        <?php foreach ($registros as $row): 
                                            $esp = strtolower($row['especie'] ?? '');
                                            $badgeClass = 'badge-other';
                                            if (str_contains($esp, 'cão') || str_contains($esp, 'cao') || str_contains($esp, 'cachorro')) {
                                                $badgeClass = 'badge-dog';
                                            } elseif (str_contains($esp, 'gato')) {
                                                $badgeClass = 'badge-cat';
                                            }
                                            
                                            // Foto ou Avatar
                                            $fotoSrc = !empty($row['foto']) && file_exists(__DIR__ . '/' . $row['foto']) ? $row['foto'] : null;
                                            $petIcon = str_contains($esp, 'gato') ? '🐱' : '🐶';

                                            // Dados formatados em JSON para o Modal
                                            $jsonData = htmlspecialchars(json_encode([
                                                'cliente_id' => $row['cliente_id'],
                                                'cliente_nome' => $row['cliente_nome'],
                                                'telefone' => $row['telefone'],
                                                'email' => $row['email'],
                                                'pet_id' => $row['pet_id'],
                                                'pet_nome' => $row['pet_nome'],
                                                'especie' => $row['especie'],
                                                'raca' => $row['raca'] ?? 'Não informada',
                                                'idade' => $row['idade'] ?? 'Não informada',
                                                'observacoes' => $row['observacoes'] ?? '',
                                                'foto' => $fotoSrc,
                                                'data_cadastro' => date('d/m/Y H:i', strtotime($row['cliente_data'] ?? 'now'))
                                            ]), ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="pet-cell">
                                                        <?php if ($fotoSrc): ?>
                                                            <img src="<?= htmlspecialchars($fotoSrc) ?>" alt="Foto" class="pet-avatar">
                                                        <?php else: ?>
                                                            <div class="pet-avatar"><?= $petIcon ?></div>
                                                        <?php endif; ?>
                                                        <div class="pet-info-col">
                                                            <span class="pet-name-title"><?= htmlspecialchars($row['pet_nome'] ?? 'Sem Pet') ?></span>
                                                            <span class="pet-sub-meta"><?= htmlspecialchars($row['raca'] ?: 'Raça não informada') ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $badgeClass ?>">
                                                        <?= htmlspecialchars($row['especie'] ?: 'Indefinido') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="tutor-cell-title"><?= htmlspecialchars($row['cliente_nome']) ?></div>
                                                    <div class="tutor-cell-sub"><?= htmlspecialchars($row['email']) ?></div>
                                                </td>
                                                <td>
                                                    <span style="font-weight: 500;"><?= htmlspecialchars($row['telefone']) ?></span>
                                                </td>
                                                <td>
                                                    <div style="font-size: 0.85rem; font-weight: 600; color: #1e293b;">
                                                        <?= htmlspecialchars($row['idade'] ?: '-') ?>
                                                    </div>
                                                    <?php if (!empty($row['observacoes'])): ?>
                                                        <div style="font-size: 0.78rem; color: #f59e0b; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($row['observacoes']) ?>">
                                                            ★ <?= htmlspecialchars($row['observacoes']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align: right; white-space: nowrap;">
                                                    <button type="button" class="btn btn-info btn-sm" onclick='abrirModalDetalhes(<?= $jsonData ?>)'>
                                                        🔍 Detalhes
                                                    </button>
                                                    <a href="excluir.php?id=<?= $row['cliente_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir o tutor <?= addslashes($row['cliente_nome']) ?> e seu respectivo pet?');" style="margin-left: 0.3rem;">
                                                        🗑️ Excluir
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
                                                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🐾</div>
                                                <p style="font-size: 1rem; font-weight: 600; color: #64748b;">Nenhum tutor ou pet encontrado.</p>
                                                <p style="font-size: 0.85rem; margin-top: 0.3rem;">Utilize o formulário de <strong>Novo Cadastro</strong> para adicionar o primeiro registro.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ==========================================
                     ABA 3: GESTÃO DE AGENDAMENTOS (AC3 - 4 COLUNAS)
                     ========================================== -->
                <section id="section-agendamentos" class="tab-content <?= $active_tab === 'agendamentos' ? 'active' : '' ?>">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <div>
                                <h2 class="dashboard-card-title">
                                    <span>📅</span> Gestão de Agendamentos por Colunas
                                </h2>
                                <p class="dashboard-card-subtitle">Acompanhe o fluxo de atendimento em 4 colunas: Agendados, Em andamento, Concluídos e Cancelados.</p>
                            </div>
                            <div style="display: flex; gap: 0.8rem; align-items: center; flex-wrap: wrap;">
                                <!-- Seletor de Visão (Colunas vs Tabela) -->
                                <div class="view-toggle">
                                    <button type="button" class="view-toggle-btn active" id="btnVisaoColunas" onclick="alternarVisaoAgendamentos('colunas')">
                                        <span>📊</span> Colunas
                                    </button>
                                    <button type="button" class="view-toggle-btn" id="btnVisaoTabela" onclick="alternarVisaoAgendamentos('tabela')">
                                        <span>📋</span> Tabela
                                    </button>
                                </div>

                                <button type="button" class="btn btn-secondary btn-sm" onclick="switchTab('servicos')">
                                    <span>✂️</span> Tabela de Serviços
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalNovoAgendamento()">
                                    <span>+</span> Novo Agendamento
                                </button>
                            </div>
                        </div>

                        <!-- Barra de Busca -->
                        <div class="table-filter-bar">
                            <form method="GET" action="index.php" class="search-box">
                                <input type="hidden" name="tab" value="agendamentos">
                                <input type="text" name="busca_ag" placeholder="Buscar por pet, tutor ou serviço..." value="<?= htmlspecialchars($busca_ag) ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">Filtrar</button>
                            </form>
                            <?php if ($busca_ag !== ''): ?>
                                <a href="index.php?tab=agendamentos" class="btn btn-secondary btn-sm">Limpar Busca</a>
                            <?php endif; ?>
                            <span style="font-size: 0.85rem; color: #64748b;">
                                Total: <strong><?= $total_agendamentos ?></strong> agendamentos cadastrados
                            </span>
                        </div>

                        <!-- ==============================================
                             VISÃO 1: 4 COLUNAS KANBAN DE AGENDAMENTOS
                             ============================================== -->
                        <div id="containerVisaoColunas" class="kanban-board">
                            <?php foreach ($colunas_status as $statusChave => $col): ?>
                                <div class="kanban-column <?= $col['classe'] ?>">
                                    <div class="kanban-column-header">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span><?= $col['icone'] ?></span>
                                            <span><?= $col['titulo'] ?></span>
                                        </div>
                                        <span class="kanban-col-count"><?= count($col['itens']) ?></span>
                                    </div>

                                    <div class="kanban-cards-list">
                                        <?php if (count($col['itens']) > 0): ?>
                                            <?php foreach ($col['itens'] as $ag): 
                                                // Avatar do Pet
                                                $petFotoSrc = !empty($ag['pet_foto']) && file_exists(__DIR__ . '/' . $ag['pet_foto']) ? $ag['pet_foto'] : null;
                                                $petIconDefault = str_contains(strtolower($ag['especie']), 'gato') ? '🐱' : '🐶';
                                                
                                                // Ícone do serviço
                                                $sIcone = resolverIconeServico($ag['servico_icone'] ?? '', $ag['servico'] ?? '');

                                                // JSON para o PopUp
                                                $jsonAgData = htmlspecialchars(json_encode([
                                                    'id' => $ag['id'],
                                                    'pet_id' => $ag['pet_id'],
                                                    'pet_nome' => $ag['pet_nome'],
                                                    'tutor_nome' => $ag['tutor_nome'],
                                                    'servico_id' => $ag['servico_id'],
                                                    'servico' => $ag['servico'],
                                                    'data_hora' => date('Y-m-d\TH:i', strtotime($ag['data_hora'])),
                                                    'status' => $ag['status'],
                                                    'valor' => $ag['valor'],
                                                    'observacoes' => $ag['observacoes'] ?? ''
                                                ]), ENT_QUOTES, 'UTF-8');
                                            ?>
                                                <div class="kanban-card">
                                                    <!-- Data e Hora -->
                                                    <div class="kanban-card-date">
                                                        <span>🕒 <?= date('d/m/Y', strtotime($ag['data_hora'])) ?> às <?= date('H:i', strtotime($ag['data_hora'])) ?></span>
                                                        <span style="font-size: 0.75rem; color: #94a3b8;">#<?= $ag['id'] ?></span>
                                                    </div>

                                                    <!-- Pet & Tutor -->
                                                    <div class="kanban-card-pet">
                                                        <?php if ($petFotoSrc): ?>
                                                            <img src="<?= htmlspecialchars($petFotoSrc) ?>" alt="Foto" class="kanban-pet-avatar">
                                                        <?php else: ?>
                                                            <div class="kanban-pet-avatar"><?= $petIconDefault ?></div>
                                                        <?php endif; ?>
                                                        <div class="kanban-pet-info">
                                                            <div class="kanban-pet-name"><?= htmlspecialchars($ag['pet_nome']) ?></div>
                                                            <div class="kanban-tutor-name">Tutor: <?= htmlspecialchars($ag['tutor_nome']) ?> (<?= htmlspecialchars($ag['tutor_telefone']) ?>)</div>
                                                        </div>
                                                    </div>

                                                    <!-- Serviço e Preço -->
                                                    <div class="kanban-card-service">
                                                        <span style="font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
                                                            <span><?= $sIcone ?></span>
                                                            <span><?= htmlspecialchars($ag['servico']) ?></span>
                                                        </span>
                                                        <span class="kanban-card-price">R$ <?= number_format($ag['valor'], 2, ',', '.') ?></span>
                                                    </div>

                                                    <!-- Observações (PopUp) -->
                                                    <?php if (!empty($ag['observacoes'])): ?>
                                                        <div class="kanban-card-obs" onclick='abrirModalEditarAgendamento(<?= $jsonAgData ?>)' title="Clique para abrir e ver/editar observações no PopUp">
                                                            ⭐ <strong>Obs:</strong> <?= htmlspecialchars(mb_strimwidth($ag['observacoes'], 0, 48, "...")) ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Controle de Mudança de Status do Atendimento -->
                                                    <div class="kanban-card-status-control">
                                                        <label class="kanban-status-label">Mudar Status:</label>
                                                        <select class="status-select-inline" onchange="mudarStatusAgendamento(<?= $ag['id'] ?>, this.value)">
                                                            <option value="Agendado" <?= $ag['status'] === 'Agendado' ? 'selected' : '' ?>>🔵 Agendado</option>
                                                            <option value="Em Andamento" <?= $ag['status'] === 'Em Andamento' ? 'selected' : '' ?>>🟡 Em Andamento</option>
                                                            <option value="Concluído" <?= $ag['status'] === 'Concluído' ? 'selected' : '' ?>>🟢 Concluído</option>
                                                            <option value="Cancelado" <?= $ag['status'] === 'Cancelado' ? 'selected' : '' ?>>🔴 Cancelado</option>
                                                        </select>
                                                    </div>

                                                    <!-- Botões de Ação do Card -->
                                                    <div class="kanban-card-btns">
                                                        <?php if ($ag['status'] === 'Agendado'): ?>
                                                            <a href="index.php?tab=agendamentos&mudar_status=Em+Andamento&agendamento_id=<?= $ag['id'] ?>" class="btn btn-warning btn-sm" title="Iniciar Atendimento">
                                                                ▶ Iniciar
                                                            </a>
                                                        <?php elseif ($ag['status'] === 'Em Andamento'): ?>
                                                            <a href="index.php?tab=agendamentos&mudar_status=Conclu%C3%ADdo&agendamento_id=<?= $ag['id'] ?>" class="btn btn-success btn-sm" title="Concluir Atendimento">
                                                                ✓ Concluir
                                                            </a>
                                                        <?php endif; ?>

                                                        <button type="button" class="btn btn-secondary btn-sm" onclick='abrirModalEditarAgendamento(<?= $jsonAgData ?>)' title="Ver detalhes completos e observações no PopUp">
                                                            🔍 PopUp
                                                        </button>
                                                        <a href="excluir_agendamento.php?id=<?= $ag['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja realmente excluir este agendamento?');" title="Excluir">
                                                            🗑️
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="kanban-empty">
                                                <div style="font-size: 1.8rem; margin-bottom: 0.3rem;">📋</div>
                                                Nenhum agendamento nesta coluna
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- ==============================================
                             VISÃO 2: TABELA DE AGENDAMENTOS
                             ============================================== -->
                        <div id="containerVisaoTabela" class="table-responsive" style="display: none; margin-top: 1.2rem;">
                            <table class="pet-table">
                                <thead>
                                    <tr>
                                        <th>Data / Horário</th>
                                        <th>Pet & Tutor</th>
                                        <th>Serviço</th>
                                        <th>Valor</th>
                                        <th>Status do Atendimento</th>
                                        <th>Observações</th>
                                        <th style="text-align: right;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($agendamentos) > 0): ?>
                                        <?php foreach ($agendamentos as $ag): 
                                            $dataFormatada = date('d/m/Y \à\s H:i', strtotime($ag['data_hora']));
                                            $valorFormatado = 'R$ ' . number_format($ag['valor'], 2, ',', '.');
                                            $petFotoSrc = !empty($ag['pet_foto']) && file_exists(__DIR__ . '/' . $ag['pet_foto']) ? $ag['pet_foto'] : null;
                                            $petIconDefault = str_contains(strtolower($ag['especie']), 'gato') ? '🐱' : '🐶';
                                            $sIcone = resolverIconeServico($ag['servico_icone'] ?? '', $ag['servico'] ?? '');

                                            $jsonAgData = htmlspecialchars(json_encode([
                                                'id' => $ag['id'],
                                                'pet_id' => $ag['pet_id'],
                                                'pet_nome' => $ag['pet_nome'],
                                                'tutor_nome' => $ag['tutor_nome'],
                                                'servico_id' => $ag['servico_id'],
                                                'servico' => $ag['servico'],
                                                'data_hora' => date('Y-m-d\TH:i', strtotime($ag['data_hora'])),
                                                'status' => $ag['status'],
                                                'valor' => $ag['valor'],
                                                'observacoes' => $ag['observacoes'] ?? ''
                                            ]), ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 700; color: #1e293b; font-size: 0.92rem;">
                                                        <?= $dataFormatada ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="pet-cell">
                                                        <?php if ($petFotoSrc): ?>
                                                            <img src="<?= htmlspecialchars($petFotoSrc) ?>" alt="Foto" class="pet-avatar">
                                                        <?php else: ?>
                                                            <div class="pet-avatar"><?= $petIconDefault ?></div>
                                                        <?php endif; ?>
                                                        <div class="pet-info-col">
                                                            <span class="pet-name-title"><?= htmlspecialchars($ag['pet_nome']) ?></span>
                                                            <span class="pet-sub-meta">Tutor: <?= htmlspecialchars($ag['tutor_nome']) ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="service-pill">
                                                        <span><?= $sIcone ?></span>
                                                        <span><?= htmlspecialchars($ag['servico']) ?></span>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span style="font-weight: 700; color: #047857; font-size: 0.92rem;">
                                                        <?= $valorFormatado ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <!-- Seletor Interativo de Status na Tabela -->
                                                    <select class="status-select-inline" onchange="mudarStatusAgendamento(<?= $ag['id'] ?>, this.value)">
                                                        <option value="Agendado" <?= $ag['status'] === 'Agendado' ? 'selected' : '' ?>>🔵 Agendado</option>
                                                        <option value="Em Andamento" <?= $ag['status'] === 'Em Andamento' ? 'selected' : '' ?>>🟡 Em Andamento</option>
                                                        <option value="Concluído" <?= $ag['status'] === 'Concluído' ? 'selected' : '' ?>>🟢 Concluído</option>
                                                        <option value="Cancelado" <?= $ag['status'] === 'Cancelado' ? 'selected' : '' ?>>🔴 Cancelado</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <?php if (!empty($ag['observacoes'])): ?>
                                                        <span style="font-size: 0.85rem; color: #b45309; cursor: pointer;" onclick='abrirModalEditarAgendamento(<?= $jsonAgData ?>)' title="Ver observações no PopUp">
                                                            ⭐ <?= htmlspecialchars(mb_strimwidth($ag['observacoes'], 0, 32, "...")) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color: #94a3b8; font-size: 0.82rem;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align: right; white-space: nowrap;">
                                                    <button type="button" class="btn btn-secondary btn-sm" onclick='abrirModalEditarAgendamento(<?= $jsonAgData ?>)' title="Abrir PopUp">
                                                        🔍 PopUp
                                                    </button>
                                                    <a href="excluir_agendamento.php?id=<?= $ag['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja realmente excluir este agendamento?');" title="Excluir">
                                                        🗑️
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
                                                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📅</div>
                                                <p style="font-size: 1rem; font-weight: 600; color: #64748b;">Nenhum agendamento encontrado.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ==========================================
                     ABA 4: ÁREA DE SERVIÇOS & PREÇOS (AC3)
                     ========================================== -->
                <section id="section-servicos" class="tab-content <?= $active_tab === 'servicos' ? 'active' : '' ?>">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <div>
                                <h2 class="dashboard-card-title">
                                    <span>✂️</span> Tabela de Serviços e Preços
                                </h2>
                                <p class="dashboard-card-subtitle">Cadastre os tipos de serviços e seus valores padrão que serão carregados automaticamente no agendamento.</p>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalNovoAgendamento()">
                                <span>📅</span> Ir para Agendamentos
                            </button>
                        </div>

                        <!-- Formulário de Novo Serviço -->
                        <div class="form-fieldset" style="margin-bottom: 2rem;">
                            <h3 class="form-fieldset-title">
                                <span>✨</span> Cadastrar Novo Serviço
                            </h3>
                            <form method="POST" action="index.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; align-items: flex-end;">
                                <input type="hidden" name="action" value="cadastrar_servico">

                                <div class="form-group">
                                    <label>Nome do Serviço: <span class="required">*</span></label>
                                    <input type="text" name="nome_servico" class="form-control" placeholder="Ex: Tosa na Tesoura, Táxi Dog..." required>
                                </div>

                                <div class="form-group">
                                    <label>Preço Padrão (R$): <span class="required">*</span></label>
                                    <input type="number" step="0.01" min="0" name="preco_servico" class="form-control" placeholder="0.00" required>
                                </div>

                                <div class="form-group">
                                    <label>Ícone / Emoji:</label>
                                    <select name="icone_servico" class="form-control">
                                        <option value="🛁">🛁 Banho</option>
                                        <option value="✂️">✂️ Tosa</option>
                                        <option value="🧼">🧼 Banho & Tosa</option>
                                        <option value="🩺">🩺 Consulta Veterinária</option>
                                        <option value="💉">💉 Vacinação</option>
                                        <option value="✨">✨ Hidratação</option>
                                        <option value="🐾">🐾 Higiene Geral</option>
                                        <option value="🚗">🚗 Táxi Dog</option>
                                        <option value="🦴">🦴 Adestramento / Hotel</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Duração Estimada (minutos):</label>
                                    <input type="number" name="duracao_minutos" class="form-control" placeholder="30" value="30">
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label>Descrição do Serviço:</label>
                                    <input type="text" name="descricao_servico" class="form-control" placeholder="Ex: Higienização completa com produtos de primeira linha e secagem rápida.">
                                </div>

                                <div style="grid-column: 1 / -1; display: flex; justify-content: flex-end;">
                                    <button type="submit" class="btn btn-primary">
                                        <span>💾</span> Salvar Serviço no Catálogo
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Catálogo de Serviços Cadastrados (Exibição em Cards) -->
                        <div>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.2rem; flex-wrap: wrap; gap: 0.8rem;">
                                <div>
                                    <h3 style="font-size: 1.2rem; font-weight: 700; color: #0f2d4a; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem;">
                                        <span>✂️</span> Serviços Disponíveis no Sistema (<?= $total_servicos ?>)
                                    </h3>
                                    <p style="font-size: 0.85rem; color: #64748b;">Cartões com preços e tempos de atendimento padrão para agendamento rápido.</p>
                                </div>
                                <span class="badge badge-other" style="font-size: 0.82rem; padding: 0.4rem 0.8rem;">
                                    <?= $total_servicos ?> Serviços Ativos
                                </span>
                            </div>

                            <div class="services-catalog-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.4rem;">
                                <?php foreach ($lista_servicos as $s): ?>
                                    <div class="service-item-card">
                                        <div class="service-card-top">
                                            <div class="service-item-header">
                                                <div class="service-item-icon"><?= resolverIconeServico($s['icone'] ?? '', $s['nome'] ?? '') ?></div>
                                                <span class="service-duration-badge">⏱️ <?= $s['duracao_minutos'] ?> min</span>
                                            </div>
                                            <h4 class="service-item-name"><?= htmlspecialchars($s['nome']) ?></h4>
                                            <p class="service-item-desc"><?= htmlspecialchars($s['descricao'] ?: 'Serviço profissional especializado para cães e gatos.') ?></p>
                                        </div>

                                        <div class="service-item-footer">
                                            <div>
                                                <span class="service-price-label">Valor Padrão</span>
                                                <div class="service-item-price">R$ <?= number_format($s['preco'], 2, ',', '.') ?></div>
                                            </div>

                                            <div style="display: flex; gap: 0.4rem; align-items: center;">
                                                <button type="button" class="btn btn-navy btn-sm" onclick="abrirModalNovoAgendamentoComServico(<?= $s['id'] ?>, <?= $s['preco'] ?>, '<?= addslashes($s['nome']) ?>')" title="Agendar este serviço para um pet">
                                                    📅 Agendar
                                                </button>
                                                <a href="excluir_servico.php?id=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja realmente remover o serviço <?= addslashes($s['nome']) ?> do catálogo?');" title="Excluir este serviço">
                                                    🗑️
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ==========================================
                     ABA 2: NOVO CADASTRO (FORMULÁRIO)
                     ========================================== -->
                <section id="section-cadastro" class="tab-content <?= $active_tab === 'cadastro' ? 'active' : '' ?>">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <div>
                                <h2 class="dashboard-card-title">
                                    <span>✨</span> Novo Cadastro Integrado
                                </h2>
                                <p class="dashboard-card-subtitle">Cadastre o tutor e seu animal de estimação em uma única etapa.</p>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="switchTab('pets')">
                                &larr; Ver Lista de Pets
                            </button>
                        </div>

                        <form method="POST" action="index.php" enctype="multipart/form-data" class="form-grid">
                            <input type="hidden" name="action" value="cadastrar">

                            <!-- Dados do Tutor -->
                            <div class="form-fieldset">
                                <h3 class="form-fieldset-title">
                                    <span>👤</span> Informações do Tutor
                                </h3>

                                <div class="form-group">
                                    <label>Nome Completo: <span class="required">*</span></label>
                                    <input type="text" name="nome_cliente" class="form-control" placeholder="Ex: Ana Souza da Silva" required>
                                </div>

                                <div class="form-group">
                                    <label>Telefone / WhatsApp: <span class="required">*</span></label>
                                    <input type="text" name="telefone" class="form-control" placeholder="Ex: (11) 98765-4321" required>
                                </div>

                                <div class="form-group">
                                    <label>E-mail: <span class="required">*</span></label>
                                    <input type="email" name="email" class="form-control" placeholder="Ex: ana.souza@gmail.com" required>
                                </div>
                            </div>

                            <!-- Dados do Pet -->
                            <div class="form-fieldset">
                                <h3 class="form-fieldset-title">
                                    <span>🐾</span> Informações do Pet
                                </h3>

                                <div class="form-group">
                                    <label>Nome do Pet: <span class="required">*</span></label>
                                    <input type="text" name="nome_pet" class="form-control" placeholder="Ex: Pipoca, Thor, Mel" required>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label>Espécie: <span class="required">*</span></label>
                                        <input type="text" name="especie" class="form-control" placeholder="Ex: Cão, Gato, Ave" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Raça:</label>
                                        <input type="text" name="raca" class="form-control" placeholder="Ex: Golden, Poodle, SRD">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Idade do Pet:</label>
                                    <input type="text" name="idade" class="form-control" placeholder="Ex: 2 anos, 6 meses">
                                </div>

                                <!-- Upload de Foto do Pet com Preview -->
                                <div class="form-group">
                                    <label>Foto do Pet:</label>
                                    <label class="photo-upload-box" for="fotoInput">
                                        <img id="previewCadastro" class="photo-preview-img" alt="Preview">
                                        <div id="uploadPlaceholder">
                                            <div class="photo-upload-icon">📷</div>
                                            <div class="photo-upload-text">Clique para selecionar a foto do pet</div>
                                            <div class="photo-upload-hint">Formatos suportados: PNG, JPG, WEBP</div>
                                        </div>
                                        <input type="file" id="fotoInput" name="foto" accept="image/*" style="display: none;" onchange="handleFilePreview(this, 'previewCadastro', 'uploadPlaceholder')">
                                    </label>
                                </div>

                                <!-- Preferência / Observações -->
                                <div class="form-group">
                                    <label>Preferências ou Cuidados Especiais:</label>
                                    <textarea name="observacoes" class="form-control" placeholder="Ex: Alérgico a perfumes, tem medo de secador de ar quente, gosta de carinho na orelha..."></textarea>
                                </div>
                            </div>

                            <div class="form-footer">
                                <button type="reset" class="btn btn-secondary" onclick="resetFilePreview('previewCadastro', 'uploadPlaceholder')">Limpar Campos</button>
                                <button type="submit" class="btn btn-primary">🐾 Finalizar e Salvar Cadastro</button>
                            </div>
                        </form>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- ========================================================
         MODAL POPUP DE DETALHES E EDIÇÃO (SUBSTITUI DETALHES.PHP)
         ======================================================== -->
    <div id="modalDetalhes" class="modal-overlay" onclick="handleBackdropClick(event)">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">
                    <span>🐾</span> Detalhes do Tutor & Pet
                </h3>
                <button type="button" class="modal-close-btn" onclick="fecharModalDetalhes()">✕</button>
            </div>

            <div class="modal-body">
                <!-- Banner Hero do Pet -->
                <div class="pet-hero-banner">
                    <img id="modalPetFotoImg" src="" alt="Pet" class="pet-hero-photo" style="display: none;">
                    <div id="modalPetFotoIcon" class="pet-hero-photo">🐶</div>
                    <div class="pet-hero-info">
                        <div class="pet-hero-name" id="modalPetNome">Nome do Pet</div>
                        <div class="pet-hero-badges">
                            <span class="pet-hero-badge" id="modalPetEspecie">Espécie</span>
                            <span class="pet-hero-badge" id="modalPetRaca">Raça</span>
                            <span class="pet-hero-badge" id="modalPetIdade">Idade</span>
                        </div>
                    </div>
                </div>

                <!-- MODO 1: VISUALIZAÇÃO DOS DETALHES -->
                <div id="modoVisualizacao">
                    <div class="detail-grid">
                        <!-- Tutor -->
                        <div class="detail-box">
                            <div class="detail-box-title">👤 Dados do Tutor</div>
                            <div class="detail-item">
                                <strong>NOME DO TUTOR</strong>
                                <span id="modalTutorNome" style="font-weight: 600;">-</span>
                            </div>
                            <div class="detail-item">
                                <strong>TELEFONE / WHATSAPP</strong>
                                <span id="modalTutorTelefone">-</span>
                            </div>
                            <div class="detail-item">
                                <strong>E-MAIL</strong>
                                <span id="modalTutorEmail">-</span>
                            </div>
                        </div>

                        <!-- Pet -->
                        <div class="detail-box">
                            <div class="detail-box-title">🐾 Perfil do Pet</div>
                            <div class="detail-item">
                                <strong>NOME DO ANIMAL</strong>
                                <span id="modalPetNomeInfo" style="font-weight: 600;">-</span>
                            </div>
                            <div class="detail-item">
                                <strong>ESPÉCIE E RAÇA</strong>
                                <span id="modalPetRacaInfo">-</span>
                            </div>
                            <div class="detail-item">
                                <strong>DATA DO CADASTRO</strong>
                                <span id="modalDataCadastro">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Preferências / Observações -->
                    <div class="detail-observation-box">
                        <strong>⭐ Preferências e Cuidados Especiais:</strong>
                        <p class="detail-observation-text" id="modalPetObservacoes">Nenhuma observação informada.</p>
                    </div>

                    <div class="modal-footer" style="padding: 0; border: none; margin-top: 1.5rem;">
                        <a id="modalBtnExcluir" href="#" class="btn btn-danger btn-sm" onclick="return confirm('Deseja realmente excluir este cadastro?');">
                            🗑️ Excluir Registro
                        </a>
                        <div style="display: flex; gap: 0.6rem;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="fecharModalDetalhes()">Fechar</button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="alternarModoEdicao(true)">✏️ Editar Cadastro</button>
                        </div>
                    </div>
                </div>

                <!-- MODO 2: EDIÇÃO COMPLETA NO PRÓPRIO POPUP -->
                <div id="modoEdicao" style="display: none;">
                    <form method="POST" action="index.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="atualizar">
                        <input type="hidden" name="cliente_id" id="editClienteId">
                        <input type="hidden" name="pet_id" id="editPetId">

                        <div class="detail-grid" style="margin-bottom: 1rem;">
                            <!-- Tutor Edit -->
                            <div class="form-fieldset" style="padding: 1.2rem;">
                                <div class="form-fieldset-title" style="font-size: 0.95rem;">👤 Editar Tutor</div>
                                <div class="form-group">
                                    <label>Nome:</label>
                                    <input type="text" name="nome_cliente" id="editTutorNome" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Telefone:</label>
                                    <input type="text" name="telefone" id="editTutorTelefone" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>E-mail:</label>
                                    <input type="email" name="email" id="editTutorEmail" class="form-control" required>
                                </div>
                            </div>

                            <!-- Pet Edit -->
                            <div class="form-fieldset" style="padding: 1.2rem;">
                                <div class="form-fieldset-title" style="font-size: 0.95rem;">🐾 Editar Pet</div>
                                <div class="form-group">
                                    <label>Nome do Pet:</label>
                                    <input type="text" name="nome_pet" id="editPetNome" class="form-control" required>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem;">
                                    <div class="form-group">
                                        <label>Espécie:</label>
                                        <input type="text" name="especie" id="editPetEspecie" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Raça:</label>
                                        <input type="text" name="raca" id="editPetRaca" class="form-control">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Idade:</label>
                                    <input type="text" name="idade" id="editPetIdade" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Foto Update e Obs -->
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Trocar Foto do Pet (opcional):</label>
                            <input type="file" name="foto_update" class="form-control" accept="image/*">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.4rem;">
                            <label>Preferências / Observações:</label>
                            <textarea name="observacoes" id="editPetObservacoes" class="form-control"></textarea>
                        </div>

                        <div class="modal-footer" style="padding: 0; border: none;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="alternarModoEdicao(false)">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm">💾 Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         MODAL DE AGENDAMENTO (NOVO / EDIÇÃO) - AC3
         ======================================================== -->
    <div id="modalAgendamento" class="modal-overlay" onclick="handleAgendamentoBackdropClick(event)">
        <div class="modal-card" style="max-width: 580px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modalAgendamentoTitle">
                    <span>📅</span> Novo Agendamento de Serviço
                </h3>
                <button type="button" class="modal-close-btn" onclick="fecharModalAgendamento()">✕</button>
            </div>

            <div class="modal-body">
                <form method="POST" action="index.php" id="formAgendamento">
                    <input type="hidden" name="action" id="agendamentoAction" value="cadastrar_agendamento">
                    <input type="hidden" name="agendamento_id" id="agendamentoId" value="">
                    <input type="hidden" name="servico" id="agendamentoServicoNome" value="">

                    <div class="form-group" style="margin-bottom: 1.1rem;">
                        <label>Pet / Tutor: <span class="required">*</span></label>
                        <select name="pet_id" id="agendamentoPetId" class="form-control" required>
                            <option value="">-- Selecione o Pet --</option>
                            <?php foreach ($lista_pets_select as $p): ?>
                                <option value="<?= $p['pet_id'] ?>">
                                    <?= htmlspecialchars($p['pet_nome']) ?> (<?= htmlspecialchars($p['especie']) ?><?= !empty($p['raca']) ? ' - ' . htmlspecialchars($p['raca']) : '' ?>) - Tutor: <?= htmlspecialchars($p['tutor_nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.1rem;">
                        <div class="form-group">
                            <label>Tipo de Serviço: <span class="required">*</span></label>
                            <select name="servico_id" id="agendamentoServicoId" class="form-control" onchange="atualizarPrecoServico()" required>
                                <option value="">-- Selecione o Serviço --</option>
                                <?php foreach ($lista_servicos as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-preco="<?= $s['preco'] ?>" data-nome="<?= htmlspecialchars($s['nome']) ?>">
                                        <?= resolverIconeServico($s['icone'] ?? '', $s['nome'] ?? '') ?> <?= htmlspecialchars($s['nome']) ?> — R$ <?= number_format($s['preco'], 2, ',', '.') ?> (<?= $s['duracao_minutos'] ?>m)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Valor do Serviço (R$):</label>
                            <input type="number" step="0.01" min="0" name="valor" id="agendamentoValor" class="form-control" placeholder="0.00" value="0.00">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.1rem;">
                        <div class="form-group">
                            <label>Data e Horário: <span class="required">*</span></label>
                            <input type="datetime-local" name="data_hora" id="agendamentoDataHora" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status" id="agendamentoStatus" class="form-control">
                                <option value="Agendado">🔵 Agendado</option>
                                <option value="Em Andamento">🟡 Em Andamento</option>
                                <option value="Concluído">🟢 Concluído</option>
                                <option value="Cancelado">🔴 Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campo de Observações / Recomendações mantido no PopUp -->
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Observações / Recomendações:</label>
                        <textarea name="observacoes" id="agendamentoObservacoes" class="form-control" placeholder="Ex: Cuidado com os ouvidos, usar shampoo neutro, tosa higiênica, comportamento arisco..."></textarea>
                    </div>

                    <div class="modal-footer" style="padding: 0; border: none;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="fecharModalAgendamento()">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="btnSalvarAgendamento">💾 Confirmar Agendamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===============================
         SCRIPTS INTERATIVOS DO DASHBOARD
         =============================== -->
    <script>
        // Alternância suave entre abas (Cadastro vs Pets vs Agendamentos vs Serviços)
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.sidebar-nav .nav-item').forEach(el => el.classList.remove('active'));

            const targetSection = document.getElementById('section-' + tabId);
            if (targetSection) {
                targetSection.classList.add('active');
            }

            if (tabId === 'cadastro') {
                const btn = document.getElementById('btnNavCadastro');
                if (btn) btn.classList.add('active');
            } else if (tabId === 'agendamentos') {
                const btn = document.getElementById('btnNavAgendamentos');
                if (btn) btn.classList.add('active');
            } else if (tabId === 'servicos') {
                const btn = document.getElementById('btnNavServicos');
                if (btn) btn.classList.add('active');
            } else {
                const btn = document.getElementById('btnNavPets');
                if (btn) btn.classList.add('active');
            }

            // Atualiza histórico na URL sem refresh
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        }

        // Alternância entre Visão em Colunas e Visão em Tabela
        function alternarVisaoAgendamentos(tipo) {
            const colunas = document.getElementById('containerVisaoColunas');
            const tabela = document.getElementById('containerVisaoTabela');
            const btnCol = document.getElementById('btnVisaoColunas');
            const btnTab = document.getElementById('btnVisaoTabela');

            if (tipo === 'colunas') {
                if (colunas) colunas.style.display = 'grid';
                if (tabela) tabela.style.display = 'none';
                if (btnCol) btnCol.classList.add('active');
                if (btnTab) btnTab.classList.remove('active');
            } else {
                if (colunas) colunas.style.display = 'none';
                if (tabela) tabela.style.display = 'block';
                if (btnCol) btnCol.classList.remove('active');
                if (btnTab) btnTab.classList.add('active');
            }
        }

        // Mudança de Status Interativa (Conforme o Atendimento)
        function mudarStatusAgendamento(agendamentoId, novoStatus) {
            window.location.href = 'index.php?tab=agendamentos&mudar_status=' + encodeURIComponent(novoStatus) + '&agendamento_id=' + agendamentoId;
        }

        // Atualização automática do preço ao escolher o serviço
        function atualizarPrecoServico() {
            const select = document.getElementById('agendamentoServicoId');
            const opt = select.options[select.selectedIndex];
            if (opt && opt.dataset.preco) {
                document.getElementById('agendamentoValor').value = parseFloat(opt.dataset.preco).toFixed(2);
                document.getElementById('agendamentoServicoNome').value = opt.dataset.nome || '';
            }
        }

        // Preview interativo da foto
        function handleFilePreview(input, imgId, placeholderId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById(imgId);
                    const placeholder = document.getElementById(placeholderId);
                    if (img && placeholder) {
                        img.src = e.target.result;
                        img.style.display = 'block';
                        placeholder.style.display = 'none';
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function resetFilePreview(imgId, placeholderId) {
            const img = document.getElementById(imgId);
            const placeholder = document.getElementById(placeholderId);
            if (img && placeholder) {
                img.src = '';
                img.style.display = 'none';
                placeholder.style.display = 'block';
            }
        }

        // PopUP Modal de Detalhes do Tutor & Pet
        let dadosAtuais = null;

        function abrirModalDetalhes(dados) {
            dadosAtuais = dados;

            const img = document.getElementById('modalPetFotoImg');
            const icon = document.getElementById('modalPetFotoIcon');
            if (dados.foto) {
                img.src = dados.foto;
                img.style.display = 'block';
                icon.style.display = 'none';
            } else {
                img.style.display = 'none';
                icon.style.display = 'flex';
                icon.textContent = (dados.especie && dados.especie.toLowerCase().includes('gato')) ? '🐱' : '🐶';
            }

            document.getElementById('modalPetNome').textContent = dados.pet_nome || 'Sem Nome';
            document.getElementById('modalPetEspecie').textContent = dados.especie || 'Espécie Indefinida';
            document.getElementById('modalPetRaca').textContent = dados.raca || 'SRD';
            document.getElementById('modalPetIdade').textContent = dados.idade ? 'Idade: ' + dados.idade : 'Idade não informada';

            document.getElementById('modalTutorNome').textContent = dados.cliente_nome;
            document.getElementById('modalTutorTelefone').textContent = dados.telefone;
            document.getElementById('modalTutorEmail').textContent = dados.email;

            document.getElementById('modalPetNomeInfo').textContent = dados.pet_nome || '-';
            document.getElementById('modalPetRacaInfo').textContent = (dados.especie || '') + ' - ' + (dados.raca || '');
            document.getElementById('modalDataCadastro').textContent = dados.data_cadastro;
            document.getElementById('modalPetObservacoes').textContent = dados.observacoes ? dados.observacoes : 'Nenhuma observação ou preferência registrada.';

            document.getElementById('modalBtnExcluir').href = 'excluir.php?id=' + dados.cliente_id;

            document.getElementById('editClienteId').value = dados.cliente_id;
            document.getElementById('editPetId').value = dados.pet_id;
            document.getElementById('editTutorNome').value = dados.cliente_nome;
            document.getElementById('editTutorTelefone').value = dados.telefone;
            document.getElementById('editTutorEmail').value = dados.email;

            document.getElementById('editPetNome').value = dados.pet_nome || '';
            document.getElementById('editPetEspecie').value = dados.especie || '';
            document.getElementById('editPetRaca').value = dados.raca !== 'Não informada' ? dados.raca : '';
            document.getElementById('editPetIdade').value = dados.idade !== 'Não informada' ? dados.idade : '';
            document.getElementById('editPetObservacoes').value = dados.observacoes || '';

            alternarModoEdicao(false);
            document.getElementById('modalDetalhes').classList.add('active');
        }

        function fecharModalDetalhes() {
            document.getElementById('modalDetalhes').classList.remove('active');
        }

        function alternarModoEdicao(emEdicao) {
            document.getElementById('modoVisualizacao').style.display = emEdicao ? 'none' : 'block';
            document.getElementById('modoEdicao').style.display = emEdicao ? 'block' : 'none';
        }

        function handleBackdropClick(e) {
            if (e.target.id === 'modalDetalhes') {
                fecharModalDetalhes();
            }
        }

        // ==========================================
        // MODAL DE AGENDAMENTOS (POPUP - AC3)
        // ==========================================
        function abrirModalNovoAgendamento() {
            document.getElementById('modalAgendamentoTitle').innerHTML = '<span>📅</span> Novo Agendamento de Serviço';
            document.getElementById('agendamentoAction').value = 'cadastrar_agendamento';
            document.getElementById('agendamentoId').value = '';
            document.getElementById('agendamentoPetId').value = '';
            
            // Seleciona o primeiro serviço e atualiza valor
            const selServ = document.getElementById('agendamentoServicoId');
            if (selServ && selServ.options.length > 1) {
                selServ.selectedIndex = 1;
                atualizarPrecoServico();
            } else {
                document.getElementById('agendamentoValor').value = '60.00';
            }

            document.getElementById('agendamentoStatus').value = 'Agendado';
            document.getElementById('agendamentoObservacoes').value = '';

            // Data/hora padrão: amanhã às 10:00
            const now = new Date();
            now.setDate(now.getDate() + 1);
            now.setHours(10, 0, 0, 0);
            const tzoffset = (new Date()).getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - tzoffset)).toISOString().slice(0, 16);
            document.getElementById('agendamentoDataHora').value = localISOTime;

            document.getElementById('btnSalvarAgendamento').textContent = '💾 Confirmar Agendamento';
            document.getElementById('modalAgendamento').classList.add('active');
        }

        function abrirModalNovoAgendamentoComServico(servicoId, servicoPreco, servicoNome) {
            abrirModalNovoAgendamento();
            const selServ = document.getElementById('agendamentoServicoId');
            if (selServ) {
                selServ.value = servicoId;
                atualizarPrecoServico();
            }
        }

        function abrirModalEditarAgendamento(dados) {
            document.getElementById('modalAgendamentoTitle').innerHTML = '<span>✏️</span> Detalhes & Edição do Agendamento #' + dados.id;
            document.getElementById('agendamentoAction').value = 'atualizar_agendamento';
            document.getElementById('agendamentoId').value = dados.id;
            document.getElementById('agendamentoPetId').value = dados.pet_id;

            // Busca opção correspondente por ID ou Nome
            const selServ = document.getElementById('agendamentoServicoId');
            let encontrado = false;
            if (dados.servico_id) {
                selServ.value = dados.servico_id;
                encontrado = (selServ.value == dados.servico_id);
            }
            if (!encontrado && dados.servico) {
                for (let i = 0; i < selServ.options.length; i++) {
                    if (selServ.options[i].dataset.nome && selServ.options[i].dataset.nome.toLowerCase() === dados.servico.toLowerCase()) {
                        selServ.selectedIndex = i;
                        encontrado = true;
                        break;
                    }
                }
            }

            document.getElementById('agendamentoServicoNome').value = dados.servico || '';
            document.getElementById('agendamentoValor').value = parseFloat(dados.valor).toFixed(2);
            document.getElementById('agendamentoDataHora').value = dados.data_hora;
            document.getElementById('agendamentoStatus').value = dados.status;
            document.getElementById('agendamentoObservacoes').value = dados.observacoes || '';

            document.getElementById('btnSalvarAgendamento').textContent = '💾 Salvar Alterações';
            document.getElementById('modalAgendamento').classList.add('active');
        }

        function fecharModalAgendamento() {
            document.getElementById('modalAgendamento').classList.remove('active');
        }

        function handleAgendamentoBackdropClick(e) {
            if (e.target.id === 'modalAgendamento') {
                fecharModalAgendamento();
            }
        }

        // Fechar com tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalDetalhes();
                fecharModalAgendamento();
            }
        });
    </script>
</body>
</html>
