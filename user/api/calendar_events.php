<?php
// /gestion-evenements/user/api/calendar_events.php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'user')) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../../classes/Database.php';

$userId = (int) $_SESSION['user_id'];

$start = $_GET['start'] ?? null;
$end   = $_GET['end'] ?? null;

$db  = new Database();
$pdo = $db->getConnection();

$sql = "
SELECT
  e.id AS event_id,
  e.titre,
  e.description,
  e.date_evenement,
  e.heure_debut,
  e.heure_fin,
  e.lieu,
  c.nom AS categorie,
  i.id AS inscription_id,
  i.statut AS inscription_statut
FROM inscriptions i
INNER JOIN evenements e ON e.id = i.evenement_id
LEFT JOIN categories c ON c.id = e.categorie_id
WHERE i.user_id = :uid
  AND i.statut = 'confirme'
";

$params = [':uid' => $userId];

if ($start && $end) {
    $sql .= " AND e.date_evenement BETWEEN :start AND :end ";
    $params[':start'] = substr($start, 0, 10);
    $params[':end']   = substr($end, 0, 10);
}

$sql .= " ORDER BY e.date_evenement ASC, e.heure_debut ASC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];

foreach ($rows as $r) {
    $date = $r['date_evenement']; // YYYY-MM-DD
    $startDt = $date;
    $endDt = $date;

    if (!empty($r['heure_debut'])) {
        $startDt = $date . 'T' . substr($r['heure_debut'], 0, 8);
    }
    if (!empty($r['heure_fin'])) {
        $endDt = $date . 'T' . substr($r['heure_fin'], 0, 8);
    } else {
        $endDt = $startDt;
    }

    $events[] = [
        'id'    => 'ev_' . (int)$r['event_id'] . '_ins_' . (int)$r['inscription_id'],
        'title' => $r['titre'],
        'start' => $startDt,
        'end'   => $endDt,
        'allDay'=> empty($r['heure_debut']),
        'extendedProps' => [
            'event_id'      => (int)$r['event_id'],
            'inscription_id'=> (int)$r['inscription_id'],
            'description'   => $r['description'] ?? '',
            'date_evenement'=> $r['date_evenement'] ?? '',
            'heure_debut'   => !empty($r['heure_debut']) ? substr($r['heure_debut'], 0, 5) : '',
            'heure_fin'     => !empty($r['heure_fin']) ? substr($r['heure_fin'], 0, 5) : '',
            'lieu'          => $r['lieu'] ?? '',
            'categorie'     => $r['categorie'] ?? '',
            'date_label'    => !empty($r['date_evenement']) ? date('d/m/Y', strtotime($r['date_evenement'])) : '',
            'time_label'    => (!empty($r['heure_debut']) ? substr($r['heure_debut'], 0, 5) : '') .
                               (!empty($r['heure_fin']) ? ' - ' . substr($r['heure_fin'], 0, 5) : ''),
        ],
    ];
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
