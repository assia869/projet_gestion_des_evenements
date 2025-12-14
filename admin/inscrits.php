<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once '../classes/Evenement.php';
require_once '../classes/Inscription.php';

$evenementId = $_GET['id'] ?? 0;

$evenementObj = new Evenement();
$evenement = $evenementObj->getEvenementById($evenementId);

if (!$evenement) {
    header('Location: evenements.php');
    exit;
}

$inscriptionObj = new Inscription();
$inscrits = $inscriptionObj->getInscritsByEvenement($evenementId);

$pageTitle = 'Liste des Inscrits';
$cssPath = '/gestion-evenements/assets/css/style.css';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <!-- En-tête avec infos événement -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-calendar-event"></i> <?php echo htmlspecialchars($evenement['titre']); ?></h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong><i class="bi bi-calendar3"></i> Date :</strong><br>
                    <?php echo date('d/m/Y', strtotime($evenement['date_evenement'])); ?>
                </div>
                <div class="col-md-3">
                    <strong><i class="bi bi-clock"></i> Horaire :</strong><br>
                    <?php echo substr($evenement['heure_debut'], 0, 5); ?> - <?php echo substr($evenement['heure_fin'], 0, 5); ?>
                </div>
                <div class="col-md-3">
                    <strong><i class="bi bi-geo-alt"></i> Lieu :</strong><br>
                    <?php echo htmlspecialchars($evenement['lieu']); ?>
                </div>
                <div class="col-md-3">
                    <strong><i class="bi bi-people"></i> Inscrits :</strong><br>
                    <span class="badge bg-info fs-6">
                        <?php echo count($inscrits); ?> / <?php echo $evenement['nb_max_participants']; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des inscrits -->
    <div class="card shadow">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-people"></i> Liste des Inscrits (<?php echo count($inscrits); ?>)</h5>
            <a href="evenements.php" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($inscrits)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Aucune inscription pour cet événement
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="tableInscrits">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Date d'inscription</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $index = 1; ?>
                            <?php foreach ($inscrits as $inscrit): ?>
                                <tr>
                                    <td><?php echo $index++; ?></td>
                                    <td><?php echo htmlspecialchars($inscrit['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($inscrit['prenom']); ?></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($inscrit['email']); ?>">
                                            <?php echo htmlspecialchars($inscrit['email']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($inscrit['telephone'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($inscrit['date_inscription'])); ?></td>
                                    <td>
                                        <?php 
                                        $badgeClass = 'bg-success';
                                        $statusText = 'Confirmé';
                                        
                                        if ($inscrit['statut'] === 'en_attente') {
                                            $badgeClass = 'bg-warning';
                                            $statusText = 'En attente';
                                        } elseif ($inscrit['statut'] === 'annule') {
                                            $badgeClass = 'bg-danger';
                                            $statusText = 'Annulé';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Boutons d'export -->
                <div class="mt-3">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Imprimer la liste
                    </button>
                    <button class="btn btn-success" onclick="exportToCSV()">
                        <i class="bi bi-file-earmark-excel"></i> Exporter en CSV
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('tableInscrits');
    let csv = [];
    
    // En-têtes
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Données
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
    });
    
    // Téléchargement
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'inscrits_evenement_<?php echo $evenementId; ?>.csv';
    link.click();
}
</script>

<?php 
$jsPath = '/gestion-evenements/assets/js/script.js';
include '../includes/footer.php'; 
?>