<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$pagina_atual = 'agendamentos';
$titulo_pagina = 'PetCare - Agendamentos de Serviços';
$mensagem = '';
$toast_notificacao = null;

// ==========================================
// AÇÕES DO BACKEND (AGENDAMENTOS)
// ==========================================

// 1. Cadastrar Agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cadastrar_agendamento') {
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    $servico_id = !empty($_POST['servico_id']) ? (int)$_POST['servico_id'] : null;
    $servico = trim($_POST['servico'] ?? '');
    $valor = 0.00;

    // O valor do serviço fica obrigatoriamente atrelado ao serviço cadastrado
    if ($servico_id) {
        $stmt_s = $pdo->prepare("SELECT nome, preco FROM servicos WHERE id = ?");
        $stmt_s->execute([$servico_id]);
        $s_info = $stmt_s->fetch();
        if ($s_info) {
            $servico = $s_info['nome'];
            $valor = (float)$s_info['preco'];
        }
    }

    $data_hora = trim($_POST['data_hora'] ?? '');
    $status = 'Agendado'; // Todo novo agendamento inicia como 'Agendado'
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($pet_id > 0 && !empty($servico) && !empty($data_hora)) {
        try {
            $stmt_ag = $pdo->prepare("INSERT INTO agendamentos (pet_id, servico_id, servico, data_hora, status, valor, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_ag->execute([$pet_id, $servico_id, $servico, $data_hora, $status, $valor, $observacoes]);

            $toast_notificacao = [
                'msg' => 'Agendamento de <strong>' . htmlspecialchars($servico) . '</strong> realizado com sucesso!',
                'tipo' => 'success',
                'icone' => '📅'
            ];
        } catch (Exception $e) {
            $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro ao agendar:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Atenção:</strong> Selecione o Pet e preencha a Data/Hora do agendamento.</div>";
    }
}

// 2. Atualizar Agendamento (Edição - Apenas Agendados e Em Andamento)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_agendamento') {
    $agendamento_id = (int)($_POST['agendamento_id'] ?? 0);
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    $servico_id = !empty($_POST['servico_id']) ? (int)$_POST['servico_id'] : null;
    $servico = trim($_POST['servico'] ?? '');
    $valor = 0.00;

    if ($servico_id) {
        $stmt_s = $pdo->prepare("SELECT nome, preco FROM servicos WHERE id = ?");
        $stmt_s->execute([$servico_id]);
        $s_info = $stmt_s->fetch();
        if ($s_info) {
            $servico = $s_info['nome'];
            $valor = (float)$s_info['preco'];
        }
    }

    $data_hora = trim($_POST['data_hora'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($agendamento_id > 0 && $pet_id > 0 && !empty($servico) && !empty($data_hora)) {
        try {
            $stmt_check = $pdo->prepare("SELECT status FROM agendamentos WHERE id = ?");
            $stmt_check->execute([$agendamento_id]);
            $status_atual = $stmt_check->fetchColumn();

            if (!in_array($status_atual, ['Agendado', 'Em Andamento'])) {
                $toast_notificacao = [
                    'msg' => 'Agendamentos com status "' . htmlspecialchars($status_atual) . '" não podem ser editados.',
                    'tipo' => 'warning',
                    'icone' => '🔒'
                ];
            } else {
                $stmt_up_ag = $pdo->prepare("UPDATE agendamentos SET pet_id = ?, servico_id = ?, servico = ?, data_hora = ?, valor = ?, observacoes = ? WHERE id = ?");
                $stmt_up_ag->execute([$pet_id, $servico_id, $servico, $data_hora, $valor, $observacoes, $agendamento_id]);

                $toast_notificacao = [
                    'msg' => 'Agendamento atualizado com sucesso!',
                    'tipo' => 'success',
                    'icone' => '✏️'
                ];
            }
        } catch (Exception $e) {
            $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// 3. Mudança de Status Interativa / Drag & Drop AJAX
if (isset($_GET['mudar_status']) && isset($_GET['agendamento_id'])) {
    $agendamento_id = (int)$_GET['agendamento_id'];
    $novo_status = trim($_GET['mudar_status']);
    $status_permitidos = ['Agendado', 'Em Andamento', 'Concluído', 'Cancelado'];

    if ($agendamento_id > 0 && in_array($novo_status, $status_permitidos)) {
        try {
            // Regra estrita: se o agendamento já estiver Cancelado, é irreversível
            $stmt_check_cancelado = $pdo->prepare("SELECT status FROM agendamentos WHERE id = ?");
            $stmt_check_cancelado->execute([$agendamento_id]);
            $status_banco = $stmt_check_cancelado->fetchColumn();

            if ($status_banco === 'Cancelado') {
                if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Agendamentos cancelados são irreversíveis e não podem ser reativados.']);
                    exit;
                }
                $toast_notificacao = [
                    'msg' => 'Agendamentos cancelados são irreversíveis e não podem ser alterados.',
                    'tipo' => 'warning',
                    'icone' => '🚫'
                ];
            } else {
                $stmt_status = $pdo->prepare("UPDATE agendamentos SET status = ? WHERE id = ?");
                $stmt_status->execute([$novo_status, $agendamento_id]);
                
                if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'status' => $novo_status, 'agendamento_id' => $agendamento_id]);
                    exit;
                }
                $toast_notificacao = [
                    'msg' => 'Status do agendamento alterado para <strong>' . htmlspecialchars($novo_status) . '</strong>!',
                    'tipo' => 'success',
                    'icone' => '🔄'
                ];
            }
        } catch (Exception $e) {
            if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
            $mensagem = "<div class='alert alert-error'><span>⚠️</span> <strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// ==========================================
// CONSULTA E AGRUPAMENTO DOS AGENDAMENTOS
// ==========================================
$busca_ag = trim($_GET['busca_ag'] ?? '');

$query_ag = "SELECT a.*, p.nome as pet_nome, p.especie, p.raca, p.foto as pet_foto, p.observacoes as pet_observacoes,
                    c.id as tutor_id, c.nome as tutor_nome, c.telefone as tutor_telefone, c.email as tutor_email,
                    s.icone as servico_icone
             FROM agendamentos a
             JOIN pets p ON a.pet_id = p.id
             JOIN clientes c ON p.cliente_id = c.id
             LEFT JOIN servicos s ON a.servico_id = s.id
             WHERE 1=1";

$params_ag = [];
if ($busca_ag !== '') {
    $query_ag .= " AND (p.nome LIKE :busca_ag1 OR c.nome LIKE :busca_ag2 OR a.servico LIKE :busca_ag3)";
    $termo_ag = "%$busca_ag%";
    $params_ag['busca_ag1'] = $termo_ag;
    $params_ag['busca_ag2'] = $termo_ag;
    $params_ag['busca_ag3'] = $termo_ag;
}
$query_ag .= " ORDER BY a.data_hora ASC";

$stmt_ag = $pdo->prepare($query_ag);
$stmt_ag->execute($params_ag);
$agendamentos = $stmt_ag->fetchAll();

// 4 Colunas Kanban
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
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div>
            <h2 class="dashboard-card-title">
                <span>📅</span> Gestão de Atendimentos & Agendamentos
            </h2>
            <p class="dashboard-card-subtitle">Arraste e solte os cards entre as colunas para atualizar o fluxo de atendimento em tempo real.</p>
        </div>

        <div style="display: flex; gap: 0.8rem; align-items: center; flex-wrap: wrap;">
            <!-- Alternância de Visualização (Colunas vs Tabela) -->
            <div class="view-toggle">
                <button type="button" class="view-toggle-btn active" id="btnVisaoColunas" onclick="alternarVisaoAgendamentos('colunas')" title="Visualização em 4 Colunas Kanban">
                    <span>📊</span> Colunas
                </button>
                <button type="button" class="view-toggle-btn" id="btnVisaoTabela" onclick="alternarVisaoAgendamentos('tabela')" title="Visualização em Lista Tabular">
                    <span>📄</span> Tabela
                </button>
            </div>

            <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalNovoAgendamento()">
                <span>+</span> Novo Agendamento
            </button>
        </div>
    </div>

    <!-- Barra de Busca -->
    <div class="table-filter-bar">
        <form method="GET" action="agendamentos.php" class="search-box">
            <input type="text" name="busca_ag" placeholder="Buscar por pet, tutor ou serviço..." value="<?= htmlspecialchars($busca_ag) ?>">
            <button type="submit" class="btn btn-secondary btn-sm">Filtrar</button>
        </form>
        <?php if ($busca_ag !== ''): ?>
            <a href="agendamentos.php" class="btn btn-secondary btn-sm">Limpar Busca</a>
        <?php endif; ?>
        <span style="font-size: 0.85rem; color: #64748b;">
            Total: <strong><?= count($agendamentos) ?></strong> agendamentos cadastrados
        </span>
    </div>

    <!-- ==============================================
         VISÃO 1: 4 COLUNAS KANBAN (DRAG AND DROP)
         ============================================== -->
    <div id="containerVisaoColunas" class="kanban-board">
        <?php foreach ($colunas_status as $statusChave => $col): ?>
            <div class="kanban-column <?= $col['classe'] ?>" data-status-col="<?= $statusChave ?>">
                <div class="kanban-column-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span><?= $col['icone'] ?></span>
                        <span><?= $col['titulo'] ?></span>
                    </div>
                    <span class="kanban-col-count"><?= count($col['itens']) ?></span>
                </div>

                <div class="kanban-cards-list" 
                     data-status="<?= $statusChave ?>" 
                     ondragover="handleDragOver(event)" 
                     ondragenter="handleDragEnter(event)" 
                     ondragleave="handleDragLeave(event)" 
                     ondrop="handleDrop(event, '<?= $statusChave ?>')">
                    <?php if (count($col['itens']) > 0): ?>
                        <?php foreach ($col['itens'] as $ag): 
                            $petFotoSrc = !empty($ag['pet_foto']) && file_exists(__DIR__ . '/' . $ag['pet_foto']) ? $ag['pet_foto'] : null;
                            $petIconDefault = str_contains(strtolower($ag['especie']), 'gato') ? '🐱' : '🐶';
                            $sIcone = resolverIconeServico($ag['servico_icone'] ?? '', $ag['servico'] ?? '');

                            // Permissão de Edição: Apenas Agendados e Em Andamento
                            $podeEditar = in_array($ag['status'], ['Agendado', 'Em Andamento']);

                            $agStatus = $ag['status'];
                            $isCanceled = ($agStatus === 'Cancelado');
                            $podeEditar = in_array($agStatus, ['Agendado', 'Em Andamento']);

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
                                'observacoes' => $ag['observacoes'] ?? '',
                                'pet_observacoes' => $ag['pet_observacoes'] ?? ''
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>
                            <div class="kanban-card <?= $isCanceled ? 'is-canceled' : '' ?>" 
                                 id="card-ag-<?= $ag['id'] ?>" 
                                 draggable="<?= $isCanceled ? 'false' : 'true' ?>" 
                                 ondragstart="handleDragStart(event, <?= $ag['id'] ?>, '<?= $ag['status'] ?>', '<?= addslashes($ag['pet_nome']) ?>')" 
                                 ondragend="handleDragEnd(event)" 
                                 style="<?= $isCanceled ? 'cursor: not-allowed;' : 'cursor: grab;' ?>"
                                 data-ag-id="<?= $ag['id'] ?>" 
                                 data-ag-status="<?= $ag['status'] ?>" 
                                 data-pet-id="<?= $ag['pet_id'] ?>"
                                 data-pet-nome="<?= htmlspecialchars($ag['pet_nome']) ?>" 
                                 data-tutor-nome="<?= htmlspecialchars($ag['tutor_nome']) ?>"
                                 data-servico-id="<?= $ag['servico_id'] ?>"
                                 data-servico="<?= htmlspecialchars($ag['servico']) ?>"
                                 data-data-hora="<?= date('Y-m-d\TH:i', strtotime($ag['data_hora'])) ?>"
                                 data-valor="<?= $ag['valor'] ?>"
                                 data-observacoes="<?= htmlspecialchars($ag['observacoes'] ?? '') ?>"
                                 data-pet-observacoes="<?= htmlspecialchars($ag['pet_observacoes'] ?? '') ?>"
                                 data-json="<?= $jsonAgData ?>">

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

                                <!-- Serviço e Preço Atrelado -->
                                <div class="kanban-card-service">
                                    <span style="font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
                                        <span><?= $sIcone ?></span>
                                        <span><?= htmlspecialchars($ag['servico']) ?></span>
                                    </span>
                                    <span class="kanban-card-price">R$ <?= number_format($ag['valor'], 2, ',', '.') ?></span>
                                </div>

                                <!-- Observações Clínicas & Atendimento (Amarelo Clarinho + Azul) -->
                                <?php if (!empty($ag['pet_observacoes'])): ?>
                                    <div class="kanban-obs-box obs-padrao-pet" 
                                         style="<?= $podeEditar ? 'cursor: pointer;' : 'cursor: default;' ?>" 
                                         <?= $podeEditar ? "onclick='abrirModalEditarAgendamento({$ag['id']})'" : "" ?> 
                                         title="Ficha Permanente do Pet: <?= htmlspecialchars($ag['pet_observacoes']) ?>">
                                        ⭐ <strong>Ficha:</strong> <?= htmlspecialchars(mb_strimwidth(trim(preg_replace('/\s+/', ' ', str_replace(['•', '*'], '', $ag['pet_observacoes']))), 0, 48, "...")) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($ag['observacoes'])): ?>
                                    <div class="kanban-obs-box obs-agendamento-especifico" 
                                         style="<?= $podeEditar ? 'cursor: pointer;' : 'cursor: default;' ?>" 
                                         <?= $podeEditar ? "onclick='abrirModalEditarAgendamento({$ag['id']})'" : "" ?> 
                                         title="Observação deste Atendimento: <?= htmlspecialchars($ag['observacoes']) ?>">
                                        📌 <strong>Atendimento:</strong> <?= htmlspecialchars(mb_strimwidth(trim(preg_replace('/\s+/', ' ', $ag['observacoes'])), 0, 48, "...")) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Botões de Ação do Card (O seletor mudar status só aparece na visualização em Tabela) -->
                                <div class="kanban-card-btns">
                                    <span class="status-quick-btn-box">
                                        <?php if ($ag['status'] === 'Agendado'): ?>
                                            <button type="button" class="btn btn-warning btn-sm" onclick="mudarStatusAgendamento(<?= $ag['id'] ?>, 'Em Andamento')" title="Iniciar Atendimento">
                                                Iniciar
                                            </button>
                                        <?php elseif ($ag['status'] === 'Em Andamento'): ?>
                                            <button type="button" class="btn btn-success btn-sm" onclick="mudarStatusAgendamento(<?= $ag['id'] ?>, 'Concluído')" title="Concluir Atendimento">
                                                Concluir
                                            </button>
                                        <?php endif; ?>
                                    </span>

                                    <!-- Edição permitida APENAS para Agendados e Em Andamento -->
                                    <button type="button" 
                                            class="btn btn-secondary btn-sm btn-card-editar" 
                                            style="<?= $podeEditar ? 'display: inline-flex;' : 'display: none;' ?>" 
                                            onclick="abrirModalEditarAgendamento(<?= $ag['id'] ?>)" 
                                            title="Editar agendamento no PopUp do pet <?= htmlspecialchars($ag['pet_nome']) ?>">
                                        Editar
                                    </button>

                                    <a href="excluir_agendamento.php?id=<?= $ag['id'] ?>" class="btn btn-danger btn-sm btn-card-delete" onclick="return confirm('Deseja realmente excluir este agendamento?');" title="Excluir">
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
                    <th style="min-width: 140px;">Data / Horário</th>
                    <th style="min-width: 180px;">Pet & Tutor</th>
                    <th style="min-width: 130px;">Serviço</th>
                    <th style="min-width: 95px;">Valor</th>
                    <th style="min-width: 195px;">Status do Atendimento</th>
                    <th style="min-width: 250px;">Observações</th>
                    <th style="text-align: right; min-width: 120px;">Ações</th>
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
                        $podeEditarTab = in_array($ag['status'], ['Agendado', 'Em Andamento']);

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
                            'observacoes' => $ag['observacoes'] ?? '',
                            'pet_observacoes' => $ag['pet_observacoes'] ?? ''
                        ]), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr id="tr-ag-<?= $ag['id'] ?>" 
                            data-ag-id="<?= $ag['id'] ?>" 
                            data-ag-status="<?= $ag['status'] ?>" 
                            data-pet-id="<?= $ag['pet_id'] ?>" 
                            data-pet-nome="<?= htmlspecialchars($ag['pet_nome']) ?>" 
                            data-tutor-nome="<?= htmlspecialchars($ag['tutor_nome']) ?>" 
                            data-servico-id="<?= $ag['servico_id'] ?>" 
                            data-servico="<?= htmlspecialchars($ag['servico']) ?>" 
                            data-data-hora="<?= date('Y-m-d\TH:i', strtotime($ag['data_hora'])) ?>" 
                            data-valor="<?= $ag['valor'] ?>" 
                            data-observacoes="<?= htmlspecialchars($ag['observacoes'] ?? '') ?>" 
                            data-pet-observacoes="<?= htmlspecialchars($ag['pet_observacoes'] ?? '') ?>" 
                            data-json="<?= $jsonAgData ?>">
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
                                <?php if ($ag['status'] === 'Cancelado'): ?>
                                    <span class="badge badge-danger" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; font-weight: 700;">🔴 Cancelado</span>
                                <?php else: ?>
                                    <select class="status-select-inline" 
                                            id="table-select-status-<?= $ag['id'] ?>" 
                                            data-ag-id="<?= $ag['id'] ?>" 
                                            data-pet-nome="<?= htmlspecialchars($ag['pet_nome']) ?>" 
                                            data-status-atual="<?= $ag['status'] ?>" 
                                            onchange="handleSelectStatusTabela(this)">
                                        <option value="Agendado" <?= $ag['status'] === 'Agendado' ? 'selected' : '' ?>>🔵 Agendado</option>
                                        <option value="Em Andamento" <?= $ag['status'] === 'Em Andamento' ? 'selected' : '' ?>>🟡 Em Andamento</option>
                                        <option value="Concluído" <?= $ag['status'] === 'Concluído' ? 'selected' : '' ?>>🟢 Concluído</option>
                                        <option value="Cancelado">🔴 Cancelar (Irreversível)</option>
                                    </select>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem; align-items: flex-start;">
                                    <?php if (!empty($ag['pet_observacoes'])): ?>
                                        <span class="table-obs-badge obs-yellow" title="Ficha Permanente do Pet: <?= htmlspecialchars($ag['pet_observacoes']) ?>" <?= $podeEditarTab ? "style='cursor: pointer;' onclick='abrirModalEditarAgendamento({$ag['id']})'" : "" ?>>
                                            ⭐ <strong>Ficha:</strong> <?= htmlspecialchars(mb_strimwidth(trim(preg_replace('/\s+/', ' ', str_replace(['•', '*'], '', $ag['pet_observacoes']))), 0, 36, "...")) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($ag['observacoes'])): ?>
                                        <span class="table-obs-badge obs-blue" title="Observação deste Atendimento: <?= htmlspecialchars($ag['observacoes']) ?>" <?= $podeEditarTab ? "style='cursor: pointer;' onclick='abrirModalEditarAgendamento({$ag['id']})'" : "" ?>>
                                            📌 <strong>Atendimento:</strong> <?= htmlspecialchars(mb_strimwidth(trim(preg_replace('/\s+/', ' ', $ag['observacoes'])), 0, 36, "...")) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (empty($ag['pet_observacoes']) && empty($ag['observacoes'])): ?>
                                        <span style="color: #94a3b8; font-size: 0.82rem;">-</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <?php if ($podeEditarTab): ?>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="abrirModalEditarAgendamento(<?= $ag['id'] ?>)" title="Editar agendamento de <?= htmlspecialchars($ag['pet_nome']) ?>">
                                        Editar
                                    </button>
                                <?php endif; ?>
                                <a href="excluir_agendamento.php?id=<?= $ag['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja realmente excluir este agendamento?');" title="Excluir">
                                    🗑️
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <p style="font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Nenhum agendamento encontrado.</p>
                            <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 1rem;">
                                <?= $busca_ag ? 'Tente buscar por outro termo.' : 'Agende o primeiro atendimento para os pets cadastrados.' ?>
                            </p>
                            <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalNovoAgendamento()">+ Novo Agendamento</button>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================================
     MODAL DE CONFIRMAÇÃO DE CANCELAMENTO (IRREVERSÍVEL)
     ======================================================== -->
