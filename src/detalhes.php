<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php?tab=pets");
    exit;
}

$mensagem = '';

// Atualizar (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar'])) {
    $nome_cliente = trim($_POST['nome_cliente'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    $nome_pet = trim($_POST['nome_pet'] ?? '');
    $especie = trim($_POST['especie'] ?? '');
    $raca = trim($_POST['raca'] ?? '');
    $idade = trim($_POST['idade'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    try {
        $pdo->beginTransaction();

        $stmt_cliente = $pdo->prepare("UPDATE clientes SET nome = ?, telefone = ?, email = ? WHERE id = ?");
        $stmt_cliente->execute([$nome_cliente, $telefone, $email, $id]);

        $stmt_pet = $pdo->prepare("UPDATE pets SET nome = ?, especie = ?, raca = ?, idade = ?, observacoes = ? WHERE cliente_id = ?");
        $stmt_pet->execute([$nome_pet, $especie, $raca, $idade, $observacoes, $id]);

        $pdo->commit();
        $mensagem = "<div class='alert alert-success'><span>✅</span> Dados atualizados com sucesso!</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='alert alert-error'><span>⚠️</span> Erro ao atualizar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Buscar dados atuais
$stmt = $pdo->prepare("SELECT c.*, p.id as pet_id, p.nome as pet_nome, p.especie, p.raca, p.idade, p.foto, p.observacoes 
                       FROM clientes c 
                       LEFT JOIN pets p ON c.id = p.cliente_id 
                       WHERE c.id = ?");
$stmt->execute([$id]);
$dados = $stmt->fetch();

if (!$dados) {
    header("Location: index.php?tab=pets");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Tutor e Pet - PetCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: block; background: #f4f7fb; padding: 2rem 1rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <a href="index.php?tab=pets" class="btn btn-secondary btn-sm">&larr; Voltar ao Painel</a>
            <span style="font-size: 0.85rem; color: #64748b;">ID do Tutor: #<?= htmlspecialchars($dados['id']) ?></span>
        </div>

        <?= $mensagem ?>

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div>
                    <h2 class="dashboard-card-title"><span>🐾</span> Detalhes e Edição</h2>
                    <p class="dashboard-card-subtitle">Visualização completa do Tutor e Pet cadastrados.</p>
                </div>
            </div>

            <form method="POST" class="form-grid">
                <div class="form-fieldset">
                    <h3 class="form-fieldset-title"><span>👤</span> Dados do Tutor</h3>
                    <div class="form-group">
                        <label>Nome Completo:</label>
                        <input type="text" name="nome_cliente" class="form-control" value="<?= htmlspecialchars($dados['nome']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Telefone / WhatsApp:</label>
                        <input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($dados['telefone']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>E-mail:</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($dados['email']) ?>" required>
                    </div>
                </div>

                <div class="form-fieldset">
                    <h3 class="form-fieldset-title"><span>🐶</span> Dados do Pet</h3>
                    <div class="form-group">
                        <label>Nome do Pet:</label>
                        <input type="text" name="nome_pet" class="form-control" value="<?= htmlspecialchars($dados['pet_nome'] ?? '') ?>" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem;">
                        <div class="form-group">
                            <label>Espécie:</label>
                            <input type="text" name="especie" class="form-control" value="<?= htmlspecialchars($dados['especie'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Raça:</label>
                            <input type="text" name="raca" class="form-control" value="<?= htmlspecialchars($dados['raca'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Idade:</label>
                        <input type="text" name="idade" class="form-control" value="<?= htmlspecialchars($dados['idade'] ?? '') ?>" placeholder="Ex: 2 anos">
                    </div>
                    <div class="form-group">
                        <label>Preferências / Observações:</label>
                        <textarea name="observacoes" class="form-control"><?= htmlspecialchars($dados['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-footer">
                    <a href="excluir.php?id=<?= $dados['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja realmente excluir este registro?');">Excluir Cadastro</a>
                    <button type="submit" name="atualizar" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
