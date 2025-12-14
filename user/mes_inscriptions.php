<?php
// /gestion-evenements/user/mes_inscriptions.php

session_start();

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'user')) {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once __DIR__ . '/../classes/Inscription.php';

$inscriptionObj = new Inscription();

/* ============================
   GESTION ANNULATION
============================ */
$messageKey  = null;
$messageType = null;

if (isset($_GET['annuler'])) {
    $inscriptionId = (int) $_GET['annuler'];

    if ($inscriptionObj->annulerInscription($inscriptionId, (int) $_SESSION['user_id'])) {
        $messageKey  = 'registration_cancel_success';
        $messageType = 'success';
    } else {
        $messageKey  = 'registration_cancel_error';
        $messageType = 'danger';
    }
}

/* ============================
   DONNÉES
============================ */
$inscriptions = $inscriptionObj->getMesInscriptions((int) $_SESSION['user_id']);

$pageTitleKey = 'my_registrations';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container my-5">

        <?php if ($messageKey): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                <?= htmlspecialchars(t($messageKey), ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($inscriptions)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <?= htmlspecialchars(t('no_registrations_yet'), ENT_QUOTES, 'UTF-8') ?>
            </div>

            <a href="/gestion-evenements/user/" class="btn btn-primary">
                <i class="bi bi-calendar-event"></i> <?= htmlspecialchars(t('discover_events'), ENT_QUOTES, 'UTF-8') ?>
            </a>

        <?php else: ?>

            <div class="row g-4">
                <?php foreach ($inscriptions as $inscription): ?>
                    <?php
                        // Dans getMesInscriptions(): SELECT i.* donc $inscription['id'] = ID INSCRIPTION ✅
                        $inscriptionId = (int) ($inscription['id'] ?? 0);
                    ?>

                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">

                            <?php if (!empty($inscription['image'])): ?>
                                <img src="<?= htmlspecialchars($inscription['image'], ENT_QUOTES, 'UTF-8'); ?>"
                                     class="card-img-top"
                                     style="height:200px;object-fit:cover;"
                                     alt="<?= htmlspecialchars($inscription['titre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center"
                                     style="height:200px;">
                                    <i class="bi bi-calendar-event display-4"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <?php if (!empty($inscription['categorie'])): ?>
                                    <span class="badge bg-primary mb-2">
                                        <?= htmlspecialchars($inscription['categorie'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php endif; ?>

                                <h5 class="card-title">
                                    <?= htmlspecialchars($inscription['titre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </h5>

                                <ul class="list-unstyled small mb-0">
                                    <li>
                                        <i class="bi bi-calendar3"></i>
                                        <?= !empty($inscription['date_evenement']) ? date('d/m/Y', strtotime($inscription['date_evenement'])) : '—'; ?>
                                    </li>
                                    <li>
                                        <i class="bi bi-clock"></i>
                                        <?= !empty($inscription['heure_debut']) ? htmlspecialchars(substr($inscription['heure_debut'], 0, 5), ENT_QUOTES, 'UTF-8') : '—'; ?>
                                    </li>
                                    <li>
                                        <i class="bi bi-geo-alt"></i>
                                        <?= htmlspecialchars($inscription['lieu'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                                    </li>
                                </ul>

                                <div class="mt-3">
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i>
                                        <?= htmlspecialchars(t('registered_on'), ENT_QUOTES, 'UTF-8') ?>
                                        <?= !empty($inscription['date_inscription']) ? date('d/m/Y', strtotime($inscription['date_inscription'])) : ''; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="card-footer bg-white d-grid gap-2">

                                <!-- ✅ PDF invitation (corrigé) -->
                                <a class="btn btn-outline-primary btn-sm w-100"
                                   href="/gestion-evenements/user/telecharger_invitation.php?inscription_id=<?= $inscriptionId ?>"
                                   target="_blank" rel="noopener">
                                    <i class="bi bi-download"></i>
                                    <?= htmlspecialchars(t('download_pdf_invitation'), ENT_QUOTES, 'UTF-8') ?>
                                </a>

                                <!-- ✅ Annulation -->
                                <a href="?annuler=<?= $inscriptionId ?>"
                                   class="btn btn-danger btn-sm w-100"
                                   onclick="return confirm('<?= htmlspecialchars(t('cancel_confirm'), ENT_QUOTES, 'UTF-8') ?>');">
                                    <i class="bi bi-x-circle"></i>
                                    <?= htmlspecialchars(t('cancel_registration'), ENT_QUOTES, 'UTF-8') ?>
                                </a>

                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
