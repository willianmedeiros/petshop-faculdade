<?php
require_once 'db.php';

$mensagem = '';

// Lógica de Cadastro (INSERT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar'])) {
    $nome_cliente = $_POST['nome_cliente'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    
    $nome_pet = $_POST['nome_pet'] ?? '';
    $especie = $_POST['especie'] ?? '';
    $raca = $_POST['raca'] ?? '';

    try {
        $pdo->beginTransaction();

        // Insere Cliente
        $stmt_cliente = $pdo->prepare("INSERT INTO clientes (nome, telefone, email) VALUES (?, ?, ?)");
        $stmt_cliente->execute([$nome_cliente, $telefone, $email]);
        $cliente_id = $pdo->lastInsertId();

        // Insere Pet
        $stmt_pet = $pdo->prepare("INSERT INTO pets (cliente_id, nome, especie, raca) VALUES (?, ?, ?, ?)");
        $stmt_pet->execute([$cliente_id, $nome_pet, $especie, $raca]);

        $pdo->commit();
        $mensagem = "<div class='alert success'>Cadastro realizado com sucesso!</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='alert error'>Erro ao cadastrar: " . $e->getMessage() . "</div>";
    }
}

// Lógica de Listagem (SELECT) com filtro
$busca = $_GET['busca'] ?? '';
$query = "SELECT c.id as cliente_id, c.nome as cliente_nome, c.telefone, p.nome as pet_nome, p.especie, p.raca 
          FROM clientes c 
          LEFT JOIN pets p ON c.id = p.cliente_id";

if ($busca) {
    $query .= " WHERE c.nome LIKE :busca1 OR p.nome LIKE :busca2";
}
$query .= " ORDER BY c.data_cadastro DESC";

$stmt_listagem = $pdo->prepare($query);
if ($busca) {
    $stmt_listagem->execute(['busca1' => "%$busca%", 'busca2' => "%$busca%"]);
} else {
    $stmt_listagem->execute();
}
$resultados = $stmt_listagem->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetShop - Cadastro e Listagem</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🐾 Sistema PetShop</h1>
        </header>

        <?= $mensagem ?>

        <div class="main-content">
            <!-- Formulário de Cadastro -->
            <section class="card form-section">
                <h2>Novo Cadastro</h2>
                <form method="POST" action="index.php">
                    <fieldset>
                        <legend>Dados do Tutor</legend>
                        <div class="input-group">
                            <label>Nome Completo:</label>
                            <input type="text" name="nome_cliente" required>
                        </div>
                        <div class="input-group">
                            <label>Telefone:</label>
                            <input type="text" name="telefone" required>
                        </div>
                        <div class="input-group">
                            <label>Email:</label>
                            <input type="email" name="email" required>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Dados do Pet</legend>
                        <div class="input-group">
                            <label>Nome do Pet:</label>
                            <input type="text" name="nome_pet" required>
                        </div>
                        <div class="input-group">
                            <label>Espécie (Ex: Cão, Gato):</label>
                            <input type="text" name="especie" required>
                        </div>
                        <div class="input-group">
                            <label>Raça:</label>
                            <input type="text" name="raca">
                        </div>
                    </fieldset>

                    <button type="submit" name="cadastrar" class="btn-primary">Cadastrar</button>
                </form>
            </section>

            <!-- Listagem com Filtro -->
            <section class="card list-section">
                <h2>Pets e Tutores Cadastrados</h2>
                
                <form method="GET" action="index.php" class="search-form">
                    <input type="text" name="busca" placeholder="Buscar por tutor ou pet..." value="<?= htmlspecialchars($busca) ?>">
                    <button type="submit" class="btn-secondary">Filtrar</button>
                    <?php if ($busca): ?>
                        <a href="index.php" class="btn-clear">Limpar</a>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Tutor</th>
                                <th>Telefone</th>
                                <th>Pet</th>
                                <th>Espécie</th>
                                <th>Raça</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($resultados) > 0): ?>
                                <?php foreach ($resultados as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['cliente_nome']) ?></td>
                                        <td><?= htmlspecialchars($row['telefone']) ?></td>
                                        <td><?= htmlspecialchars($row['pet_nome']) ?></td>
                                        <td><?= htmlspecialchars($row['especie']) ?></td>
                                        <td><?= htmlspecialchars($row['raca']) ?></td>
                                        <td style="white-space: nowrap;">
                                            <a href="detalhes.php?id=<?= $row['cliente_id'] ?>" class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; text-decoration: none;">Detalhes</a>
                                            <a href="excluir.php?id=<?= $row['cliente_id'] ?>" class="btn-clear" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; margin-left: 0.5rem;" onclick="return confirm('Tem certeza que deseja excluir o tutor e seu pet?');">Excluir</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Nenhum registro encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
