<?php
// /gestion-evenements/user/api/notifications_actions.php
header('Content-Type: application/json; charset=UTF-8');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
    exit;
}

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Notification.php';

$db  = new Database();
$pdo = $db->getConnection();
$notif = new Notification($pdo);

$userId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'mark_all_read') {
        $notif->markAllAsRead($userId);
        echo json_encode(['ok' => true, 'unread' => 0]);
        exit;
    }

    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $notif->markAsRead($id, $userId);
        }
        $unread = $notif->countUnread($userId);
        echo json_encode(['ok' => true, 'unread' => $unread]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'bad_action']);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'exception', 'message' => $e->getMessage()]);
}
