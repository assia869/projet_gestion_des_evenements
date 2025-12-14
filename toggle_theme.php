<?php
session_start();

// Toggle le thème entre light et dark
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light';
}

$_SESSION['theme'] = $_SESSION['theme'] === 'light' ? 'dark' : 'light';

// Rediriger vers la page précédente
$redirect = $_SERVER['HTTP_REFERER'] ?? '/gestion-evenements/';
header('Location: ' . $redirect);
exit;
?>