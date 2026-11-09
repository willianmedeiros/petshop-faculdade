<?php
// ==========================================
// FUNÇÕES UTILITÁRIAS E HELPERS DO SISTEMA
// ==========================================

// Processamento de Upload de Foto com Extensões Permitidas
function processarUploadFoto($fileInputName) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extensao = strtolower(pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION));
        
        if (in_array($extensao, $extensoes_permitidas)) {
            $nome_arquivo = 'pet_' . uniqid() . '.' . $extensao;
            $destino_dir = dirname(__DIR__) . '/uploads/';
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

// Resolução de Ícone Seguro e Emojis em UTF-8
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
    if (str_contains($nomeLower, 'hotel') || str_contains($nomeLower, 'adestram') || str_contains($nomeLower, 'hospedagem')) return '🦴';
    return '🐾';
}

// Carregamento de Métricas Globais para o Sidebar e Topbar
function carregarMetricasGlobais($pdo) {
    $total_pets = (int)$pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn();
    $total_clientes = (int)$pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $total_caes = (int)$pdo->query("SELECT COUNT(*) FROM pets WHERE LOWER(especie) LIKE '%cão%' OR LOWER(especie) LIKE '%cao%' OR LOWER(especie) LIKE '%cachorro%'")->fetchColumn();
    $total_gatos = (int)$pdo->query("SELECT COUNT(*) FROM pets WHERE LOWER(especie) LIKE '%gato%'")->fetchColumn();

    $total_servicos = (int)$pdo->query("SELECT COUNT(*) FROM servicos")->fetchColumn();
    $total_agendamentos = (int)$pdo->query("SELECT COUNT(*) FROM agendamentos")->fetchColumn();
    $total_agendamentos_pendentes = (int)$pdo->query("SELECT COUNT(*) FROM agendamentos WHERE status IN ('Agendado', 'Em Andamento')")->fetchColumn();
    $total_agendamentos_concluidos = (int)$pdo->query("SELECT COUNT(*) FROM agendamentos WHERE status = 'Concluído'")->fetchColumn();
    $faturamento_servicos = (float)$pdo->query("SELECT COALESCE(SUM(valor), 0) FROM agendamentos WHERE status = 'Concluído'")->fetchColumn();

    return [
        'total_pets' => $total_pets,
        'total_clientes' => $total_clientes,
        'total_caes' => $total_caes,
        'total_gatos' => $total_gatos,
        'total_servicos' => $total_servicos,
        'total_agendamentos' => $total_agendamentos,
        'total_agendamentos_pendentes' => $total_agendamentos_pendentes,
        'total_agendamentos_concluidos' => $total_agendamentos_concluidos,
        'faturamento_servicos' => $faturamento_servicos,
    ];
}