<div id="modalConfirmarCancelamento" class="modal-overlay" onclick="handleCancelamentoBackdropClick(event)">
    <div class="modal-card" style="max-width: 480px; text-align: center;">
        <div class="modal-header" style="justify-content: center; border-bottom: none; padding-bottom: 0;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto;">
                ⚠️
            </div>
        </div>
        <div class="modal-body" style="padding-top: 0.6rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 0.6rem;">
                Confirmar Cancelamento?
            </h3>
            <p style="color: #475569; font-size: 0.92rem; margin-bottom: 1.1rem; line-height: 1.5;">
                Deseja realmente cancelar o agendamento do pet <strong id="cancelModalPetNome">este pet</strong>?
            </p>
            <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 0.8rem 1rem; color: #9f1239; font-size: 0.85rem; text-align: left; line-height: 1.45; margin-bottom: 1.4rem;">
                <strong>Atenção:</strong> Esta ação é <u>irreversível</u>. O agendamento ficará congelado na coluna Cancelados e não poderá mais ser reativado, editado ou movido para etapas anteriores.
            </div>
            <div style="display: flex; gap: 0.8rem; justify-content: center;">
                <button type="button" class="btn btn-secondary" onclick="fecharModalCancelamento()" style="flex: 1;">
                    Não, Manter
                </button>
                <button type="button" class="btn btn-danger" onclick="executarConfirmacaoCancelamento()" style="flex: 1;">
                    Sim, Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
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

