<?php
// /gestion-evenements/user/index.php

session_start();

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'user')) {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once __DIR__ . '/../classes/Database.php';

// ✅ Page title via traduction
$pageTitleKey = 'available_events';
$cssPath = '/gestion-evenements/assets/css/style.css';

// ✅ Inputs filtres
$q          = trim($_GET['q'] ?? '');
$categorie  = (int)($_GET['categorie'] ?? 0);
$sort       = $_GET['sort'] ?? 'date_asc';

$db  = new Database();
$pdo = $db->getConnection();

// ✅ Charger catégories (pour le select)
$catsStmt = $pdo->query("SELECT id, nom FROM categories ORDER BY nom ASC");
$categories = $catsStmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Construction requête événements
$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(e.titre LIKE :q OR e.description LIKE :q OR e.lieu LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

if ($categorie > 0) {
    $where[] = "e.categorie_id = :cat";
    $params[':cat'] = $categorie;
}

$whereSql = !empty($where) ? ("WHERE " . implode(" AND ", $where)) : "";

// ✅ Tri
switch ($sort) {
    case 'date_desc':
        $orderSql = "ORDER BY e.date_evenement DESC, e.heure_debut DESC";
        break;
    case 'title_asc':
        $orderSql = "ORDER BY e.titre ASC";
        break;
    case 'title_desc':
        $orderSql = "ORDER BY e.titre DESC";
        break;
    case 'date_asc':
    default:
        $orderSql = "ORDER BY e.date_evenement ASC, e.heure_debut ASC";
        break;
}

// ✅ Requête events + catégorie + places (inscrits confirmés)
$sql = "
    SELECT
        e.id,
        e.titre,
        e.description,
        e.date_evenement,
        e.heure_debut,
        e.heure_fin,
        e.lieu,
        e.image,
        e.nb_max_participants,
        c.nom AS categorie,
        (
            SELECT COUNT(*)
            FROM inscriptions i
            WHERE i.evenement_id = e.id
              AND i.statut = 'confirme'
        ) AS nb_inscrits
    FROM evenements e
    LEFT JOIN categories c ON c.id = e.categorie_id
    $whereSql
    $orderSql
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container my-5">

        <div class="d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-calendar-event fs-3"></i>
            <h2 class="mb-0"><?= htmlspecialchars(t('available_events'), ENT_QUOTES, 'UTF-8') ?></h2>
        </div>

        <!-- ✅ Barre filtres -->
        <form method="GET" class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">

                    <div class="col-md-5">
                        <label class="form-label fw-semibold"><?= htmlspecialchars(t('search'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="<?= htmlspecialchars(t('search_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold"><?= htmlspecialchars(t('category'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="categorie" class="form-select">
                            <option value="0"><?= htmlspecialchars(t('all_categories'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>" <?= ($categorie === (int)$cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold"><?= htmlspecialchars(t('sort_by'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="sort" class="form-select">
                            <option value="date_asc"  <?= $sort === 'date_asc' ? 'selected' : '' ?>><?= htmlspecialchars(t('sort_date_asc'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>><?= htmlspecialchars(t('sort_date_desc'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : '' ?>><?= htmlspecialchars(t('sort_title_asc'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="title_desc" <?= $sort === 'title_desc' ? 'selected' : '' ?>><?= htmlspecialchars(t('sort_title_desc'), ENT_QUOTES, 'UTF-8') ?></option>
                        </select>
                    </div>

                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-funnel"></i> <?= htmlspecialchars(t('filter'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>

                </div>

                <div class="mt-3">
                    <a class="btn btn-sm btn-outline-secondary" href="/gestion-evenements/user/">
                        <i class="bi bi-arrow-counterclockwise"></i> <?= htmlspecialchars(t('reset_filters'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
            </div>
        </form>

        <?php if (empty($events)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <?= htmlspecialchars(t('no_events'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php else: ?>

            <div class="row g-4">
                <?php foreach ($events as $e): ?>
                    <?php
                        $nbMax = (int)($e['nb_max_participants'] ?? 0);
                        $nbIns = (int)($e['nb_inscrits'] ?? 0);
                        $dateFr = date('d/m/Y', strtotime($e['date_evenement']));
                        $heure  = !empty($e['heure_debut']) ? substr($e['heure_debut'], 0, 5) : '';
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0">

                            <?php if (!empty($e['image'])): ?>
                                <img src="<?= htmlspecialchars($e['image'], ENT_QUOTES, 'UTF-8') ?>"
                                     class="card-img-top"
                                     style="height:200px;object-fit:cover;"
                                     alt="<?= htmlspecialchars($e['titre'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center"
                                     style="height:200px;">
                                    <i class="bi bi-calendar-event display-4"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <?php if (!empty($e['categorie'])): ?>
                                    <span class="badge bg-primary mb-2">
                                        <?= htmlspecialchars($e['categorie'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>

                                <h5 class="card-title">
                                    <?= htmlspecialchars($e['titre'], ENT_QUOTES, 'UTF-8') ?>
                                </h5>

                                <p class="card-text small mb-3">
                                    <?= htmlspecialchars(mb_strimwidth(strip_tags($e['description'] ?? ''), 0, 110, '...'), ENT_QUOTES, 'UTF-8') ?>
                                </p>

                                <ul class="list-unstyled small mb-0">
                                    <li><i class="bi bi-calendar3"></i> <?= htmlspecialchars($dateFr, ENT_QUOTES, 'UTF-8') ?></li>
                                    <li><i class="bi bi-clock"></i> <?= htmlspecialchars($heure, ENT_QUOTES, 'UTF-8') ?></li>
                                    <li><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($e['lieu'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
                                    <li><i class="bi bi-people"></i> <?= (int)$nbIns ?> / <?= (int)$nbMax ?></li>
                                </ul>
                            </div>

                            <div class="card-footer bg-white border-0">
                                <a class="btn btn-primary w-100"
                                   href="/gestion-evenements/user/details_evenement.php?id=<?= (int)$e['id'] ?>">
                                    <i class="bi bi-info-circle"></i> <?= htmlspecialchars(t('view_details'), ENT_QUOTES, 'UTF-8') ?>
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
