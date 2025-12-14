<?php
// /gestion-evenements/admin/supprimer_evenement.php  (REMPLACE TOUT LE FICHIER)
// ✅ version corrigée: utilise /classes/Database.php (et Notification::create(message, link, type) correct)

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Evenement.php';
require_once __DIR__ . '/../classes/Notification.php';

$db  = new Database();
$pdo = $db->getConnection();

$evenementObj = new Evenement();
$notification = new Notification($pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: evenements.php');
    exit;
}

/* 🔍 récupérer titre + users inscrits */
$titre = '';
try {
    $evt = $evenementObj->getEvenementById($id);
    if ($evt && !empty($evt['titre'])) $titre = $evt['titre'];
} catch (Throwable $e) {}

$stmt = $pdo->prepare("SELECT DISTINCT user_id FROM inscriptions WHERE evenement_id = ?");
$stmt->execute([$id]);
$users = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* 🔥 suppression */
if ($evenementObj->supprimerEvenement($id)) {

    $link = "/gestion-evenements/user/";
    $msg  = "❌ Événement annulé : " . ($titre !== '' ? $titre : ("ID #" . $id));

    // notifier les inscrits
    if (!empty($users)) {
        $notification->createMany($users, $msg, $link, "event_cancelled");
    }

    header('Location: evenements.php?success=delete');
    exit;
}

header('Location: evenements.php?error=delete');
exit;