// Controle do PopUp de Confirmação de Cancelamento
let pendingCancelCallback = null;

function solicitarCancelamento(agId, petNome, onConfirm, onCancel) {
    pendingCancelCallback = { onConfirm, onCancel };
    document.getElementById('cancelModalPetNome').textContent = petNome ? `"${petNome}"` : 'este pet';
    document.getElementById('modalConfirmarCancelamento').classList.add('active');
}

function fecharModalCancelamento() {
    if (pendingCancelCallback && typeof pendingCancelCallback.onCancel === 'function') {
        pendingCancelCallback.onCancel();
    }
    pendingCancelCallback = null;
    const m = document.getElementById('modalConfirmarCancelamento');
    if (m) m.classList.remove('active');
}

function executarConfirmacaoCancelamento() {
    if (pendingCancelCallback && typeof pendingCancelCallback.onConfirm === 'function') {
        pendingCancelCallback.onConfirm();
    }
    pendingCancelCallback = null;
    const m = document.getElementById('modalConfirmarCancelamento');
    if (m) m.classList.remove('active');
}

function handleCancelamentoBackdropClick(e) {
    if (e.target.id === 'modalConfirmarCancelamento') {
        fecharModalCancelamento();
    }
}

// Drag and Drop Kanban
let cardArrastadoId = null;
let petNomeArrastado = '';

