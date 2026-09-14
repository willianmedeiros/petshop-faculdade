<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$mensagem = '';

// Atualizar (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar'])) {
    $nome_cliente = $_POST['nome_cliente'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    
    $nome_pet = $_POST['nome_pet'] ?? '';
    $especie = $_POST['especie'] ?? '';
    $raca = $_POST['raca'] ?? '';

    try {
        $pdo->beginTransaction();

        $stmt_cliente = $pdo->prepare("UPDATE clientes SET nome = ?, telefone = ?, email = ? WHERE id = ?");
        $stmt_cliente->execute([$nome_cliente, $telefone, $email, $id]);

        $stmt_pet = $pdo->prepare("UPDATE pets SET nome = ?, especie = ?, raca = ? WHERE cliente_id = ?");
        $stmt_pet->execute([$nome_pet, $especie, $raca, $id]);

        $pdo->commit();
        $mensagem = "<div class='alert success'>Dados atualizados com sucesso!</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='alert error'>Erro ao atualizar: " . $e->getMessage() . "</div>";
    }
}

// Buscar dados atuais
$stmt = $pdo->prepare("SELECT c.*, p.nome as pet_nome, p.especie, p.raca 
                       FROM clientes c 
                       LEFT JOIN pets p ON c.id = p.cliente_id 
                       WHERE c.id = ?");
$stmt->execute([$id]);
$dados = $stmt->fetch();

if (!$dados) {
    echo "Cliente não encontrado!";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes - PetShop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🐾 Detalhes do Tutor e Pet</h1>
            <div style="margin-top: 1rem;">
                <a href="index.php" class="btn-secondary" style="text-decoration: none;">&larr; Voltar para Home</a>
            </div>
        </header>

        <?= $mensagem ?>

        <div class="main-content" style="grid-template-columns: 1fr; max-width: 800px; margin: 0 auto;">
            <section class="card form-section">
                <h2>Visualizar e Editar</h2>
                <form method="POST">
                    <fieldset>
                        <legend>Dados do Tutor</legend>
                        <div class="input-group">
                            <label>Nome Completo:</label>
                            <input type="text" name="nome_cliente" value="<?= htmlspecialchars($dados['nome']) ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Telefone:</label>
                            <input type="text" name="telefone" value="<?= htmlspecialchars($dados['telefone']) ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Email:</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($dados['email']) ?>" required>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Dados do Pet</legend>
                        <div class="input-group">
                            <label>Nome do Pet:</label>
                            <input type="text" name="nome_pet" value="<?= htmlspecialchars($dados['pet_nome'] ?? '') ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Espécie (Ex: Cão, Gato):</label>
                            <input type="text" name="especie" value="<?= htmlspecialchars($dados['especie'] ?? '') ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Raça:</label>
                            <input type="text" name="raca" value="<?= htmlspecialchars($dados['raca'] ?? '') ?>">
                        </div>
                    </fieldset>

                    <button type="submit" name="atualizar" class="btn-primary">Salvar Alterações</button>
                </form>
            </section>
        </div>
    </div>
</body>
</html>
