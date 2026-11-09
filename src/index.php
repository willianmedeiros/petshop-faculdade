<?php
// Redirecionamento inteligente a partir da raiz para as páginas individuais
$tab = $_GET['tab'] ?? 'dashboard';

switch ($tab) {
    case 'pets':
        header("Location: pets.php");
        break;
    case 'cadastro':
        header("Location: cadastro.php");
        break;
    case 'agendamentos':
        header("Location: agendamentos.php");
        break;
    case 'servicos':
        header("Location: servicos.php");
        break;
    case 'dashboard':
    default:
        header("Location: dashboard.php");
        break;
}
exit;
?>