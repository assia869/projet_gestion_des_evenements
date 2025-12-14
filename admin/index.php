<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once '../classes/Evenement.php';
require_once '../classes/User.php';

$evenementObj = new Evenement();
$userObj = new User();

$evenements = $evenementObj->getAllEvenements();
$users = $userObj->getAllUsers();

$pdo = $evenementObj->getConnection();
$stmt = $pdo->query("SELECT COUNT(*) as total FROM inscriptions");
$totalInscriptions = $stmt->fetch()['total'];

$pageTitle = 'Dashboard Admin';
$cssPath = '/gestion-evenements/assets/css/style.css';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard Administrateur</h2>
    
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card text-white bg-primary shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase">Événements</h6>
                            <h2 class="mb-0"><?php echo count($evenements); ?></h2>
                        </div>
                        <i class="bi bi-calendar-event display-4"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="evenements.php" class="text-white text-decoration-none">
                        Voir tous <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-white bg-success shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase">Utilisateurs</h6>
                            <h2 class="mb-0"><?php echo count($users); ?></h2>
                        </div>
                        <i class="bi bi-people display-4"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <span class="text-white">Inscrits sur la plateforme</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-white bg-info shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase">Inscriptions</h6>
                            <h2 class="mb-0"><?php echo $totalInscriptions; ?></h2>
                        </div>
                        <i class="bi bi-bookmark-check display-4"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <span class="text-white">Total des inscriptions</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-calendar3"></i> Événements récents</h5>
            <a href="ajouter_evenement.php" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle"></i> Nouveau
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Date</th>
                            <th>Lieu</th>
                            <th>Inscrits</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($evenements, 0, 5) as $evenement): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($evenement['titre']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($evenement['date_evenement'])); ?></td>
                                <td><?php echo htmlspecialchars($evenement['lieu']); ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $evenement['nb_inscrits']; ?> / <?php echo $evenement['nb_max_participants']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="inscrits.php?id=<?php echo $evenement['id']; ?>" 
                                       class="btn btn-sm btn-info" title="Voir inscrits">
                                        <i class="bi bi-people"></i>
                                    </a>
                                    <a href="modifier_evenement.php?id=<?php echo $evenement['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-plus display-1 text-primary mb-3"></i>
                    <h5>Créer un événement</h5>
                    <p>Ajoutez un nouvel événement à la plateforme</p>
                    <a href="ajouter_evenement.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Créer
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-body text-center">
                    <i class="bi bi-tags display-1 text-success mb-3"></i>
                    <h5>Gérer les catégories</h5>
                    <p>Organisez vos événements par catégorie</p>
                    <a href="categories.php" class="btn btn-success">
                        <i class="bi bi-tags"></i> Gérer
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$jsPath = '/gestion-evenements/assets/js/script.js';
include '../includes/footer.php'; 
?>