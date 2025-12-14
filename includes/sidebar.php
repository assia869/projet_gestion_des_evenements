<?php
// /gestion-evenements/includes/sidebar.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin    = $isLoggedIn && (($_SESSION['role'] ?? '') === 'admin');

$userName  = $isLoggedIn ? trim(($_SESSION['nom'] ?? '') . ' ' . ($_SESSION['prenom'] ?? '')) : '';
$userEmail = $isLoggedIn ? ($_SESSION['email'] ?? '') : '';

$current_page = basename($_SERVER['PHP_SELF']);

/* ===============================
   NOTIFICATIONS
=============================== */
$notifications = [];
$unreadCount   = 0;

if ($isLoggedIn) {
    require_once __DIR__ . '/../classes/Database.php';
    require_once __DIR__ . '/../classes/Notification.php';

    $db  = new Database();
    $pdo = $db->getConnection();

    $notif = new Notification($pdo);
    $notifications = $notif->getUserNotifications((int) $_SESSION['user_id'], 20);
    $unreadCount   = $notif->countUnread((int) $_SESSION['user_id']);
}
?>

<div class="sidebar" id="sidebar">

    <div class="sidebar-header d-flex align-items-center justify-content-between">
        <div class="logo d-flex align-items-center gap-2">
            <i class="bi bi-calendar-event"></i>
            <span><?= htmlspecialchars(t('app_title') ?? 'Gestion Événements', ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <?php if ($isLoggedIn): ?>
            <button id="notifBell" class="notif-bell" type="button" aria-label="Notifications">
                <i class="bi bi-bell-fill"></i>
                <span id="notifBadge" class="notif-badge <?= ($unreadCount > 0 ? '' : 'd-none') ?>">
                    <?= (int) $unreadCount ?>
                </span>
            </button>
        <?php endif; ?>
    </div>

    <?php if ($isLoggedIn): ?>
        <div class="user-profile text-center">
            <div class="avatar mb-2">
                <i class="bi bi-person-circle"></i>
            </div>
            <h6><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></h6>
            <small><?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?></small>
        </div>
    <?php endif; ?>

    <nav class="sidebar-nav">
        <?php if ($isAdmin): ?>
            <a href="/gestion-evenements/admin/" class="nav-item <?= $current_page === 'index.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> <?= htmlspecialchars(t('dashboard') ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?>
            </a>
            <a href="/gestion-evenements/admin/evenements.php" class="nav-item <?= $current_page === 'evenements.php' ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i> <?= htmlspecialchars(t('events') ?? 'Événements', ENT_QUOTES, 'UTF-8') ?>
            </a>
            <a href="/gestion-evenements/admin/categories.php" class="nav-item <?= $current_page === 'categories.php' ? 'active' : '' ?>">
                <i class="bi bi-tags"></i> <?= htmlspecialchars(t('categories') ?? 'Catégories', ENT_QUOTES, 'UTF-8') ?>
            </a>
            <a href="/gestion-evenements/admin/ajouter_evenement.php" class="nav-item <?= $current_page === 'ajouter_evenement.php' ? 'active' : '' ?>">
                <i class="bi bi-plus-circle"></i> <?= htmlspecialchars(t('new_event') ?? 'Nouvel événement', ENT_QUOTES, 'UTF-8') ?>
            </a>
            <a href="/gestion-evenements/admin/carte.php" class="nav-item <?= $current_page === 'carte.php' ? 'active' : '' ?>">
                <i class="bi bi-map"></i> <?= htmlspecialchars(t('map') ?? 'Carte', ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php else: ?>
            <a href="/gestion-evenements/user/profil.php" class="nav-item <?= $current_page === 'profil.php' ? 'active' : '' ?>">
  <i class="bi bi-person"></i> Profil
</a>

            <a href="/gestion-evenements/user/" class="nav-item <?= $current_page === 'index.php' ? 'active' : '' ?>">
                <i class="bi bi-house"></i> <?= htmlspecialchars(t('home') ?? 'Accueil', ENT_QUOTES, 'UTF-8') ?>
            </a>
            <a href="/gestion-evenements/user/carte.php" class="nav-item <?= $current_page === 'carte.php' ? 'active' : '' ?>">
                <i class="bi bi-map"></i> <?= htmlspecialchars(t('map') ?? 'Carte', ENT_QUOTES, 'UTF-8') ?>
            </a>
            
            <a href="/gestion-evenements/user/calendrier.php" class="nav-item <?= $current_page === 'calendrier.php' ? 'active' : '' ?>">
                <i class="bi bi-calendar-week"></i> <?= htmlspecialchars(t('calendar') ?? 'Calendrier', ENT_QUOTES, 'UTF-8') ?>
            </a>
            <a href="/gestion-evenements/user/mes_inscriptions.php" class="nav-item <?= $current_page === 'mes_inscriptions.php' ? 'active' : '' ?>">
                <i class="bi bi-bookmark-check"></i> <?= htmlspecialchars(t('my_registrations') ?? 'Mes inscriptions', ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endif; ?>

        <div class="nav-divider"></div>

        <a href="/gestion-evenements/toggle_theme.php" class="nav-item">
            <i class="bi bi-moon-fill"></i> <?= htmlspecialchars(t('dark_mode') ?? 'Mode sombre', ENT_QUOTES, 'UTF-8') ?>
        </a>

        <a href="/gestion-evenements/change_language.php" class="nav-item">
            <i class="bi bi-translate"></i>
            <?= (($_SESSION['lang'] ?? 'fr') === 'fr') ? '🇫🇷 Français' : '🇬🇧 English' ?>
        </a>

        <div class="nav-divider"></div>

        <a href="/gestion-evenements/logout.php" class="nav-item text-danger">
            <i class="bi bi-box-arrow-right"></i> <?= htmlspecialchars(t('logout') ?? 'Déconnexion', ENT_QUOTES, 'UTF-8') ?>
        </a>
    </nav>
</div>

<!-- ✅ OVERLAY + PANEL À DROITE -->
<?php if ($isLoggedIn): ?>
<div id="notifPanelOverlay" class="notif-panel-overlay"></div>

<aside id="notifPanel" class="notif-panel" aria-hidden="true">
    <div class="notif-panel-header">
        <div class="fw-semibold">
            <i class="bi bi-bell"></i> <?= htmlspecialchars(t('notifications') ?? 'Notifications', ENT_QUOTES, 'UTF-8') ?>
        </div>
        <button type="button" id="notifClose" class="btn btn-sm btn-light">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="notif-panel-body" id="notifList">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $n): ?>
                <?php
                    $id     = (int)($n['id'] ?? 0);
                    $lien   = $n['lien'] ?? '#';
                    $msg    = $n['message'] ?? '';
                    $isRead = !empty($n['is_read']);
                    $dt     = $n['created_at'] ?? null;
                ?>
                <a class="notif-item <?= $isRead ? '' : 'unread' ?>"
                   data-id="<?= $id ?>"
                   href="<?= htmlspecialchars($lien, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="notif-msg"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($dt): ?>
                        <div class="notif-date"><?= date('d/m/Y H:i', strtotime($dt)) ?></div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-3 text-muted">
                <?= htmlspecialchars(t('no_notifications') ?? 'Aucune notification', ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
    </div>
</aside>
<?php endif; ?>
