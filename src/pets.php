<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$pagina_atual = 'pets';
$titulo_pagina = 'PetCare - Pets e Tutores';
$mensagem = '';
$toast_notificacao = null;

// Mensagens vindas via GET (redirecionamentos)
if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'cadastrado') {
    $nomePet = htmlspecialchars($_GET['pet'] ?? 'Pet');
    $toast_notificacao = [
        'msg' => "Pet <strong>{$nomePet}</strong> e Tutor cadastrados com sucesso!",
        'tipo' => 'success',
        'icone' => '✨'
    ];
}

// ==========================================
// ATUALIZAÇÃO DE PET E TUTOR (VIA MODAL)
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
            $toast_notificacao = [
                'msg' => "Dados do Pet <strong>" . htmlspecialchars($nome_pet) . "</strong> e Tutor atualizados com sucesso!",
                'tipo' => 'success',
                'icone' => '✅'
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// ==========================================
// FILTROS E BUSCA DE PETS
// ==========================================
$busca = trim($_GET['busca'] ?? '');
$filtro_especie = trim($_GET['especie'] ?? '');

$query = "SELECT c.id as cliente_id, c.nome as cliente_nome, c.telefone, c.email,
                 p.id as pet_id, p.nome as pet_nome, p.especie, p.raca, p.idade, p.foto, p.observacoes, p.data_cadastro
          FROM clientes c
          LEFT JOIN pets p ON c.id = p.cliente_id
          WHERE 1=1";

$params = [];

if ($busca !== '') {
    $query .= " AND (c.nome LIKE :busca1 OR p.nome LIKE :busca2 OR c.telefone LIKE :busca3)";
    $termo = "%$busca%";
    $params['busca1'] = $termo;
    $params['busca2'] = $termo;
    $params['busca3'] = $termo;
}

if ($filtro_especie !== '') {
    if ($filtro_especie === 'Cão') {
        $query .= " AND (LOWER(p.especie) LIKE '%cão%' OR LOWER(p.especie) LIKE '%cao%' OR LOWER(p.especie) LIKE '%cachorro%')";
    } elseif ($filtro_especie === 'Gato') {
        $query .= " AND LOWER(p.especie) LIKE '%gato%'";
    } elseif ($filtro_especie === 'Outro') {
        $query .= " AND LOWER(p.especie) NOT LIKE '%cão%' AND LOWER(p.especie) NOT LIKE '%cao%' AND LOWER(p.especie) NOT LIKE '%cachorro%' AND LOWER(p.especie) NOT LIKE '%gato%'";
    }
}

$query .= " ORDER BY c.id DESC";

$stmt_listagem = $pdo->prepare($query);
$stmt_listagem->execute($params);
$registros = $stmt_listagem->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div>
            <h2 class="dashboard-card-title">
                <span>🐶</span> Lista de Pets e Tutores Registrados
            </h2>
            <p class="dashboard-card-subtitle">Visualize o cadastro completo, preferências e histórico de tutores da clínica.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="cadastro.php" class="btn btn-primary btn-sm">
                <span>+</span> Novo Cadastro
            </a>
        </div>
    </div>

    <!-- Barra de Filtros e Busca -->
    <div class="table-filter-bar">
        <form method="GET" action="pets.php" class="search-box">
            <?php if ($filtro_especie !== ''): ?>
                <input type="hidden" name="especie" value="<?= htmlspecialchars($filtro_especie) ?>">
            <?php endif; ?>
            <input type="text" name="busca" placeholder="Buscar por pet, tutor ou telefone..." value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" class="btn btn-secondary btn-sm">Filtrar</button>
            <?php if ($busca !== ''): ?>
                <a href="pets.php<?= $filtro_especie ? '?especie=' . urlencode($filtro_especie) : '' ?>" class="btn btn-danger-solid btn-sm" title="Limpar busca">Limpar Busca</a>
            <?php endif; ?>
        </form>

        <div class="filter-pills">
            <a href="pets.php<?= $busca ? '?busca=' . urlencode($busca) : '' ?>" class="filter-pill <?= empty($filtro_especie) ? 'active' : '' ?>">
                Todos
            </a>
            <a href="pets.php?especie=Cão<?= $busca ? '&busca=' . urlencode($busca) : '' ?>" class="filter-pill <?= $filtro_especie === 'Cão' ? 'active' : '' ?>">
                🐶 Cães
            </a>
            <a href="pets.php?especie=Gato<?= $busca ? '&busca=' . urlencode($busca) : '' ?>" class="filter-pill <?= $filtro_especie === 'Gato' ? 'active' : '' ?>">
                🐱 Gatos
            </a>
            <a href="pets.php?especie=Outro<?= $busca ? '&busca=' . urlencode($busca) : '' ?>" class="filter-pill <?= $filtro_especie === 'Outro' ? 'active' : '' ?>">
                🐾 Outros
            </a>
        </div>
    </div>

    <!-- Tabela Principal de Pets -->
    <div class="table-responsive">
        <table class="pet-table">
            <thead>
                <tr>
                    <th>Animal (Pet)</th>
                    <th>Espécie / Raça</th>
                    <th>Idade</th>
                    <th>Tutor (Cliente)</th>
                    <th>Telefone / Contato</th>
                    <th>Data Registro</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($registros) > 0): ?>
                    <?php foreach ($registros as $row): 
                        $fotoSrc = !empty($row['foto']) && file_exists(__DIR__ . '/' . $row['foto']) ? $row['foto'] : null;
                        $especieLower = strtolower($row['especie'] ?? '');
                        
                        $isCat = str_contains($especieLower, 'gato');
                        $isDog = str_contains($especieLower, 'cão') || str_contains($especieLower, 'cao') || str_contains($especieLower, 'cachorro');
                        
                        $badgeClass = 'badge-other';
                        $petIconDefault = '🐾';
                        if ($isDog) {
                            $badgeClass = 'badge-dog';
                            $petIconDefault = '🐶';
                        } elseif ($isCat) {
                            $badgeClass = 'badge-cat';
                            $petIconDefault = '🐱';
                        }

                        $dataCadastroFormatada = !empty($row['data_cadastro']) ? date('d/m/Y', strtotime($row['data_cadastro'])) : 'Recente';

                        $jsonData = htmlspecialchars(json_encode([
                            'cliente_id' => $row['cliente_id'],
                            'cliente_nome' => $row['cliente_nome'],
                            'telefone' => $row['telefone'],
                            'email' => $row['email'],
                            'pet_id' => $row['pet_id'],
                            'pet_nome' => $row['pet_nome'],
                            'especie' => $row['especie'],
                            'raca' => !empty($row['raca']) ? $row['raca'] : 'Não informada',
                            'idade' => !empty($row['idade']) ? $row['idade'] : 'Não informada',
                            'foto' => $fotoSrc,
                            'observacoes' => $row['observacoes'] ?? '',
                            'data_cadastro' => $dataCadastroFormatada
                        ]), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr>
                            <td>
                                <div class="pet-cell">
                                    <?php if ($fotoSrc): ?>
                                        <img src="<?= htmlspecialchars($fotoSrc) ?>" alt="Foto" class="pet-avatar">
                                    <?php else: ?>
                                        <div class="pet-avatar"><?= $petIconDefault ?></div>
                                    <?php endif; ?>
                                    <div class="pet-info-col">
                                        <span class="pet-name-title"><?= htmlspecialchars($row['pet_nome'] ?? 'Sem nome') ?></span>
                                        <span class="pet-sub-meta">#PET-<?= $row['pet_id'] ?? '-' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.2rem; align-items: flex-start;">
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars($row['especie'] ?? 'Outro') ?>
                                    </span>
                                    <span style="font-size: 0.8rem; color: #64748b; margin-left: 0.2rem;">
                                        <?= !empty($row['raca']) ? htmlspecialchars($row['raca']) : 'SRD' ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; color: #475569;">
                                    <?= !empty($row['idade']) ? htmlspecialchars($row['idade']) : '-' ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: #1e293b;"><?= htmlspecialchars($row['cliente_nome']) ?></strong>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 0.85rem; color: #0284c7; font-weight: 500;">
                                        <?= htmlspecialchars($row['telefone']) ?>
                                    </span>
                                    <span style="font-size: 0.75rem; color: #94a3b8;">
                                        <?= htmlspecialchars($row['email']) ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; color: #64748b;">
                                    <?= $dataCadastroFormatada ?>
                                </span>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick='abrirModalDetalhes(<?= $jsonData ?>)' title="Ver Detalhes e Editar">
                                    🔍 Detalhes
                                </button>
                                <a href="excluir.php?id=<?= $row['cliente_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja realmente excluir este cadastro (Tutor e Pet)?');" title="Excluir">
                                    🗑️
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-state-icon">🐾</div>
                            <p style="font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Nenhum animal ou tutor encontrado.</p>
                            <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 1rem;">
                                <?= $busca ? 'Tente ajustar os termos da sua busca.' : 'Comece cadastrando o primeiro pet no sistema.' ?>
                            </p>
                            <?php if ($busca): ?>
                                <a href="pets.php" class="btn btn-secondary btn-sm">Limpar Filtro</a>
                            <?php else: ?>
                                <a href="cadastro.php" class="btn btn-primary btn-sm">+ Cadastrar Novo Pet</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================================
     MODAL POPUP DE DETALHES E EDIÇÃO DE TUTOR & PET
     ======================================================== -->
<div id="modalDetalhes" class="modal-overlay" onclick="handleBackdropClick(event)">
    <div class="modal-card modal-card-pet-details">
        <div class="modal-header">
            <h3 class="modal-title">
                <span>🐾</span> Ficha Cadastral do Pet & Tutor
            </h3>
            <button type="button" class="modal-close-btn" onclick="fecharModalDetalhes()">✕</button>
        </div>

        <div class="modal-body">
            <!-- MODO VISUALIZAÇÃO -->
            <div id="modoVisualizacao">
                
                <!-- Hero Banner do Pet -->
                <div class="pet-hero-banner">
                    <img id="modalPetFotoImg" src="" alt="Foto" class="pet-hero-photo" style="display: none;">
                    <div id="modalPetFotoIcon" class="pet-hero-photo" style="display: flex;">🐶</div>

                    <div class="pet-hero-info">
                        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                            <h3 id="modalPetNome" class="pet-hero-name">Nome do Pet</h3>
                            <span id="modalPetIdBadge" class="pet-id-pill">#PET-0</span>
                        </div>
                        <div class="pet-hero-badges">
                            <span id="modalPetEspecie" class="badge badge-dog">Espécie</span>
                            <span id="modalPetRaca" class="pet-hero-badge">Raça</span>
                            <span id="modalPetIdade" class="pet-hero-badge">Idade</span>
                            <span class="pet-hero-badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399; font-weight: 700;">✓ Ativo</span>
                        </div>
                    </div>
                </div>

                <!-- Grid de 2 Seções Lado a Lado (Tutor & Registro) -->
                <div class="pet-details-sections-grid">
                    
                    <!-- Seção 1: Tutor Responsável -->
                    <div class="pet-details-card">
                        <div class="pet-details-card-header">
                            <span>👤</span>
                            <h4>Tutor Responsável</h4>
                        </div>
                        <div class="pet-details-card-body">
                            <div class="detail-field">
                                <span class="detail-field-label">Nome Completo</span>
                                <span class="detail-field-val" id="modalTutorNome">-</span>
                            </div>
                            <div class="detail-field">
                                <span class="detail-field-label">Telefone / WhatsApp</span>
                                <span class="detail-field-val highlight-phone" id="modalTutorTelefone">-</span>
                            </div>
                            <div class="detail-field">
                                <span class="detail-field-label">E-mail</span>
                                <span class="detail-field-val" id="modalTutorEmail">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Seção 2: Dados de Registro do Pet -->
                    <div class="pet-details-card">
                        <div class="pet-details-card-header">
                            <span>📋</span>
                            <h4>Dados de Registro</h4>
                        </div>
                        <div class="pet-details-card-body">
                            <div class="detail-field">
                                <span class="detail-field-label">Nome no Registro</span>
                                <span class="detail-field-val" id="modalPetNomeInfo">-</span>
                            </div>
                            <div class="detail-field">
                                <span class="detail-field-label">Espécie & Linhagem</span>
                                <span class="detail-field-val" id="modalPetRacaInfo">-</span>
                            </div>
                            <div class="detail-field">
                                <span class="detail-field-label">Data de Cadastro</span>
                                <span class="detail-field-val" id="modalDataCadastro">-</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Seção 3: Observações & Recomendações Clínicas em Lista (Amarelo Clarinho) -->
                <div class="pet-obs-section-card">
                    <div class="pet-obs-section-header">
                        <span style="font-size: 1.15rem;">⭐</span>
                        <h4>Observações & Recomendações Clínicas (Ficha Permanente)</h4>
                    </div>
                    <div id="modalPetObservacoesContainer" class="pet-obs-section-body">
                        <!-- Itens da lista injetados via JS -->
                    </div>
                </div>

                <!-- Footer de Ações do Modal -->
                <div class="modal-footer-actions">
                    <a href="#" id="modalBtnExcluir" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir permanentemente este cadastro?');">
                        <span>🗑️</span> Excluir Cadastro
                    </a>
                    <button type="button" class="btn btn-primary btn-sm" onclick="alternarModoEdicao(true)">
                        <span>✏️</span> Editar Informações
                    </button>
                </div>
            </div>

            <!-- MODO EDIÇÃO -->
            <div id="modoEdicao" style="display: none;">
                <form method="POST" action="pets.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="atualizar">
                    <input type="hidden" name="cliente_id" id="editClienteId">
                    <input type="hidden" name="pet_id" id="editPetId">

                    <h4 style="font-size: 0.95rem; font-weight: 700; color: #0b1f33; margin-bottom: 0.8rem; display: flex; align-items: center; gap: 0.4rem;">
                        <span>👤</span> Dados do Tutor
                    </h4>

                    <div class="form-group" style="margin-bottom: 0.8rem;">
                        <label>Nome do Tutor: <span class="required">*</span></label>
                        <input type="text" name="nome_cliente" id="editTutorNome" class="form-control" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom: 1.2rem;">
                        <div class="form-group">
                            <label>Telefone: <span class="required">*</span></label>
                            <input type="text" name="telefone" id="editTutorTelefone" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>E-mail: <span class="required">*</span></label>
                            <input type="email" name="email" id="editTutorEmail" class="form-control" required>
                        </div>
                    </div>

                    <h4 style="font-size: 0.95rem; font-weight: 700; color: #0b1f33; margin-bottom: 0.8rem; display: flex; align-items: center; gap: 0.4rem;">
                        <span>🐶</span> Dados do Animal
                    </h4>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.8rem; margin-bottom: 0.8rem;">
                        <div class="form-group">
                            <label>Nome do Pet: <span class="required">*</span></label>
                            <input type="text" name="nome_pet" id="editPetNome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Espécie: <span class="required">*</span></label>
                            <select name="especie" id="editPetEspecie" class="form-control" required>
                                <option value="Cão">🐶 Cão</option>
                                <option value="Gato">🐱 Gato</option>
                                <option value="Outro">🐾 Outro</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom: 0.8rem;">
                        <div class="form-group">
                            <label>Raça:</label>
                            <input type="text" name="raca" id="editPetRaca" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Idade:</label>
                            <input type="text" name="idade" id="editPetIdade" class="form-control">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Trocar Foto do Pet (opcional):</label>
                        <input type="file" name="foto_update" class="form-control" accept="image/*">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.4rem;">
                        <label style="display: flex; justify-content: space-between; align-items: center;">
                            <span>⭐ Preferências / Observações Clínicas (Ficha do Pet):</span>
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">Itens da lista permanente</span>
                        </label>
                        <div class="obs-list-builder-box">
                            <div class="obs-input-row">
                                <input type="text" id="inputNovoItemObsEdicao" class="form-control" placeholder="Ex: Cuidado com as orelhas ao tomar banho..." onkeydown="if(event.key === 'Enter'){ event.preventDefault(); adicionarItemObsEdicao(); }">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="adicionarItemObsEdicao()">+ Adicionar Item</button>
                            </div>
                            <!-- Chips Rápidos no Modo Edição -->
                            <div class="obs-quick-chips">
                                <span class="chip-label">Sugestões:</span>
                                <button type="button" class="obs-chip" onclick="adicionarChipObsEdicao('Cuidado com as orelhas ao banhar')">👂 Orelhas</button>
                                <button type="button" class="obs-chip" onclick="adicionarChipObsEdicao('Alérgico a perfumes / cosméticos')">⚠️ Alergia</button>
                                <button type="button" class="obs-chip" onclick="adicionarChipObsEdicao('Sensível ao tocar nas patas')">🐾 Patas</button>
                                <button type="button" class="obs-chip" onclick="adicionarChipObsEdicao('Medo do barulho do secador')">💨 Secador</button>
                                <button type="button" class="obs-chip" onclick="adicionarChipObsEdicao('Dócil com outros animais')">🐶 Dócil</button>
                                <button type="button" class="obs-chip" onclick="adicionarChipObsEdicao('Arisco / Estressado com estranhos')">🐱 Arisco</button>
                            </div>
                            <div id="listaItensObsEdicaoContainer" class="obs-items-active-list">
                                <!-- Itens dinâmicos -->
                            </div>
                            <textarea name="observacoes" id="editPetObservacoes" class="form-control" style="display: none;"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer-edit" style="position: sticky; bottom: -1.6rem; background: #ffffff; padding: 1rem 0 0.5rem; margin-top: 1.2rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 0.8rem; z-index: 10;">
                        <button type="button" class="btn btn-secondary" onclick="alternarModoEdicao(false)">Cancelar</button>
                        <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let dadosAtuais = null;
let itensObsEdicao = [];

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

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
    document.getElementById('modalPetIdBadge').textContent = '#PET-' + (dados.pet_id || '0');
    document.getElementById('modalPetEspecie').textContent = dados.especie || 'Espécie Indefinida';
    document.getElementById('modalPetRaca').textContent = dados.raca || 'SRD';
    document.getElementById('modalPetIdade').textContent = dados.idade ? 'Idade: ' + dados.idade : 'Idade não informada';

    document.getElementById('modalTutorNome').textContent = dados.cliente_nome;
    document.getElementById('modalTutorTelefone').textContent = dados.telefone;
    document.getElementById('modalTutorEmail').textContent = dados.email;

    document.getElementById('modalPetNomeInfo').textContent = dados.pet_nome || '-';
    document.getElementById('modalPetRacaInfo').textContent = (dados.especie || '') + (dados.raca ? ' - ' + dados.raca : '');
    document.getElementById('modalDataCadastro').textContent = dados.data_cadastro;

    // Renderiza a lista de observações permanente do Pet (amarelo clarinho)
    const obsContainer = document.getElementById('modalPetObservacoesContainer');
    if (dados.observacoes && dados.observacoes.trim() !== '') {
        const lines = dados.observacoes.split(/\r?\n|;/).map(s => s.trim()).filter(s => s.length > 0);
        if (lines.length > 0) {
            let listHtml = '<ul class="pet-obs-list-styled">';
            lines.forEach(item => {
                const cleanItem = item.replace(/^[•\-\*\s]+/, '');
                listHtml += `<li><span class="obs-item-bullet">⭐</span> <span class="obs-item-text">${escapeHtml(cleanItem)}</span></li>`;
            });
            listHtml += '</ul>';
            obsContainer.innerHTML = listHtml;
        } else {
            obsContainer.innerHTML = '<div class="pet-obs-empty"><span>ℹ️</span> Nenhuma recomendação clínica registrada na ficha do animal.</div>';
        }
    } else {
        obsContainer.innerHTML = '<div class="pet-obs-empty"><span>ℹ️</span> Nenhuma recomendação clínica registrada na ficha do animal.</div>';
    }

    document.getElementById('modalBtnExcluir').href = 'excluir.php?id=' + dados.cliente_id;

    // Preenche dados do formulário de edição
    document.getElementById('editClienteId').value = dados.cliente_id;
    document.getElementById('editPetId').value = dados.pet_id;
    document.getElementById('editTutorNome').value = dados.cliente_nome;
    document.getElementById('editTutorTelefone').value = dados.telefone;
    document.getElementById('editTutorEmail').value = dados.email;

    document.getElementById('editPetNome').value = dados.pet_nome || '';
    document.getElementById('editPetEspecie').value = dados.especie || '';
    document.getElementById('editPetRaca').value = dados.raca !== 'Não informada' ? dados.raca : '';
    document.getElementById('editPetIdade').value = dados.idade !== 'Não informada' ? dados.idade : '';
    
    // Inicializa a lista de observações no modo edição
    itensObsEdicao = [];
    if (dados.observacoes && dados.observacoes.trim() !== '') {
        const lines = dados.observacoes.split(/\r?\n|;/).map(s => s.trim()).filter(s => s.length > 0);
        lines.forEach(item => {
            const clean = item.replace(/^[•\-\*\s]+/, '');
            if (clean) itensObsEdicao.push(clean);
        });
    }
    renderizarItensObsEdicao();

    alternarModoEdicao(false);
    document.getElementById('modalDetalhes').classList.add('active');
}

