<?php
// /gestion-evenements/user/details_evenement.php

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once '../classes/Evenement.php';
require_once '../classes/Inscription.php';

$evenementId = $_GET['id'] ?? 0;

$evenementObj = new Evenement();
$evenement = $evenementObj->getEvenementById($evenementId);

if (!$evenement) {
    header('Location: /gestion-evenements/user/');
    exit;
}

$inscriptionObj = new Inscription();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => $_SESSION['nom'] . ' ' . $_SESSION['prenom'],
        'email' => $_SESSION['email'],
        'telephone' => $_POST['telephone'] ?? ''
    ];

    $result = $inscriptionObj->inscrire((int)$evenementId, (int)$_SESSION['user_id'], $data);

    if (!empty($result['success'])) {
        header('Location: /gestion-evenements/user/telecharger_invitation.php?inscription_id=' . (int)$result['inscription_id']);
        exit;
    }

    $message = $result['message'] ?? 'Erreur';
    $messageType = 'danger';

    $evenement = $evenementObj->getEvenementById($evenementId);
}

$pageTitle = $evenement['titre'];
$cssPath = '/gestion-evenements/assets/css/style.css';
include(__DIR__ . '/../includes/header.php');
include(__DIR__ . '/../includes/navbar.php');



$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$shareUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/gestion-evenements/user/details_evenement.php?id=' . urlencode((string)$evenementId);
?>
<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <?php if ($evenement['image']): ?>
                    <img src="<?php echo htmlspecialchars($evenement['image']); ?>"
                         class="card-img-top" alt="<?php echo htmlspecialchars($evenement['titre']); ?>"
                         style="max-height: 400px; object-fit: cover;">
                <?php endif; ?>

                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <div class="mb-2">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($evenement['categorie_nom']); ?></span>
                            </div>
                            <h2 class="card-title mb-0"><?php echo htmlspecialchars($evenement['titre']); ?></h2>
                        </div>

                        <div class="text-end">
                            <button class="btn btn-outline-primary btn-sm" type="button"
                                    data-bs-toggle="modal" data-bs-target="#shareModal">
                                <i class="bi bi-share"></i> Partager
                            </button>
                        </div>
                    </div>

                    <div class="my-4">
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($evenement['description'])); ?></p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar3 fs-4 text-primary me-3"></i>
                                <div>
                                    <small class="text-muted">Date</small>
                                    <p class="mb-0"><?php echo date('d/m/Y', strtotime($evenement['date_evenement'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-clock fs-4 text-primary me-3"></i>
                                <div>
                                    <small class="text-muted">Horaire</small>
                                    <p class="mb-0"><?php echo substr($evenement['heure_debut'], 0, 5); ?> - <?php echo substr($evenement['heure_fin'], 0, 5); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-geo-alt fs-4 text-primary me-3"></i>
                                <div>
                                    <small class="text-muted">Lieu</small>
                                    <p class="mb-0"><?php echo htmlspecialchars($evenement['lieu']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-people fs-4 text-primary me-3"></i>
                                <div>
                                    <small class="text-muted">Places</small>
                                    <p class="mb-0"><?php echo $evenement['nb_inscrits']; ?> / <?php echo $evenement['nb_max_participants']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">Contact</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-envelope fs-5 text-primary me-2"></i>
                                <div>
                                    <small class="text-muted">Email</small>
                                    <p class="mb-0"><?php echo htmlspecialchars($evenement['contact_email'] ?? '—'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-telephone fs-5 text-primary me-2"></i>
                                <div>
                                    <small class="text-muted">Téléphone</small>
                                    <p class="mb-0"><?php echo htmlspecialchars($evenement['contact_phone'] ?? '—'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-plus"></i> S'inscrire</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($evenement['nb_inscrits'] >= $evenement['nb_max_participants']): ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> Cet événement est complet
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($_SESSION['nom'] . ' ' . $_SESSION['prenom']); ?>"
                                       readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control"
                                       value="<?php echo htmlspecialchars($_SESSION['email']); ?>"
                                       readonly>
                            </div>

                            <div class="mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone"
                                       placeholder="Votre numéro de téléphone">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Confirmer l'inscription
                            </button>

                            <div class="small text-muted mt-2">
                                Après inscription, votre invitation PDF (avec QR Code) se téléchargera automatiquement.
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <a href="/gestion-evenements/user/" class="btn btn-outline-secondary w-100 mt-3">
                <i class="bi bi-arrow-left"></i> Retour aux événements
            </a>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-share"></i> Partager l’événement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Lien</label>
          <div class="input-group">
            <input type="text" class="form-control" id="shareLink" value="<?= htmlspecialchars($shareUrl) ?>" readonly>
            <button class="btn btn-outline-primary" type="button" id="copyShareLink">
              <i class="bi bi-clipboard"></i>
            </button>
          </div>
        </div>

        <div class="d-grid gap-2">
          <a class="btn btn-success" id="shareWhatsapp" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp"></i> WhatsApp
          </a>
          <a class="btn btn-primary" id="shareMessenger" target="_blank" rel="noopener">
            <i class="bi bi-messenger"></i> Messenger
          </a>
          <a class="btn btn-secondary" id="shareEmail">
            <i class="bi bi-envelope"></i> Email
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
window.SHARE_DATA = {
  url: <?= json_encode($shareUrl) ?>,
  title: <?= json_encode((string)$evenement['titre']) ?>
};
</script>
<script src="/gestion-evenements/assets/js/share.js"></script>

<?php include(__DIR__ . '/../includes/footer.php');?>
