<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Traductions
require_once __DIR__ . '/../lang/functions.php';
$currentLang  = getCurrentLanguage();
$translations = loadLanguage($currentLang); // utilisé par t()

// ✅ Titre (compat: $pageTitle OU $pageTitleKey)
if (isset($pageTitleKey)) {
    $resolvedTitle = t($pageTitleKey);
} else {
    $resolvedTitle = isset($pageTitle) ? $pageTitle : t('app_title');
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <link rel="stylesheet" href="/gestion-evenements/assets/css/notifications.css">
<script src="/gestion-evenements/assets/js/notifications.js" defer></script>

    <link rel="stylesheet" href="/gestion-evenements/assets/css/notifications_panel.css">
    <script defer src="/gestion-evenements/assets/js/notifications.js"></script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($resolvedTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="/gestion-evenements/assets/css/notifications.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- CSS global -->
    <link rel="stylesheet" href="/gestion-evenements/assets/css/style.css">

    <!-- CSS spécifique page (optionnel) -->
    <?php if (!empty($cssPath)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <script src="/gestion-evenements/assets/js/notifications.js"></script>

    <script>
      // utile pour JS (FullCalendar, etc.)
      window.APP_LANG = "<?= htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8') ?>";
    </script>
</head>

<body class="<?= (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'dark-mode' : '' ?>">
