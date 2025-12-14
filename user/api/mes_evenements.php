<?php
// /gestion-evenements/user/api/mes_evenements.php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

require_once __DIR__ . '/../../classes/Inscription.php';

$inscriptionObj = new Inscription();
$rows = $inscriptionObj->getMesInscriptionsCalendrier((int)$_SESSION['user_id']);

$events = [];

foreach ($rows as $r) {
    $date = $r['date_evenement'] ?? null;
    if (!$date) continue;

    $hDebut = !empty($r['heure_debut']) ? substr($r['heure_debut'], 0, 8) : null;
    $hFin   = !empty($r['heure_fin'])   ? substr($r['heure_fin'], 0, 8)   : null;

    // start
    $start = $hDebut ? ($date . 'T' . $hDebut) : $date;

    // end : si pas d'heure_fin, on met +1h pour que ça s'affiche bien en week/day
    if ($hDebut && !$hFin) {
        try {
            $dt = new DateTimeImmutable($date . ' ' . substr($hDebut, 0, 5));
            $hFin = $dt->modify('+1 hour')->format('H:i:s');
        } catch (Exception $e) {
            $hFin = null;
        }
    }
    $end = ($hDebut && $hFin) ? ($date . 'T' . $hFin) : null;

    $events[] = [
        'id'    => (int)$r['id'],
        'title' => $r['titre'] ?? 'Événement',
        'start' => $start,
        'end'   => $end,
        'url'   => '/gestion-evenements/user/details_evenement.php?id=' . (int)$r['id'],
        'extendedProps' => [
            'lieu'      => $r['lieu'] ?? '',
            'categorie' => $r['categorie'] ?? ''
        ]
    ];
}

echo json_encode($events);