function handleDragStart(e, agId, statusAtual, petNome) {
    const card = document.getElementById('card-ag-' + agId);
    const statusReal = card ? card.getAttribute('data-ag-status') : statusAtual;
    
    // Regra: cards cancelados não podem ser arrastados
    if (statusReal === 'Cancelado') {
        e.preventDefault();
        mostrarToast('Agendamentos cancelados são irreversíveis e não podem ser movidos.', 'warning', '🚫', 5000);
        return false;
    }

    cardArrastadoId = agId;
    petNomeArrastado = petNome;
    e.dataTransfer.setData('text/plain', agId);
    e.dataTransfer.effectAllowed = 'move';
    if (card) {
        setTimeout(() => card.classList.add('is-dragging'), 0);
    }
}

function handleDragEnd(e) {
    if (cardArrastadoId) {
        const card = document.getElementById('card-ag-' + cardArrastadoId);
        if (card) card.classList.remove('is-dragging');
    }
    document.querySelectorAll('.kanban-cards-list').forEach(col => {
        col.classList.remove('drag-over');
    });
    cardArrastadoId = null;
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e) {
    e.preventDefault();
    const targetList = e.currentTarget;
    if (targetList && targetList.classList.contains('kanban-cards-list')) {
        targetList.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    const targetList = e.currentTarget;
    if (targetList && !targetList.contains(e.relatedTarget)) {
        targetList.classList.remove('drag-over');
    }
}

function handleDrop(e, novoStatus) {
    e.preventDefault();
    const targetList = e.currentTarget;
    if (targetList) targetList.classList.remove('drag-over');

    const agId = e.dataTransfer.getData('text/plain') || cardArrastadoId;
    if (!agId) return;

    const card = document.getElementById('card-ag-' + agId);
    if (!card) return;

    const statusAtual = card.getAttribute('data-ag-status');
    if (statusAtual === 'Cancelado') {
        mostrarToast('Agendamentos cancelados são irreversíveis e não podem ser movidos.', 'warning', '🚫', 5000);
        return;
    }
    if (statusAtual === novoStatus) return;

    const petNome = card.getAttribute('data-pet-nome') || petNomeArrastado;

    // Se o destino for "Cancelado", exige confirmação no PopUp
    if (novoStatus === 'Cancelado') {
        solicitarCancelamento(
            agId, 
            petNome,
            function() {
                moverCardNaInterface(card, agId, statusAtual, 'Cancelado', targetList);
                executarAtualizacaoStatusAjax(agId, 'Cancelado', petNome);
            },
            function() {
                // Cancelou o cancelamento: não faz nada
            }
        );
        return;
    }

    // Qualquer outro status
    moverCardNaInterface(card, agId, statusAtual, novoStatus, targetList);
    executarAtualizacaoStatusAjax(agId, novoStatus, petNome);
}

function moverCardNaInterface(card, agId, statusAntigo, novoStatus, targetList) {
    card.setAttribute('data-ag-status', novoStatus);

    const emptyPlaceholder = targetList.querySelector('.kanban-empty');
    if (emptyPlaceholder) emptyPlaceholder.style.display = 'none';

    targetList.appendChild(card);

    // Se for Cancelado: fica cinza, perde drag e cursor not-allowed
    if (novoStatus === 'Cancelado') {
        card.classList.add('is-canceled');
        card.setAttribute('draggable', 'false');
        card.style.cursor = 'not-allowed';
    } else {
        card.classList.remove('is-canceled');
        card.setAttribute('draggable', 'true');
        card.style.cursor = 'grab';
    }

    // Regra: só permitir editar quando for "Agendado" ou "Em Andamento"
    const podeEditar = (novoStatus === 'Agendado' || novoStatus === 'Em Andamento');
    const btnEditar = card.querySelector('.btn-card-editar');

    if (btnEditar) {
        btnEditar.style.display = podeEditar ? 'inline-flex' : 'none';
    }
    card.querySelectorAll('.kanban-obs-box').forEach(obsBox => {
        obsBox.style.cursor = podeEditar ? 'pointer' : 'default';
        if (podeEditar) {
            obsBox.onclick = function() { abrirModalEditarAgendamento(agId); };
        } else {
            obsBox.onclick = null;
        }
    });

    // Atualiza botões rápidos de ação
    const quickBox = card.querySelector('.status-quick-btn-box');
    if (quickBox) {
        if (novoStatus === 'Agendado') {
            quickBox.innerHTML = `<button type="button" class="btn btn-warning btn-sm" onclick="mudarStatusAgendamento(${agId}, 'Em Andamento')" title="Iniciar Atendimento">Iniciar</button>`;
        } else if (novoStatus === 'Em Andamento') {
            quickBox.innerHTML = `<button type="button" class="btn btn-success btn-sm" onclick="mudarStatusAgendamento(${agId}, 'Concluído')" title="Concluir Atendimento">Concluir</button>`;
        } else {
            quickBox.innerHTML = '';
        }
    }

    // Atualiza o seletor correspondente na visualização em tabela caso exista
    const tableSelect = document.getElementById('table-select-status-' + agId);
    if (tableSelect) {
        if (novoStatus === 'Cancelado') {
            tableSelect.value = 'Cancelado';
            tableSelect.disabled = true;
        } else {
            tableSelect.value = novoStatus;
            tableSelect.setAttribute('data-status-atual', novoStatus);
        }
    }

    atualizarContadoresColunas();
}

function atualizarContadoresColunas() {
    document.querySelectorAll('.kanban-column').forEach(col => {
        const list = col.querySelector('.kanban-cards-list');
        const countEl = col.querySelector('.kanban-col-count');
        if (list && countEl) {
            const cards = list.querySelectorAll('.kanban-card');
            countEl.textContent = cards.length;

            let emptyBox = list.querySelector('.kanban-empty');
            if (cards.length === 0) {
                if (!emptyBox) {
                    emptyBox = document.createElement('div');
                    emptyBox.className = 'kanban-empty';
                    emptyBox.innerHTML = '<div style="font-size: 1.8rem; margin-bottom: 0.3rem;">📋</div>Nenhum agendamento nesta coluna';
                    list.appendChild(emptyBox);
                } else {
                    emptyBox.style.display = 'flex';
                }
            } else if (emptyBox) {
                emptyBox.style.display = 'none';
            }
        }
    });
}

function executarAtualizacaoStatusAjax(agId, novoStatus, petNome) {
    fetch(`agendamentos.php?mudar_status=${encodeURIComponent(novoStatus)}&agendamento_id=${agId}&ajax=1`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const nome = petNome ? ` do pet <strong>${petNome}</strong>` : '';
                mostrarToast(`Status do agendamento${nome} atualizado para <strong>${novoStatus}</strong>!`, 'success', '🔄', 5000);
            } else {
                mostrarToast(`Erro ao atualizar status: ${data.error || 'Falha no servidor'}`, 'error', '⚠️', 5000);
            }
        })
        .catch(err => {
            mostrarToast('Erro de conexão ao atualizar status do agendamento.', 'error', '⚠️', 5000);
        });
}

