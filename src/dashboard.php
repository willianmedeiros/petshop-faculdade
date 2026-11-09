<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$pagina_atual = 'dashboard';
$titulo_pagina = 'PetCare - Dashboard Geral';

// Próximos agendamentos
$stmt_proximos = $pdo->query("SELECT a.*, p.nome as pet_nome, p.especie, p.raca, p.foto as pet_foto,
                                     c.nome as tutor_nome, c.telefone as tutor_telefone,
                                     s.icone as servico_icone
                              FROM agendamentos a
                              JOIN pets p ON a.pet_id = p.id
                              JOIN clientes c ON p.cliente_id = c.id
                              LEFT JOIN servicos s ON a.servico_id = s.id
                              ORDER BY a.data_hora ASC
                              LIMIT 5");
$proximos_agendamentos = $stmt_proximos->fetchAll();

// Pets cadastrados recentemente
$stmt_recentes = $pdo->query("SELECT p.*, c.nome as tutor_nome, c.telefone as tutor_telefone 
                              FROM pets p 
                              JOIN clientes c ON p.cliente_id = c.id 
                              ORDER BY p.id DESC 
                              LIMIT 4");
$pets_recentes = $stmt_recentes->fetchAll();

// Catálogo de serviços ativos
$stmt_serv = $pdo->query("SELECT * FROM servicos ORDER BY preco ASC LIMIT 4");
$servicos_destaque = $stmt_serv->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- ==============================================
     CARDS DE ESTATÍSTICAS E MÉTRICAS GERAIS
     ============================================== -->
<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrapper icon-blue">🐶</div>
        <div class="stat-info">
            <span class="stat-label">Total de Pets</span>
            <span class="stat-value"><?= $metricas['total_pets'] ?></span>
            <span class="stat-subtext"><?= $metricas['total_caes'] ?> cães • <?= $metricas['total_gatos'] ?> gatos</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper icon-amber">👤</div>
        <div class="stat-info">
            <span class="stat-label">Tutores</span>
            <span class="stat-value"><?= $metricas['total_clientes'] ?></span>
            <span class="stat-subtext">Clientes registrados</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper icon-purple">📅</div>
        <div class="stat-info">
            <span class="stat-label">Agendamentos</span>
            <span class="stat-value"><?= $metricas['total_agendamentos_pendentes'] ?></span>
            <span class="stat-subtext">Pendentes / Em andamento</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper icon-emerald">💰</div>
        <div class="stat-info">
            <span class="stat-label">Faturamento Concluído</span>
            <span class="stat-value">R$ <?= number_format($metricas['faturamento_servicos'], 2, ',', '.') ?></span>
            <span class="stat-subtext"><?= $metricas['total_agendamentos_concluidos'] ?> serviços concluídos</span>
        </div>
    </div>
</section>

<!-- ==============================================
     GRID DE CONTEÚDO DO DASHBOARD
     ============================================== -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
    <!-- Coluna da Esquerda: Próximos Atendimentos & Ações Rápidas -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Próximos Agendamentos -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div>
                    <h2 class="dashboard-card-title">
                        <span>📅</span> Próximos Atendimentos
                    </h2>
                    <p class="dashboard-card-subtitle">Fila prioritária de serviços agendados para a clínica e estética.</p>
                </div>
                <a href="agendamentos.php" class="btn btn-secondary btn-sm">
                    Ver Kanban Completo &rarr;
                </a>
            </div>

            <?php if (count($proximos_agendamentos) > 0): ?>
                <div class="table-responsive">
                    <table class="pet-table">
                        <thead>
                            <tr>
                                <th>Data / Horário</th>
                                <th>Pet & Tutor</th>
                                <th>Serviço</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximos_agendamentos as $ag): 
                                $sIcone = resolverIconeServico($ag['servico_icone'] ?? '', $ag['servico']);
                                $statusClasse = 'badge-other';
                                if ($ag['status'] === 'Agendado') $statusClasse = 'badge-dog';
                                elseif ($ag['status'] === 'Em Andamento') $statusClasse = 'badge-other';
                                elseif ($ag['status'] === 'Concluído') $statusClasse = 'badge-cat';
                            ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: #1e293b; font-size: 0.9rem;">
                                            🕒 <?= date('d/m/Y \à\s H:i', strtotime($ag['data_hora'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="pet-cell">
                                            <div class="pet-avatar">
                                                <?= str_contains(strtolower($ag['especie']), 'gato') ? '🐱' : '🐶' ?>
                                            </div>
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
                                        <span style="font-weight: 700; color: #047857; white-space: nowrap;">
                                            R$ <?= number_format($ag['valor'], 2, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $statusClasse ?>">
                                            <?= htmlspecialchars($ag['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="kanban-empty" style="padding: 2.5rem 1rem;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📅</div>
                    Nenhum agendamento pendente no momento.<br>
                    <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalNovoAgendamento()" style="margin-top: 0.8rem;">
                        + Agendar Primeiro Serviço
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Catálogo de Serviços em Destaque -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div>
                    <h2 class="dashboard-card-title">
                        <span>✂️</span> Serviços Populares
                    </h2>
                    <p class="dashboard-card-subtitle">Serviços e pacotes com valores atrelados para agendamento rápido.</p>
                </div>
                <a href="servicos.php" class="btn btn-secondary btn-sm">
                    Ver Catálogo Completo &rarr;
                </a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                <?php foreach ($servicos_destaque as $s): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.1rem; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-size: 1.8rem;"><?= resolverIconeServico($s['icone'] ?? '', $s['nome']) ?></span>
                                <span class="service-duration-badge">⏱️ <?= $s['duracao_minutos'] ?> min</span>
                            </div>
                            <h4 style="font-size: 1.05rem; font-weight: 700; color: #0f2d4a; margin-bottom: 0.2rem;"><?= htmlspecialchars($s['nome']) ?></h4>
                            <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 0.8rem;"><?= htmlspecialchars(mb_strimwidth($s['descricao'] ?: 'Serviço profissional especializado.', 0, 50, "...")) ?></p>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 0.6rem; border-top: 1px solid #e2e8f0;">
                            <span style="font-weight: 800; color: #047857; white-space: nowrap; font-size: 1.15rem;">
                                R$ <?= number_format($s['preco'], 2, ',', '.') ?>
                            </span>
                            <button type="button" class="btn btn-navy btn-sm" onclick="abrirModalNovoAgendamentoComServico(<?= $s['id'] ?>, <?= $s['preco'] ?>, '<?= addslashes($s['nome']) ?>')">
                                Agendar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Coluna da Direita: Ações Rápidas & Pets Recentes -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Atalhos Rápidos -->
        <div class="dashboard-card">
            <h3 class="dashboard-card-title" style="margin-bottom: 1rem;">
                <span>⚡</span> Ações Rápidas
            </h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <button type="button" class="btn btn-primary" onclick="abrirModalNovoAgendamento()" style="justify-content: flex-start; padding: 0.85rem 1rem;">
                    <span style="font-size: 1.2rem;">📅</span>
                    <span style="text-align: left;">
                        <strong>Novo Agendamento</strong><br>
                        <small style="opacity: 0.85; font-size: 0.75rem;">Agendar banho, tosa ou consulta</small>
                    </span>
                </button>
                <a href="cadastro.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.85rem 1rem;">
                    <span style="font-size: 1.2rem;">✨</span>
                    <span style="text-align: left;">
                        <strong>Cadastrar Pet e Tutor</strong><br>
                        <small style="opacity: 0.75; font-size: 0.75rem;">Novo cliente no sistema</small>
                    </span>
                </a>
                <a href="servicos.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.85rem 1rem;">
                    <span style="font-size: 1.2rem;">✂️</span>
                    <span style="text-align: left;">
                        <strong>Tabela de Preços</strong><br>
                        <small style="opacity: 0.75; font-size: 0.75rem;">Cadastrar ou editar serviços</small>
                    </span>
                </a>
                <a href="agendamentos.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.85rem 1rem;">
                    <span style="font-size: 1.2rem;">📋</span>
                    <span style="text-align: left;">
                        <strong>Quadro Kanban</strong><br>
                        <small style="opacity: 0.75; font-size: 0.75rem;">Arrastar e gerenciar status</small>
                    </span>
                </a>
            </div>
        </div>

        <!-- Pets Recentes -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div>
                    <h3 class="dashboard-card-title">
                        <span>🐶</span> Pets Recentes
                    </h3>
                </div>
                <a href="pets.php" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">
                    Ver Todos
                </a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($pets_recentes as $pet): 
                    $fotoSrc = !empty($pet['foto']) && file_exists(__DIR__ . '/' . $pet['foto']) ? $pet['foto'] : null;
                    $defaultIcon = str_contains(strtolower($pet['especie']), 'gato') ? '🐱' : '🐶';
                ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0.8rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <?php if ($fotoSrc): ?>
                                <img src="<?= htmlspecialchars($fotoSrc) ?>" alt="Foto" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 38px; height: 38px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                                    <?= $defaultIcon ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <strong style="color: #0f172a; font-size: 0.92rem;"><?= htmlspecialchars($pet['nome']) ?></strong>
                                <div style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($pet['especie']) ?> • <?= htmlspecialchars($pet['tutor_nome']) ?></div>
                            </div>
                        </div>
                        <a href="pets.php?busca=<?= urlencode($pet['nome']) ?>" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                            Ver
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/includes/footer.php';
?>
