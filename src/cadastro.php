<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$pagina_atual = 'cadastro';
$titulo_pagina = 'PetCare - Novo Cadastro';
$mensagem = '';
$toast_notificacao = null;

// ==========================================
// PROCESSAMENTO DO CADASTRO (TUTOR + PET)
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

        // 2. Insere Pet associado
        $stmt_pet = $pdo->prepare("INSERT INTO pets (cliente_id, nome, especie, raca, idade, foto, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_pet->execute([$cliente_id, $nome_pet, $especie, $raca, $idade, $foto_path, $observacoes]);

        $pdo->commit();
        
        // Redireciona com toast para a listagem de pets
        header("Location: pets.php?sucesso=cadastrado&pet=" . urlencode($nome_pet));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro:</strong> Não foi possível cadastrar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- ==============================================
     FORMULÁRIO DE NOVO CADASTRO INTEGRADO
     ============================================== -->
<div class="dashboard-card" style="max-width: 900px; margin: 0 auto;">
    <div class="dashboard-card-header">
        <div>
            <h2 class="dashboard-card-title">
                <span>✨</span> Novo Cadastro Integrado
            </h2>
            <p class="dashboard-card-subtitle">Cadastre o tutor e seu animal de estimação em uma única etapa para o sistema.</p>
        </div>
        <a href="pets.php" class="btn btn-secondary btn-sm">
            &larr; Ver Lista de Pets
        </a>
    </div>

    <form method="POST" action="cadastro.php" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="action" value="cadastrar">

        <!-- Coluna 1: Dados do Tutor -->
        <div class="form-fieldset">
            <h3 class="form-fieldset-title">
                <span>👤</span> Dados do Tutor / Cliente
            </h3>

            <div class="form-group">
                <label>Nome Completo: <span class="required">*</span></label>
                <input type="text" name="nome_cliente" class="form-control" placeholder="Ex: Juliana Costa" required>
            </div>

            <div class="form-group">
                <label>Telefone / WhatsApp: <span class="required">*</span></label>
                <input type="text" name="telefone" class="form-control" placeholder="(11) 98765-4321" required>
            </div>

            <div class="form-group">
                <label>E-mail: <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" placeholder="juliana.costa@email.com" required>
            </div>
        </div>

        <!-- Coluna 2: Dados do Animal -->
        <div class="form-fieldset">
            <h3 class="form-fieldset-title">
                <span>🐶</span> Dados do Animal (Pet)
            </h3>

            <div class="form-group">
                <label>Nome do Pet: <span class="required">*</span></label>
                <input type="text" name="nome_pet" class="form-control" placeholder="Ex: Thor, Mel, Nina..." required>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label>Espécie: <span class="required">*</span></label>
                    <select name="especie" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="Cão">🐶 Cão</option>
                        <option value="Gato">🐱 Gato</option>
                        <option value="Outro">🐾 Outro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Raça:</label>
                    <input type="text" name="raca" class="form-control" placeholder="Ex: Golden, Persa, SRD">
                </div>
            </div>

            <div class="form-group">
                <label>Idade Estimada:</label>
                <input type="text" name="idade" class="form-control" placeholder="Ex: 2 anos, 6 meses">
            </div>

            <div class="form-group">
                <label>Foto do Animal (opcional):</label>
                <div class="file-upload-wrapper">
                    <input type="file" name="foto" id="inputFotoCadastro" class="form-control" accept="image/*" onchange="handleFilePreview(this, 'previewFotoCadastro', 'placeholderFotoCadastro')">
                    <div id="previewContainerCadastro" class="file-preview-box">
                        <img id="previewFotoCadastro" src="" alt="Preview" style="display: none; width: 64px; height: 64px; border-radius: 8px; object-fit: cover;">
                        <span id="placeholderFotoCadastro" style="font-size: 0.8rem; color: #94a3b8;">Nenhuma foto selecionada</span>
                    </div>
                </div>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label style="display: flex; justify-content: space-between; align-items: center;">
                    <span>⭐ Observações / Recomendações Clínicas (Ficha Permanente do Pet):</span>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">Itens da lista permanente</span>
                </label>
                
                <div class="obs-list-builder-box">
                    <div class="obs-input-row">
                        <input type="text" id="inputNovoItemObsCadastro" class="form-control" placeholder="Ex: Cuidado com as orelhas ao tomar banho..." onkeydown="if(event.key === 'Enter'){ event.preventDefault(); adicionarItemObsCadastro(); }">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="adicionarItemObsCadastro()">+ Adicionar Item</button>
                    </div>

                    <!-- Chips de Sugestões Rápidas -->
                    <div class="obs-quick-chips">
                        <span class="chip-label">Sugestões rápidas:</span>
                        <button type="button" class="obs-chip" onclick="adicionarChipObsCadastro('Cuidado com as orelhas ao banhar')">👂 Orelhas</button>
                        <button type="button" class="obs-chip" onclick="adicionarChipObsCadastro('Alérgico a perfumes / cosméticos')">⚠️ Alergia</button>
                        <button type="button" class="obs-chip" onclick="adicionarChipObsCadastro('Sensível ao tocar nas patas')">🐾 Patas</button>
                        <button type="button" class="obs-chip" onclick="adicionarChipObsCadastro('Medo do barulho do secador')">💨 Secador</button>
                        <button type="button" class="obs-chip" onclick="adicionarChipObsCadastro('Dócil com outros animais')">🐶 Dócil</button>
                        <button type="button" class="obs-chip" onclick="adicionarChipObsCadastro('Arisco / Estressado com estranhos')">🐱 Arisco</button>
                        <button type="button" class="obs-chip" onclick="adicionarChipObsCadastro('Necessita medicação controlada')">💊 Medicação</button>
                    </div>

                    <!-- Lista de itens ativos adicionados -->
                    <div id="listaItensObsCadastroContainer" class="obs-items-active-list">
                        <p class="obs-items-empty" id="obsEmptyNoticeCadastro">Nenhum item adicionado à lista permanente ainda. Adicione itens acima ou clique nas sugestões.</p>
                    </div>

                    <!-- Textarea sincronizado para o envio do formulário via POST -->
                    <textarea name="observacoes" id="textareaObservacoesSync" class="form-control" style="display: none;"></textarea>
                </div>
            </div>
        </div>

        <!-- Botões de Ação do Formulário -->
        <div class="form-actions-full">
            <button type="reset" class="btn btn-secondary" onclick="resetFormularioCadastro()">
                Limpar Campos
            </button>
            <button type="submit" class="btn btn-primary">
                <span>💾</span> Finalizar Cadastro
            </button>
        </div>
    </form>
</div>

<script>
let itensObsCadastro = [];

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function renderizarItensObsCadastro() {
    const container = document.getElementById('listaItensObsCadastroContainer');
    const textarea = document.getElementById('textareaObservacoesSync');
    if (!container) return;

    if (itensObsCadastro.length === 0) {
        container.innerHTML = '<p class="obs-items-empty" id="obsEmptyNoticeCadastro">Nenhum item adicionado à lista permanente ainda. Adicione itens acima ou clique nas sugestões.</p>';
        textarea.value = '';
        return;
    }

    let html = '';
    itensObsCadastro.forEach((item, index) => {
        html += `
            <div class="obs-list-item-tag">
                <span>⭐ ${escapeHtml(item)}</span>
                <button type="button" class="obs-item-remove-btn" onclick="removerItemObsCadastro(${index})" title="Remover item">✕</button>
            </div>
        `;
    });
    container.innerHTML = html;
    textarea.value = itensObsCadastro.map(it => '• ' + it).join('\n');
}

function adicionarItemObsCadastro() {
    const input = document.getElementById('inputNovoItemObsCadastro');
    if (!input) return;
    const val = input.value.trim().replace(/^[•\-\*\s]+/, '');
    if (val) {
        itensObsCadastro.push(val);
        renderizarItensObsCadastro();
        input.value = '';
        input.focus();
    }
}

function adicionarChipObsCadastro(texto) {
    if (!itensObsCadastro.includes(texto)) {
        itensObsCadastro.push(texto);
        renderizarItensObsCadastro();
    }
}

function removerItemObsCadastro(index) {
    itensObsCadastro.splice(index, 1);
    renderizarItensObsCadastro();
}

function resetFormularioCadastro() {
    resetFilePreview('previewFotoCadastro', 'placeholderFotoCadastro');
    itensObsCadastro = [];
    renderizarItensObsCadastro();
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
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>
