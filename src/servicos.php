<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$pagina_atual = 'servicos';
$titulo_pagina = 'PetCare - Catálogo de Serviços & Preços';
$mensagem = '';
$toast_notificacao = null;

// ==========================================
// AÇÕES DO BACKEND (SERVIÇOS)
// ==========================================

// 1. Cadastrar Novo Serviço
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
            $toast_notificacao = [
                'msg' => 'Serviço <strong>' . htmlspecialchars($nome) . '</strong> adicionado ao catálogo!',
                'tipo' => 'success',
                'icone' => '✨'
            ];
        } catch (Exception $e) {
            $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro ao cadastrar serviço:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Atenção:</strong> Informe o nome e valor do serviço.</div>";
    }
}

// 2. Atualizar Serviço Existente (Edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_servico') {
    $servico_id = (int)($_POST['servico_id'] ?? 0);
    $nome = trim($_POST['nome_servico'] ?? '');
    $preco = floatval(str_replace(',', '.', $_POST['preco_servico'] ?? '0.00'));
    $descricao = trim($_POST['descricao_servico'] ?? '');
    $icone = trim($_POST['icone_servico'] ?? '🐾');
    $duracao = (int)($_POST['duracao_minutos'] ?? 30);

    if ($servico_id > 0 && !empty($nome) && $preco >= 0) {
        try {
            $stmt_up_serv = $pdo->prepare("UPDATE servicos SET nome = ?, preco = ?, descricao = ?, icone = ?, duracao_minutos = ? WHERE id = ?");
            $stmt_up_serv->execute([$nome, $preco, $descricao, $icone, $duracao, $servico_id]);
            $toast_notificacao = [
                'msg' => 'Serviço <strong>' . htmlspecialchars($nome) . '</strong> atualizado com sucesso!',
                'tipo' => 'success',
                'icone' => '✏️'
            ];
        } catch (Exception $e) {
            $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro ao atualizar serviço:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Atenção:</strong> Informe o nome e valor do serviço.</div>";
    }
}

// Consulta de todos os serviços
$stmt_servicos = $pdo->query("SELECT * FROM servicos ORDER BY nome ASC");
$lista_servicos = $stmt_servicos->fetchAll();
$total_servicos = count($lista_servicos);

include __DIR__ . '/includes/header.php';
?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div>
            <h2 class="dashboard-card-title">
                <span>✂️</span> Tabela de Serviços e Preços
            </h2>
            <p class="dashboard-card-subtitle">Cadastre os tipos de serviços e seus valores padrão que serão carregados automaticamente no agendamento.</p>
        </div>
        <a href="agendamentos.php" class="btn btn-primary btn-sm">
            <span>📅</span> Ir para Agendamentos
        </a>
    </div>

    <!-- Formulário de Novo Serviço -->
    <div class="form-fieldset" style="margin-bottom: 2rem;">
        <h3 class="form-fieldset-title">
            <span>✨</span> Cadastrar Novo Serviço
        </h3>
        <form method="POST" action="servicos.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; align-items: flex-end;">
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
                    <option value="🐾">🐾 Genérico / Outro</option>
                    <option value="🛁">🛁 Banho</option>
                    <option value="✂️">✂️ Tosa</option>
                    <option value="🧼">🧼 Banho & Tosa</option>
                    <option value="🩺">🩺 Consulta Veterinária</option>
                    <option value="💉">💉 Vacinação</option>
                    <option value="✨">✨ Hidratação</option>
                    <option value="🚗">🚗 Táxi Dog</option>
                    <option value="🦴">🦴 Hospedagem / Hotel</option>
                    <option value="🐕">🐕 Passeio / Adestramento</option>
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
                        <div class="service-price-box">
                            <span class="service-price-label">Valor Padrão</span>
                            <div class="service-item-price"><span class="price-currency">R$</span><?= number_format($s['preco'], 2, ',', '.') ?></div>
                        </div>

                        <div style="display: flex; gap: 0.4rem; align-items: center; flex-shrink: 0;">
                            <button type="button" class="btn btn-navy btn-sm" onclick="abrirModalNovoAgendamentoComServico(<?= $s['id'] ?>, <?= $s['preco'] ?>, '<?= addslashes($s['nome']) ?>')" title="Agendar este serviço para um pet">
                                📅 Agendar
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick='abrirModalEditarServico(<?= htmlspecialchars(json_encode([
                                "id" => $s["id"],
                                "nome" => $s["nome"],
                                "preco" => (float)$s["preco"],
                                "duracao_minutos" => (int)$s["duracao_minutos"],
                                "icone" => $s["icone"] ?: "🐾",
                                "descricao" => $s["descricao"] ?? ""
                            ]), ENT_QUOTES, "UTF-8") ?>)' title="Editar este serviço">
                                ✏️
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

