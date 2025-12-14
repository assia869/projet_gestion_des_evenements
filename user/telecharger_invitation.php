<?php
// /gestion-evenements/user/telecharger_invitation.php

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once __DIR__ . '/../classes/Inscription.php';
require_once __DIR__ . '/../services/TicketService.php';

$inscriptionId = isset($_GET['inscription_id']) ? (int)$_GET['inscription_id'] : 0;
if ($inscriptionId <= 0) {
    header('Location: /gestion-evenements/user/mes_inscriptions.php');
    exit;
}

$inscriptionObj = new Inscription();
$data = $inscriptionObj->getTicketDataByInscriptionIdForUser($inscriptionId, (int)$_SESSION['user_id']);

if (!$data) {
    header('Location: /gestion-evenements/user/mes_inscriptions.php');
    exit;
}

// Si PDF déjà généré et présent sur disque => télécharger direct
if (!empty($data['ticket_pdf_path'])) {
    $pdfFs = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $data['ticket_pdf_path'];
    if (is_file($pdfFs)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="invitation_' . $data['ticket_token'] . '.pdf"');
        header('Content-Length: ' . filesize($pdfFs));
        readfile($pdfFs);
        exit;
    }
}

// Sinon => générer
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
$verifyUrl = $baseUrl . '/gestion-evenements/verify_ticket.php?token=' . urlencode((string)$data['ticket_token']);

$ticketData = [
    'token' => (string)$data['ticket_token'],

    'participant_name'  => (string)$data['nom_participant'],
    'participant_email' => (string)$data['email_participant'],
    'participant_phone' => (string)($data['telephone'] ?? ''),

    'event_title'    => (string)$data['titre'],
    'event_date'     => date('d/m/Y', strtotime((string)$data['date_evenement'])),
    'event_start'    => substr((string)$data['heure_debut'], 0, 5),
    'event_end'      => substr((string)$data['heure_fin'], 0, 5),
    'event_location' => (string)$data['lieu'],

    'event_contact_email' => (string)($data['contact_email'] ?? ''),
    'event_contact_phone' => (string)($data['contact_phone'] ?? ''),

    'verify_url' => $verifyUrl,
];

$svc = new TicketService();
$out = $svc->buildAndSavePdf($ticketData);

// Sauvegarder le chemin web du PDF dans la BDD
$inscriptionObj->saveTicketPdfPath($inscriptionId, $out['pdf_web']);

// Télécharger
$pdfFs = $out['pdf_fs'];
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="invitation_' . $data['ticket_token'] . '.pdf"');
header('Content-Length: ' . filesize($pdfFs));
readfile($pdfFs);
exit;
