<?php
$pageTitle = 'Accueil - Gestion des Événements';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /gestion-evenements/admin/');
        exit;
    } else {
        header('Location: /gestion-evenements/user/');
        exit;
    }
}
?>

<div class="hero-section bg-primary text-white text-center py-5">
    <div class="container">
        <h1 class="display-4 mb-4">
            <i class="bi bi-calendar-event"></i> Bienvenue sur notre plateforme de gestion d'événements
        </h1>
        <p class="lead mb-4">Découvrez, inscrivez-vous et participez aux événements qui vous intéressent</p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="login.php" class="btn btn-light btn-lg">
                <i class="bi bi-box-arrow-in-right"></i> Se connecter
            </a>
            <a href="register.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-person-plus"></i> S'inscrire
            </a>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-check display-1 text-primary mb-3"></i>
                    <h5 class="card-title">Événements variés</h5>
                    <p class="card-text">Découvrez une grande diversité d’événements, allant des conférences professionnelles aux ateliers pratiques, en passant par les réunions et d’autres activités enrichissantes.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-clock-history display-1 text-success mb-3"></i>
                    <h5 class="card-title">Inscription facile</h5>
                    <p class="card-text">Profitez d’un système d’inscription simple et rapide. En quelques clics, vous pouvez réserver votre place pour les événements qui vous intéressent.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-bell display-1 text-warning mb-3"></i>
                    <h5 class="card-title">Notifications</h5>
                    <p class="card-text">Restez informé en temps réel grâce aux notifications. Recevez des alertes dès qu’un événement s’approche ou qu’une nouveauté est disponible.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>