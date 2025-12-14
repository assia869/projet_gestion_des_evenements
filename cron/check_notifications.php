<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';

$db = new Database();
$pdo = $db->connect();

$notification = new Notification($pdo);

// événements dans 1 jour
$sql = "
SELECT i.user_id, e.id AS event_id, e.titre, e.date_event
FROM inscriptions i
JOIN evenements e ON e.id = i.evenement_id
WHERE DATE(e.date_event) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
";

$stmt = $pdo->query($sql);

foreach ($stmt->fetchAll() as $row) {
    $message = "⏰ L'événement '{$row['titre']}' aura lieu demain !";
    $notification->create($row['user_id'], $row['event_id'], $message);
}
