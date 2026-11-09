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
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title">
                <span>📋</span> Ficha Cadastral do Paciente
            </h3>
            <button type="button" class="modal-close-btn" onclick="fecharModalDetalhes()">✕</button>
        </div>

        <div class="modal-body">
            <!-- MODO VISUALIZAÇÃO -->
            <div id="modoVisualizacao">
                <div class="pet-profile-header">
                    <img id="modalPetFotoImg" src="" alt="Foto" class="pet-profile-avatar" style="display: none;">
                    <div id="modalPetFotoIcon" class="pet-profile-avatar" style="display: flex;">🐶</div>

                    <div class="pet-profile-details">
                        <h4 id="modalPetNome" class="pet-profile-name">Nome do Pet</h4>
                        <div class="pet-profile-meta">
                            <span id="modalPetEspecie" class="badge badge-dog">Espécie</span>
                            <span id="modalPetRaca" style="color: #64748b; font-size: 0.85rem;">Raça</span>
                            <span style="color: #cbd5e1;">•</span>
                            <span id="modalPetIdade" style="color: #64748b; font-size: 0.85rem;">Idade</span>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <div class="info-section-title">
                        <span>👤</span> Tutor Responsável
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-item-label">Nome Completo</span>
                            <span class="info-item-value" id="modalTutorNome">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-item-label">Telefone / WhatsApp</span>
                            <span class="info-item-value" id="modalTutorTelefone">-</span>
                        </div>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <span class="info-item-label">E-mail</span>
                            <span class="info-item-value" id="modalTutorEmail">-</span>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <div class="info-section-title">
                        <span>📝</span> Detalhes & Observações do Animal
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-item-label">Nome de Registro</span>
                            <span class="info-item-value" id="modalPetNomeInfo">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-item-label">Espécie / Linhagem</span>
                            <span class="info-item-value" id="modalPetRacaInfo">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-item-label">Data de Cadastro</span>
                            <span class="info-item-value" id="modalDataCadastro">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-item-label">Situação Cadastral</span>
                            <span class="info-item-value" style="color: #10b981; font-weight: 700;">Ativo</span>
                        </div>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <span class="info-item-label">Preferências / Recomendações Clínicas</span>
                            <div class="info-item-value" id="modalPetObservacoes" style="background: #f8fafc; padding: 0.6rem 0.8rem; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.85rem; font-weight: 500;">
                                Nenhuma observação registrada.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="padding: 0; border: none; margin-top: 1.5rem;">
                    <a href="#" id="modalBtnExcluir" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir permanentemente este cadastro?');">
                        <span>🗑️</span> Excluir
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
                        <label>Preferências / Observações:</label>
                        <textarea name="observacoes" id="editPetObservacoes" class="form-control" rows="3"></textarea>
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

<script>
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
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>
