<?php
// Exige que o $pdo e as funções já estejam carregados
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';

$metricas = carregarMetricasGlobais($pdo);
$pagina_atual = $pagina_atual ?? 'dashboard';
$titulo_pagina = $titulo_pagina ?? 'PetCare - Sistema de Gestão para PetShop';

// Lista de serviços para o modal de agendamento compartilhado
$stmt_servicos_modal = $pdo->query("SELECT * FROM servicos ORDER BY nome ASC");
$lista_servicos_global = $stmt_servicos_modal->fetchAll();

// Lista de pets para o modal de agendamento compartilhado
$stmt_pets_modal = $pdo->query("SELECT p.id as pet_id, p.nome as pet_nome, p.especie, p.raca, p.observacoes, c.nome as tutor_nome 
                                FROM pets p 
                                JOIN clientes c ON p.cliente_id = c.id 
                                ORDER BY p.nome ASC");
$lista_pets_global = $stmt_pets_modal->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo_pagina) ?></title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/../style.css') ?>">
</head>
<body>
    <div class="app-layout">
        <!-- ===============================
             SIDEBAR DE NAVEGAÇÃO MODULAR
             =============================== -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">🐾</div>
                <div class="brand-title">pet<span>care</span></div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-label">Visão Geral</div>

                <!-- Item: Dashboard -->
                <a href="dashboard.php" class="nav-item <?= $pagina_atual === 'dashboard' ? 'active' : '' ?>">
                    <span class="icon">📊</span>
                    <span>Dashboard</span>
                </a>

                <div class="nav-label" style="margin-top: 1rem;">Gerenciamento</div>

                <!-- Item: Novo Cadastro -->
                <a href="cadastro.php" class="nav-item <?= $pagina_atual === 'cadastro' ? 'active' : '' ?>">
                    <span class="icon">✨</span>
                    <span>Novo Cadastro</span>
                </a>

                <!-- Item: Pets e Tutores -->
                <a href="pets.php" class="nav-item <?= $pagina_atual === 'pets' ? 'active' : '' ?>">
                    <span class="icon">🐶</span>
                    <span>Pets e Tutores</span>
                    <span class="nav-badge"><?= $metricas['total_pets'] ?></span>
                </a>

                <div class="nav-label" style="margin-top: 1rem;">Módulos de Atendimento</div>

                <!-- Item: Agendamentos (Kanban) -->
                <a href="agendamentos.php" class="nav-item <?= $pagina_atual === 'agendamentos' ? 'active' : '' ?>">
                    <span class="icon">📅</span>
                    <span>Agendamentos</span>
                    <span class="nav-badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;"><?= $metricas['total_agendamentos_pendentes'] ?></span>
                </a>

                <!-- Item: Serviços & Preços -->
                <a href="servicos.php" class="nav-item <?= $pagina_atual === 'servicos' ? 'active' : '' ?>">
                    <span class="icon">✂️</span>
                    <span>Serviços & Preços</span>
                    <span class="nav-badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;"><?= $metricas['total_servicos'] ?></span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-footer-info">
                    <span class="system-version">PetCare v3.0 • AC3</span>
                    <span class="system-credits">Faculdade Impacta</span>
                </div>
            </div>
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
                    <a href="servicos.php" class="btn btn-secondary btn-sm">
                        <span>✂️</span> Serviços
                    </a>
                    <a href="cadastro.php" class="btn btn-primary btn-sm">
                        <span>+</span> Novo Cadastro
                    </a>
                    <div class="user-profile-chip" title="Aluno Willian Medeiros">
                        <div class="user-avatar">WM</div>
                        <span class="user-name">Olá, Willian</span>
                    </div>
                </div>
            </header>

            <!-- Conteúdo da Página -->
            <main class="content-body">
                <?php if (!empty($mensagem)): ?>
                    <?= $mensagem ?>
                <?php endif; ?>
