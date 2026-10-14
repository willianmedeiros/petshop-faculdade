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
// 3. ESTATÍSTICAS PARA OS CARDS DO TOPO
// ==========================================
$total_pets = (int)$pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn();
$total_clientes = (int)$pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$total_caes = (int)$pdo->query("SELECT COUNT(*) FROM pets WHERE LOWER(especie) LIKE '%cão%' OR LOWER(especie) LIKE '%cao%' OR LOWER(especie) LIKE '%cachorro%'")->fetchColumn();
$total_gatos = (int)$pdo->query("SELECT COUNT(*) FROM pets WHERE LOWER(especie) LIKE '%gato%'")->fetchColumn();

// ==========================================
// 4. LISTAGEM COM FILTRO DE BUSCA (SELECT)
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare - Sistema de Gestão para PetShop</title>
    <link rel="stylesheet" href="style.css">
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

                <button type="button" class="nav-item" style="opacity: 0.65; cursor: not-allowed;" title="Módulo da AC3">
                    <span class="icon">📅</span>
                    <span>Agendamentos</span>
                    <span class="nav-badge" style="background: rgba(148, 163, 184, 0.2); color: #cbd5e1;">AC3</span>
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
                            <span class="stat-subtext">Cadastrados no sistema</span>
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
                        <div class="stat-icon-wrapper icon-emerald">🐕</div>
                        <div class="stat-info">
                            <span class="stat-label">Cães</span>
                            <span class="stat-value"><?= $total_caes ?></span>
                            <span class="stat-subtext">Espécie Canina</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon-wrapper icon-purple">🐱</div>
                        <div class="stat-info">
                            <span class="stat-label">Gatos & Outros</span>
                            <span class="stat-value"><?= $total_gatos ?></span>
                            <span class="stat-subtext">Felinos e exóticos</span>
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

    <!-- ===============================
         SCRIPTS INTERATIVOS DO DASHBOARD
         =============================== -->
    <script>
        // Alternância suave entre abas (Novo Cadastro vs Pets e Tutores)
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
            } else {
                const btn = document.getElementById('btnNavPets');
                if (btn) btn.classList.add('active');
            }

            // Atualiza histórico na URL sem refresh
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
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

        // PopUP Modal de Detalhes
        let dadosAtuais = null;

        function abrirModalDetalhes(dados) {
            dadosAtuais = dados;

            // Foto
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

            // Título e Badges
            document.getElementById('modalPetNome').textContent = dados.pet_nome || 'Sem Nome';
            document.getElementById('modalPetEspecie').textContent = dados.especie || 'Espécie Indefinida';
            document.getElementById('modalPetRaca').textContent = dados.raca || 'SRD';
            document.getElementById('modalPetIdade').textContent = dados.idade ? 'Idade: ' + dados.idade : 'Idade não informada';

            // Dados Tutor
            document.getElementById('modalTutorNome').textContent = dados.cliente_nome;
            document.getElementById('modalTutorTelefone').textContent = dados.telefone;
            document.getElementById('modalTutorEmail').textContent = dados.email;

            // Dados Pet
            document.getElementById('modalPetNomeInfo').textContent = dados.pet_nome || '-';
            document.getElementById('modalPetRacaInfo').textContent = (dados.especie || '') + ' - ' + (dados.raca || '');
            document.getElementById('modalDataCadastro').textContent = dados.data_cadastro;
            document.getElementById('modalPetObservacoes').textContent = dados.observacoes ? dados.observacoes : 'Nenhuma observação ou preferência registrada.';

            // Botão Excluir
            document.getElementById('modalBtnExcluir').href = 'excluir.php?id=' + dados.cliente_id;

            // Preenche campos do formulário de edição
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

            // Reseta para modo visualização
            alternarModoEdicao(false);

            // Abre modal
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

        // Fechar com tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalDetalhes();
            }
        });
    </script>
</body>
</html>
