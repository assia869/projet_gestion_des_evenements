<?php
session_start();

// Liste des langues disponibles (FR et EN seulement)
$available_langs = ['fr', 'en'];

// Récupérer la langue demandée
$lang = $_GET['lang'] ?? '';

// Si une langue est spécifiée
if (!empty($lang) && in_array($lang, $available_langs)) {
    $_SESSION['lang'] = $lang;
} else {
    // Basculer entre FR et EN
    $current_lang = $_SESSION['lang'] ?? 'fr';
    $_SESSION['lang'] = ($current_lang === 'fr') ? 'en' : 'fr';
}

// Rediriger vers la page précédente
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/gestion-evenements/'));
exit;
?>