function mudarStatusAgendamento(agendamentoId, novoStatus) {
    const card = document.getElementById('card-ag-' + agendamentoId);
    const statusAtual = card ? card.getAttribute('data-ag-status') : '';

    if (statusAtual === 'Cancelado') {
        mostrarToast('Agendamentos cancelados são irreversíveis e não podem ser alterados.', 'warning', '🚫', 5000);
        return;
    }
    if (statusAtual === novoStatus) return;

    const petNome = card ? card.getAttribute('data-pet-nome') : '';

    if (novoStatus === 'Cancelado') {
        solicitarCancelamento(
            agendamentoId,
            petNome,
            function() {
                if (card) {
                    const targetList = document.querySelector(`.kanban-cards-list[data-status="Cancelado"]`);
                    if (targetList) moverCardNaInterface(card, agendamentoId, statusAtual, 'Cancelado', targetList);
                }
                executarAtualizacaoStatusAjax(agendamentoId, 'Cancelado', petNome);
            },
            function() {}
        );
        return;
    }

    if (card) {
        const targetList = document.querySelector(`.kanban-cards-list[data-status="${novoStatus}"]`);
        if (targetList) {
            moverCardNaInterface(card, agendamentoId, statusAtual, novoStatus, targetList);
        }
        executarAtualizacaoStatusAjax(agendamentoId, novoStatus, petNome);
    } else {
        window.location.href = `agendamentos.php?mudar_status=${encodeURIComponent(novoStatus)}&agendamento_id=${agendamentoId}`;
    }
}

