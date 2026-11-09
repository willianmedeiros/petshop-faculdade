<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM agendamentos WHERE id = ?");
    $stmt->execute([(int)$id]);
}

header("Location: agendamentos.php");
exit;
?>