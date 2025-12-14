<?php
// /gestion-evenements/verify_ticket.php

session_start();

require_once __DIR__ . '/classes/Inscription.php';

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$inscriptionObj = new Inscription();

$ticket = null;
if ($token !== '') {
    $ticket = $inscriptionObj->getTicketDataByToken($token);
}

$pageTitle = 'Vérification Ticket';
$cssPath = '/gestion-evenements/assets/css/style.css';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container my-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-shield-check"></i> Vérification du billet</h4>
        </div>
        <div class="card-body">
            <?php if (!$ticket): ?>
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-x-circle"></i> Billet invalide ou introuvable.
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Billet valide.
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <h5 class="mb-3">Participant</h5>
                        <p class="mb-1"><strong>Nom :</strong> <?= htmlspecialchars($ticket['nom_participant']) ?></p>
                        <p class="mb-1"><strong>Email :</strong> <?= htmlspecialchars($ticket['email_participant']) ?></p>
                        <p class="mb-1"><strong>Téléphone :</strong> <?= htmlspecialchars($ticket['telephone'] ?? '—') ?></p>
                        <p class="mb-0"><strong>Statut :</strong> <?= htmlspecialchars($ticket['statut'] ?? '—') ?></p>
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-3">Événement</h5>
                        <p class="mb-1"><strong>Titre :</strong> <?= htmlspecialchars($ticket['titre']) ?></p>
                        <p class="mb-1"><strong>Date :</strong> <?= date('d/m/Y', strtotime($ticket['date_evenement'])) ?></p>
                        <p class="mb-1"><strong>Horaire :</strong> <?= substr($ticket['heure_debut'], 0, 5) ?> - <?= substr($ticket['heure_fin'], 0, 5) ?></p>
                        <p class="mb-1"><strong>Lieu :</strong> <?= htmlspecialchars($ticket['lieu']) ?></p>
                        <p class="mb-1"><strong>Contact email :</strong> <?= htmlspecialchars($ticket['contact_email'] ?? '—') ?></p>
                        <p class="mb-0"><strong>Contact téléphone :</strong> <?= htmlspecialchars($ticket['contact_phone'] ?? '—') ?></p>
                    </div>
                </div>

                <hr>
                <p class="mb-0 small text-muted">
                    Token : <code><?= htmlspecialchars($ticket['ticket_token'] ?? $token) ?></code>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
