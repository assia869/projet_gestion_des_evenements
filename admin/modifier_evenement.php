<?php
// /gestion-evenements/admin/modifier_evenement.php  (REMPLACE TOUT LE FICHIER)

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once __DIR__ . '/../classes/Evenement.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Notification.php';

$evenementObj = new Evenement();
$categories   = $evenementObj->getCategories();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$evenement = $evenementObj->getEvenementById($id);

if (!$evenement) {
    header('Location: evenements.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre               = trim($_POST['titre'] ?? '');
    $description         = trim($_POST['description'] ?? '');
    $date_evenement      = $_POST['date_evenement'] ?? '';
    $heure_debut         = $_POST['heure_debut'] ?? '';
    $heure_fin           = $_POST['heure_fin'] ?? '';
    $lieu                = trim($_POST['lieu'] ?? '');
    $contact_email       = trim($_POST['contact_email'] ?? '');
    $contact_phone       = trim($_POST['contact_phone'] ?? '');
    $categorie_id        = $_POST['categorie_id'] ?? '';
    $nb_max_participants = (int)($_POST['nb_max_participants'] ?? 50);
    $latitude            = $_POST['latitude'] ?? null;
    $longitude           = $_POST['longitude'] ?? null;

    $image_path = null;
    if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024;

        if (in_array($_FILES['image']['type'] ?? '', $allowed_types, true) && (int)($_FILES['image']['size'] ?? 0) <= $max_size) {
            $upload_dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/gestion-evenements/assets/uploads/';

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['image']['name'] ?? '', PATHINFO_EXTENSION));
            $file_name = uniqid('event_', true) . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                $image_path = '/gestion-evenements/assets/uploads/' . $file_name;
            }
        }
    }

    if ($titre === '' || $date_evenement === '' || $lieu === '') {
        $_SESSION['error'] = 'Veuillez remplir tous les champs obligatoires';
    } else {

        $ok = $evenementObj->modifierEvenement(
            $id,
            $titre,
            $description,
            $date_evenement,
            $heure_debut,
            $heure_fin,
            $lieu,
            $categorie_id,
            $nb_max_participants,
            $image_path,
            $latitude,
            $longitude,
            $contact_email,
            $contact_phone
        );

        if ($ok) {
            // ✅ notifier les inscrits
            try {
                $db  = new Database();
                $pdo = $db->getConnection();
                $notif = new Notification($pdo);

                $link = "/gestion-evenements/user/details_evenement.php?id=" . $id;
                $notif->notifyEventRegistrants($id, "✏️ L'événement a été modifié : " . $titre, $link, "event_updated");
            } catch (Throwable $e) {
                // on ne bloque pas la modification
            }

            $_SESSION['success'] = 'Événement modifié avec succès';
            header('Location: evenements.php');
            exit;
        }

        $_SESSION['error'] = 'Erreur lors de la modification';
    }

    $evenement = $evenementObj->getEvenementById($id);
}

$pageTitle = 'Modifier un Événement';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<div class="main-content">
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-pencil-square"></i> Modifier l'événement</h2>
            <a href="evenements.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">

                        <div class="col-md-8">
                            <label class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" name="titre" class="form-control" required
                                   value="<?= htmlspecialchars($evenement['titre'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Catégorie</label>
                            <select name="categorie_id" class="form-select">
                                <option value="">-- Choisir --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id']; ?>"
                                        <?= ((string)($evenement['categorie_id'] ?? '') === (string)$cat['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($cat['nom'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($evenement['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date_evenement" class="form-control" required
                                   value="<?= htmlspecialchars($evenement['date_evenement'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Heure début</label>
                            <input type="time" name="heure_debut" class="form-control"
                                   value="<?= htmlspecialchars($evenement['heure_debut'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Heure fin</label>
                            <input type="time" name="heure_fin" class="form-control"
                                   value="<?= htmlspecialchars($evenement['heure_fin'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lieu <span class="text-danger">*</span></label>
                            <input type="text" name="lieu" class="form-control" required
                                   value="<?= htmlspecialchars($evenement['lieu'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Contact email</label>
                            <input type="email" name="contact_email" class="form-control"
                                   value="<?= htmlspecialchars($evenement['contact_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Contact téléphone</label>
                            <input type="text" name="contact_phone" class="form-control"
                                   value="<?= htmlspecialchars($evenement['contact_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Nb max participants</label>
                            <input type="number" name="nb_max_participants" class="form-control" min="1"
                                   value="<?= (int)($evenement['nb_max_participants'] ?? 50); ?>">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Nouvelle image (optionnel)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <?php if (!empty($evenement['image'])): ?>
                                <div class="mt-2">
                                    <img src="<?= htmlspecialchars($evenement['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Image"
                                         style="max-height:120px;border-radius:8px;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Localisation (cliquez sur la carte)</label>
                            <div id="map" style="height: 360px; border-radius: 8px;"></div>
                            <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($evenement['latitude'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($evenement['longitude'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-12 mt-3">
                            <button class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Enregistrer
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let lat = <?= json_encode($evenement['latitude'] ?? 33.5731) ?>;
let lng = <?= json_encode($evenement['longitude'] ?? -7.5898) ?>;

lat = parseFloat(lat) || 33.5731;
lng = parseFloat(lng) || -7.5898;

let map = L.map('map').setView([lat, lng], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
}).addTo(map);

let marker = L.marker([lat, lng]).addTo(map);

map.on('click', function(e) {
    const lat2 = e.latlng.lat;
    const lng2 = e.latlng.lng;

    document.getElementById('latitude').value = lat2;
    document.getElementById('longitude').value = lng2;

    if (marker) map.removeLayer(marker);
    marker = L.marker([lat2, lng2]).addTo(map);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
