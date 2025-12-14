<?php
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userName = $isLoggedIn && isset($_SESSION['nom']) && isset($_SESSION['prenom']) ? $_SESSION['nom'] . ' ' . $_SESSION['prenom'] : '';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/gestion-evenements/">
            <i class="bi bi-calendar-event"></i> Gestion Événements
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if ($isLoggedIn): ?>
                    <?php if ($isAdmin): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/gestion-evenements/admin/">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/gestion-evenements/admin/evenements.php">
                                <i class="bi bi-calendar3"></i> Événements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/gestion-evenements/admin/categories.php">
                                <i class="bi bi-tags"></i> Catégories
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/gestion-evenements/user/">
                                <i class="bi bi-house"></i> Accueil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/gestion-evenements/user/mes_inscriptions.php">
                                <i class="bi bi-bookmark-check"></i> Mes Inscriptions
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- TOGGLE MODE SOMBRE/CLAIR -->
                    <li class="nav-item">
                        <a class="nav-link" href="/gestion-evenements/toggle_theme.php" title="Changer de thème">
                            <?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark'): ?>
                                <i class="bi bi-sun-fill"></i> Mode clair
                            <?php else: ?>
                                <i class="bi bi-moon-fill"></i> Mode sombre
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- MENU UTILISATEUR -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($userName); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="dropdown-item text-danger" href="/gestion-evenements/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/gestion-evenements/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/gestion-evenements/register.php">
                            <i class="bi bi-person-plus"></i> Inscription
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>