// Funções da Lista de Observações no Modo de Edição
function renderizarItensObsEdicao() {
    const container = document.getElementById('listaItensObsEdicaoContainer');
    const textarea = document.getElementById('editPetObservacoes');
    if (!container) return;

    if (itensObsEdicao.length === 0) {
        container.innerHTML = '<p class="obs-items-empty">Nenhum item na lista permanente. Adicione novos itens acima.</p>';
        textarea.value = '';
        return;
    }

    let html = '';
    itensObsEdicao.forEach((item, index) => {
        html += `
            <div class="obs-list-item-tag">
                <span>⭐ ${escapeHtml(item)}</span>
                <button type="button" class="obs-item-remove-btn" onclick="removerItemObsEdicao(${index})" title="Remover item">✕</button>
            </div>
        `;
    });
    container.innerHTML = html;
    textarea.value = itensObsEdicao.map(it => '• ' + it).join('\n');
}

function adicionarItemObsEdicao() {
    const input = document.getElementById('inputNovoItemObsEdicao');
    if (!input) return;
    const val = input.value.trim().replace(/^[•\-\*\s]+/, '');
    if (val) {
        itensObsEdicao.push(val);
        renderizarItensObsEdicao();
        input.value = '';
        input.focus();
    }
}

function adicionarChipObsEdicao(texto) {
    if (!itensObsEdicao.includes(texto)) {
        itensObsEdicao.push(texto);
        renderizarItensObsEdicao();
    }
}

function removerItemObsEdicao(index) {
    itensObsEdicao.splice(index, 1);
    renderizarItensObsEdicao();
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
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>
