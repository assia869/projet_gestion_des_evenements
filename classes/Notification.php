<?php
// /gestion-evenements/classes/Notification.php

class Notification
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* =========================
       CREATE
    ========================== */

    public function create(int $userId, string $message, ?string $link = null, ?string $type = null): bool
    {
        $sql = "INSERT INTO notifications (user_id, type, message, lien, is_read, created_at)
                VALUES (:uid, :type, :msg, :link, 0, NOW())";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':uid'  => $userId,
            ':type' => $type,
            ':msg'  => $message,
            ':link' => $link
        ]);
    }

    public function createMany(array $userIds, string $message, ?string $link = null, ?string $type = null): int
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) return 0;

        $sql = "INSERT INTO notifications (user_id, type, message, lien, is_read, created_at)
                VALUES (:uid, :type, :msg, :link, 0, NOW())";
        $stmt = $this->pdo->prepare($sql);

        $count = 0;
        foreach ($userIds as $uid) {
            if ($stmt->execute([':uid'=>$uid, ':type'=>$type, ':msg'=>$message, ':link'=>$link])) {
                $count++;
            }
        }
        return $count;
    }

    public function notifyAllUsers(string $message, ?string $link = null, ?string $type = null): int
    {
        $rows = $this->pdo->query("SELECT id FROM users WHERE role='user'")->fetchAll(PDO::FETCH_COLUMN);
        return $this->createMany($rows ?: [], $message, $link, $type);
    }

    public function notifyEventRegistrants(int $eventId, string $message, ?string $link = null, ?string $type = null): int
    {
        $sql = "SELECT DISTINCT user_id
                FROM inscriptions
                WHERE evenement_id = :eid
                  AND (statut IS NULL OR statut='confirme')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eid' => $eventId]);
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $this->createMany($userIds ?: [], $message, $link, $type);
    }

    /* =========================
       READ
    ========================== */

    public function getUserNotifications(int $userId, int $limit = 15): array
    {
        $sql = "SELECT id, type, message, lien, is_read, created_at
                FROM notifications
                WHERE user_id = :uid
                ORDER BY created_at DESC
                LIMIT :lim";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countUnread(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /* =========================
       MARK AS READ
    ========================== */

    public function markAsRead(int $notifId, int $userId): bool
    {
        $sql = "UPDATE notifications
                SET is_read = 1
                WHERE id = :id AND user_id = :uid";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $notifId, ':uid' => $userId]);
    }

    public function markAllAsRead(int $userId): int
    {
        $sql = "UPDATE notifications
                SET is_read = 1
                WHERE user_id = :uid AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->rowCount();
    }
}
