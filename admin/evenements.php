<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once '../classes/Evenement.php';

$evenementObj = new Evenement();
$evenements = $evenementObj->getAllEvenements();

$pageTitle = 'Gestion des Événements';
$cssPath = '/gestion-evenements/assets/css/style.css';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-calendar3"></i> Gestion des Événements</h2>
        <a href="ajouter_evenement.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Ajouter un événement
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            if ($_GET['success'] == 'add') echo 'Événement ajouté avec succès';
            if ($_GET['success'] == 'edit') echo 'Événement modifié avec succès';
            if ($_GET['success'] == 'delete') echo 'Événement supprimé avec succès';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Date</th>
                            <th>Lieu</th>
                            <th>Inscrits</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($evenements)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <p class="text-muted mt-2">Aucun événement disponible</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($evenements as $evenement): ?>
                                <tr>
                                    <td><?php echo $evenement['id']; ?></td>
                                    <td>
                                        <?php if ($evenement['image']): ?>
                                            <img src="<?php echo htmlspecialchars($evenement['image']); ?>" 
                                                 alt="Image" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($evenement['titre']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($evenement['categorie_nom']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($evenement['date_evenement'])); ?></td>
                                    <td><?php echo htmlspecialchars($evenement['lieu']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $evenement['nb_inscrits']; ?> / <?php echo $evenement['nb_max_participants']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="inscrits.php?id=<?php echo $evenement['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Voir inscrits">
                                                <i class="bi bi-people"></i>
                                            </a>
                                            <a href="modifier_evenement.php?id=<?php echo $evenement['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="supprimer_evenement.php?id=<?php echo $evenement['id']; ?>" 
                                               class="btn btn-sm btn-danger" title="Supprimer"
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
$jsPath = '/gestion-evenements/assets/js/script.js';
include '../includes/footer.php';
 
?>