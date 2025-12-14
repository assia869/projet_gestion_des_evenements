<?php
// /gestion-evenements/includes/notifications_widget.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$notifications = [];
$unreadCount = 0;

if ($isLoggedIn) {
    require_once __DIR__ . '/../classes/Database.php';
    require_once __DIR__ . '/../classes/Notification.php';

    $db  = new Database();
    $pdo = $db->getConnection();

    $notif = new Notification($pdo);
    $notifications = $notif->getUserNotifications((int)$_SESSION['user_id'], 15);
    $unreadCount   = $notif->countUnread((int)$_SESSION['user_id']);
}
?>

<?php if ($isLoggedIn): ?>
<div class="notif-wrapper position-relative">
    <button id="notifBell" class="btn btn-link position-relative p-0" type="button" aria-label="Notifications">
        <i class="bi bi-bell-fill fs-5"></i>

        <?php if ($unreadCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= (int)$unreadCount ?>
            </span>
        <?php endif; ?>
    </button>

    <div id="notifDropdown"
         class="notif-popup shadow bg-white rounded border position-absolute end-0 mt-2"
         style="min-width:320px; max-width:360px; display:none; z-index:2000;">
        <div class="p-2 border-bottom fw-semibold">Notifications</div>

        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $n): ?>
                <?php
                    $id     = (int)($n['id'] ?? 0);
                    $lien   = $n['lien'] ?? '#';
                    $msg    = $n['message'] ?? '';
                    $dt     = $n['created_at'] ?? null;
                    $isRead = !empty($n['is_read']);
                ?>
                <a href="<?= htmlspecialchars($lien, ENT_QUOTES, 'UTF-8') ?>"
                   data-notif-id="<?= $id ?>"
                   class="notif-item d-block text-decoration-none px-3 py-2 border-bottom <?= !$isRead ? 'bg-light' : '' ?>"
                   style="color:inherit;">
                    <div class="small"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($dt): ?>
                        <div class="text-muted" style="font-size:12px;">
                            <?= date('d/m/Y H:i', strtotime($dt)) ?>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="px-3 py-3 text-muted small">Aucune notification</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
