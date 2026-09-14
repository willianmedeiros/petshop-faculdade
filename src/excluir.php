<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    // A FK na tabela pets tem ON DELETE CASCADE
    // Então, deletar o cliente apaga o pet automaticamente.
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: index.php");
exit;
?>