<!-- ========================================================
     MODAL DE EDIÇÃO DE SERVIÇO DO CATÁLOGO - AC3
     ======================================================== -->
<div id="modalEditarServico" class="modal-overlay" onclick="handleServicoBackdropClick(event)">
    <div class="modal-card" style="max-width: 520px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <span>✏️</span> Editar Serviço do Catálogo
            </h3>
            <button type="button" class="modal-close-btn" onclick="fecharModalEditarServico()">✕</button>
        </div>

        <div class="modal-body">
            <form method="POST" action="servicos.php" id="formEditarServico">
                <input type="hidden" name="action" value="atualizar_servico">
                <input type="hidden" name="servico_id" id="editServicoId" value="">

                <div class="form-group" style="margin-bottom: 1.1rem;">
                    <label>Nome do Serviço: <span class="required">*</span></label>
                    <input type="text" name="nome_servico" id="editServicoNome" class="form-control" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.1rem;">
                    <div class="form-group">
                        <label>Preço Padrão (R$): <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" name="preco_servico" id="editServicoPreco" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Duração (minutos):</label>
                        <input type="number" name="duracao_minutos" id="editServicoDuracao" class="form-control" placeholder="30" value="30">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.1rem;">
                    <label>Ícone / Emoji:</label>
                    <select name="icone_servico" id="editServicoIcone" class="form-control">
                        <option value="🐾">🐾 Genérico / Outro</option>
                        <option value="🛁">🛁 Banho</option>
                        <option value="✂️">✂️ Tosa</option>
                        <option value="🧼">🧼 Banho & Tosa</option>
                        <option value="🩺">🩺 Consulta Veterinária</option>
                        <option value="💉">💉 Vacinação</option>
                        <option value="✨">✨ Hidratação</option>
                        <option value="🚗">🚗 Táxi Dog</option>
                        <option value="🦴">🦴 Hospedagem / Hotel</option>
                        <option value="🐕">🐕 Passeio / Adestramento</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1.4rem;">
                    <label>Descrição do Serviço:</label>
                    <textarea name="descricao_servico" id="editServicoDescricao" class="form-control" rows="3"></textarea>
                </div>

                <div class="modal-footer" style="padding: 0; border: none;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="fecharModalEditarServico()">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">💾 Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalEditarServico(servico) {
    document.getElementById('editServicoId').value = servico.id;
    document.getElementById('editServicoNome').value = servico.nome || '';
    document.getElementById('editServicoPreco').value = parseFloat(servico.preco).toFixed(2);
    document.getElementById('editServicoDuracao').value = servico.duracao_minutos || 30;
    document.getElementById('editServicoIcone').value = servico.icone || '🐾';
    document.getElementById('editServicoDescricao').value = servico.descricao || '';
    document.getElementById('modalEditarServico').classList.add('active');
}

function fecharModalEditarServico() {
    document.getElementById('modalEditarServico').classList.remove('active');
}

function handleServicoBackdropClick(e) {
    if (e.target.id === 'modalEditarServico') {
        fecharModalEditarServico();
    }
}
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>