// Manipulador do seletor de status no modo TABELA
function handleSelectStatusTabela(selectEl) {
    const agId = selectEl.getAttribute('data-ag-id');
    const petNome = selectEl.getAttribute('data-pet-nome');
    const statusAntigo = selectEl.getAttribute('data-status-atual');
    const novoStatus = selectEl.value;

    if (novoStatus === statusAntigo) return;

    if (novoStatus === 'Cancelado') {
        solicitarCancelamento(
            agId,
            petNome,
            function() {
                selectEl.setAttribute('data-status-atual', 'Cancelado');
                selectEl.disabled = true;
                const card = document.getElementById('card-ag-' + agId);
                if (card) {
                    const targetList = document.querySelector(`.kanban-cards-list[data-status="Cancelado"]`);
                    if (targetList) moverCardNaInterface(card, agId, statusAntigo, 'Cancelado', targetList);
                }
                executarAtualizacaoStatusAjax(agId, 'Cancelado', petNome);
            },
            function() {
                selectEl.value = statusAntigo;
            }
        );
        return;
    }

    // Outros status no modo tabela
    selectEl.setAttribute('data-status-atual', novoStatus);
    const card = document.getElementById('card-ag-' + agId);
    if (card) {
        const targetList = document.querySelector(`.kanban-cards-list[data-status="${novoStatus}"]`);
        if (targetList) moverCardNaInterface(card, agId, statusAntigo, novoStatus, targetList);
    }
    executarAtualizacaoStatusAjax(agId, novoStatus, petNome);
}

