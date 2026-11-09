<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    // Como a FK em agendamentos tem ON DELETE SET NULL, agendamentos existentes continuam válidos
    $stmt = $pdo->prepare("DELETE FROM servicos WHERE id = ?");
    $stmt->execute([(int)$id]);
}

header("Location: servicos.php");
exit;
?>