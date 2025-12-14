<?php
session_start();

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'user')) {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once __DIR__ . '/../classes/User.php';

$userObj = new User();
$userId  = (int)$_SESSION['user_id'];

$success = null;
$error   = null;

$profile = $userObj->getProfileById($userId);
if (!$profile) {
    header('Location: /gestion-evenements/user/');
    exit;
}

/* ==========================
   UPDATE INFOS
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_info') {
    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse   = trim($_POST['adresse'] ?? '');

    if ($nom === '' || $prenom === '' || $email === '') {
        $error = "Veuillez remplir Nom, Prénom et Email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif ($userObj->emailExists($email, $userId)) {
        $error = "Cet email est déjà utilisé par un autre utilisateur.";
    } else {
        $ok = $userObj->updateProfile($userId, $nom, $prenom, $email, $telephone, $adresse);

        if ($ok) {
            // ✅ mettre à jour la session (pour sidebar + affichage)
            $_SESSION['nom']       = $nom;
            $_SESSION['prenom']    = $prenom;
            $_SESSION['email']     = $email;
            $_SESSION['telephone'] = $telephone;
            $_SESSION['adresse']   = $adresse;

            $success = "Profil mis à jour avec succès.";
            $profile = $userObj->getProfileById($userId);
        } else {
            $error = "Erreur lors de la mise à jour du profil.";
        }
    }
}

/* ==========================
   UPDATE PASSWORD
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    $old = (string)($_POST['old_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $cfn = (string)($_POST['confirm_password'] ?? '');

    if ($old === '' || $new === '' || $cfn === '') {
        $error = "Veuillez remplir tous les champs du mot de passe.";
    } elseif ($new !== $cfn) {
        $error = "Confirmation du mot de passe incorrecte.";
    } elseif (strlen($new) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif (!$userObj->verifyPassword($userId, $old)) {
        $error = "Mot de passe actuel incorrect.";
    } else {
        $ok = $userObj->updatePassword($userId, $new);
        $success = $ok ? "Mot de passe modifié avec succès." : "Erreur lors du changement du mot de passe.";
        if (!$ok) $error = $success;
    }
}

$pageTitle = "Mon profil";
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <div class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="bi bi-person"></i> Mon profil</h2>
      <a href="/gestion-evenements/user/" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Retour
      </a>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- INFOS -->
      <div class="col-lg-7">
        <div class="card shadow-sm">
          <div class="card-header">
            <i class="bi bi-pencil-square"></i> Informations personnelles
          </div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="update_info">

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Nom *</label>
                  <input type="text" name="nom" class="form-control" required
                         value="<?= htmlspecialchars($profile['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label">Prénom *</label>
                  <input type="text" name="prenom" class="form-control" required
                         value="<?= htmlspecialchars($profile['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-12">
                  <label class="form-label">Email *</label>
                  <input type="email" name="email" class="form-control" required
                         value="<?= htmlspecialchars($profile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label">Téléphone</label>
                  <input type="text" name="telephone" class="form-control"
                         value="<?= htmlspecialchars($profile['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label">Adresse</label>
                  <input type="text" name="adresse" class="form-control"
                         value="<?= htmlspecialchars($profile['adresse'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-12 mt-2">
                  <button class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Enregistrer
                  </button>
                </div>
              </div>
            </form>

          </div>
        </div>
      </div>

      <!-- PASSWORD -->
      <div class="col-lg-5">
        <div class="card shadow-sm">
          <div class="card-header">
            <i class="bi bi-shield-lock"></i> Sécurité
          </div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="update_password">

              <div class="mb-3">
                <label class="form-label">Mot de passe actuel</label>
                <input type="password" name="old_password" class="form-control" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Nouveau mot de passe</label>
                <input type="password" name="new_password" class="form-control" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Confirmer</label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>

              <button class="btn btn-warning">
                <i class="bi bi-key"></i> Changer mot de passe
              </button>
            </form>
          </div>
        </div>

        <div class="small text-muted mt-3">
          Rôle : <b><?= htmlspecialchars($profile['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></b><br>
          Date création : <?= htmlspecialchars($profile['date_creation'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