// Edição de Agendamento no PopUp
function abrirModalEditarAgendamento(param) {
    let dados = null;
    let agId = (typeof param === 'object' && param !== null) ? param.id : param;
    const card = document.getElementById('card-ag-' + agId);
    const tr = document.getElementById('tr-ag-' + agId);

    if (card) {
        dados = {
            id: agId,
            pet_id: card.getAttribute('data-pet-id'),
            pet_nome: card.getAttribute('data-pet-nome'),
            tutor_nome: card.getAttribute('data-tutor-nome'),
            servico_id: card.getAttribute('data-servico-id'),
            servico: card.getAttribute('data-servico'),
            data_hora: card.getAttribute('data-data-hora'),
            status: card.getAttribute('data-ag-status'),
            valor: card.getAttribute('data-valor'),
            observacoes: card.getAttribute('data-observacoes'),
            pet_observacoes: card.getAttribute('data-pet-observacoes') || ''
        };
    } else if (tr) {
        dados = {
            id: agId,
            pet_id: tr.getAttribute('data-pet-id'),
            pet_nome: tr.getAttribute('data-pet-nome'),
            tutor_nome: tr.getAttribute('data-tutor-nome'),
            servico_id: tr.getAttribute('data-servico-id'),
            servico: tr.getAttribute('data-servico'),
            data_hora: tr.getAttribute('data-data-hora'),
            status: tr.getAttribute('data-ag-status'),
            valor: tr.getAttribute('data-valor'),
            observacoes: tr.getAttribute('data-observacoes'),
            pet_observacoes: tr.getAttribute('data-pet-observacoes') || ''
        };
    } else if (typeof param === 'object' && param !== null) {
        dados = param;
    }

    if (!dados) return;

    // Regra: Bloquear edição se o status atual for Concluído ou Cancelado
    if (dados.status === 'Concluído' || dados.status === 'Cancelado') {
        mostrarToast(`Agendamentos com status "<strong>${dados.status}</strong>" não podem ser editados.`, 'warning', '🔒', 5000);
        return;
    }

    document.getElementById('modalAgendamentoTitle').innerHTML = `<span>✏️</span> Editar Agendamento — Pet: <strong>${dados.pet_nome}</strong>`;
    document.getElementById('agendamentoAction').value = 'atualizar_agendamento';
    document.getElementById('agendamentoId').value = dados.id;

    // Mostra info fixa do Pet do agendamento
    const selPet = document.getElementById('agendamentoPetId');
    selPet.value = dados.pet_id;
    selPet.style.display = 'none';

    // Dispara carregamento das observações permanentes da ficha no bloco amarelo
    if (typeof atualizarObservacoesPetAoSelecionar === 'function') {
        atualizarObservacoesPetAoSelecionar();
    }

    const infoPet = document.getElementById('infoPetEdicao');
    if (infoPet) {
        document.getElementById('infoPetEdicaoNome').textContent = dados.pet_nome || '-';
        document.getElementById('infoPetEdicaoTutor').textContent = 'Tutor: ' + (dados.tutor_nome || '-');
        infoPet.style.display = 'flex';
    }

    // Seleciona o serviço
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
    atualizarPrecoServico();

    document.getElementById('agendamentoDataHora').value = dados.data_hora;
    document.getElementById('agendamentoStatus').value = dados.status;
    document.getElementById('agendamentoObservacoes').value = dados.observacoes || '';

    // Dispara atualização do preview do agendamento no bloco azul
    if (typeof atualizarPreviewObservacoes === 'function') {
        atualizarPreviewObservacoes();
    }

    document.getElementById('btnSalvarAgendamento').textContent = '💾 Salvar Alterações';
    document.getElementById('modalAgendamento').classList.add('active');
}
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>
