<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM agendamentos WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: index.php?tab=agendamentos");
exit;
?>
