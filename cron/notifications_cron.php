<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';

$db = new Database();
$pdo = $db->getConnection();

$notif = new Notification($pdo);

/* =====================================
   1️⃣ RAPPEL : 2 JOURS AVANT ÉVÉNEMENT
===================================== */
$sql = "
SELECT i.user_id, e.id, e.titre
FROM inscriptions i
JOIN evenements e ON e.id = i.evenement_id
WHERE DATE(e.date_evenement) = DATE_ADD(CURDATE(), INTERVAL 2 DAY)
";

foreach ($pdo->query($sql) as $row) {
    $notif->create(
        $row['user_id'],
        'Rappel événement',
        "⏰ L’événement « {$row['titre']} » aura lieu dans 2 jours.",
        'event',
        "/gestion-evenements/user/details_evenement.php?id={$row['id']}"
    );
}

/* =====================================
   2️⃣ ÉVÉNEMENT ANNULÉ
===================================== */
$sql = "
SELECT i.user_id, e.id, e.titre
FROM inscriptions i
JOIN evenements e ON e.id = i.evenement_id
WHERE e.statut = 'annule'
";

foreach ($pdo->query($sql) as $row) {
    $notif->create(
        $row['user_id'],
        'Événement annulé',
        " L’événement « {$row['titre']} » a été annulé.",
        'warning',
        "/gestion-evenements/user/details_evenement.php?id={$row['id']}"
    );
}
