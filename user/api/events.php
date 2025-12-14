<?php
// /gestion-evenements/user/api/events.php

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$pdo = $db->getConnection();

$sql = "SELECT e.*, c.nom AS categorie_nom
        FROM evenements e
        LEFT JOIN categories c ON e.categorie_id = c.id
        WHERE e.statut = 'actif'";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll();

$out = [];
foreach ($rows as $e) {
    $start = $e['date_evenement'] . 'T' . substr($e['heure_debut'] ?? '00:00:00', 0, 5) . ':00';
    $end   = $e['date_evenement'] . 'T' . substr($e['heure_fin'] ?? '00:00:00', 0, 5) . ':00';

    $out[] = [
        'id' => (int)$e['id'],
        'title' => (string)$e['titre'],
        'start' => $start,
        'end' => $end,
        'extendedProps' => [
            'lieu' => (string)($e['lieu'] ?? ''),
            'categorie' => (string)($e['categorie_nom'] ?? ''),
            'description' => (string)($e['description'] ?? ''),
        ],
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out);
