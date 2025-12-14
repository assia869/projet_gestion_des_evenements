<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once '../classes/Evenement.php';
require_once '../user/google_maps.php';

$evenementObj = new Evenement();

// Récupérer tous les événements (actifs, annulés, terminés) pour l'admin
$sql = "SELECT e.*, c.nom as categorie_nom, 
        (SELECT COUNT(*) FROM inscriptions WHERE evenement_id = e.id AND statut = 'confirme') as nb_inscrits
        FROM evenements e 
        LEFT JOIN categories c ON e.categorie_id = c.id 
        ORDER BY e.date_evenement DESC";

$pdo = $evenementObj->getConnection();
$stmt = $pdo->query($sql);
$evenements = $stmt->fetchAll();

// Convertir les événements en JSON pour JavaScript
$evenements_json = json_encode($evenements);

$pageTitle = 'Carte des Événements - Admin';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid my-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-geo-alt-fill"></i> Carte des Événements</h2>
                    <div>
                        <a href="evenements.php" class="btn btn-outline-primary">
                            <i class="bi bi-list"></i> Liste des événements
                        </a>
                        <a href="ajouter_evenement.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouvel événement
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FILTRES -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">🎯 Statut</label>
                                <select class="form-select" id="filterStatut">
                                    <option value="">Tous les statuts</option>
                                    <option value="actif" selected>Actifs uniquement</option>
                                    <option value="annule">Annulés</option>
                                    <option value="termine">Terminés</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">📊 Affichage</label>
                                <select class="form-select" id="displayMode">
                                    <option value="all">Tous les marqueurs</option>
                                    <option value="cluster">Grouper les marqueurs</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">📈 Statistiques</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="showStats" checked>
                                    <label class="form-check-label" for="showStats">
                                        Afficher les stats
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary w-100" onclick="applyFilters()">
                                    <i class="bi bi-funnel"></i> Appliquer les filtres
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- STATISTIQUES -->
            <div class="col-lg-3">
                <div class="card shadow-sm mb-4" id="statsCard">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Statistiques</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Total des événements</small>
                            <h3 class="mb-0 text-primary"><?php echo count($evenements); ?></h3>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Événements actifs</small>
                            <h4 class="mb-0 text-success">
                                <?php 
                                $actifs = array_filter($evenements, fn($e) => $e['statut'] === 'actif');
                                echo count($actifs);
                                ?>
                            </h4>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Événements annulés</small>
                            <h4 class="mb-0 text-danger">
                                <?php 
                                $annules = array_filter($evenements, fn($e) => $e['statut'] === 'annule');
                                echo count($annules);
                                ?>
                            </h4>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Événements terminés</small>
                            <h4 class="mb-0 text-secondary">
                                <?php 
                                $termines = array_filter($evenements, fn($e) => $e['statut'] === 'termine');
                                echo count($termines);
                                ?>
                            </h4>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-0">
                            <small class="text-muted">Total des inscriptions</small>
                            <h4 class="mb-0 text-info">
                                <?php 
                                $total_inscrits = array_sum(array_column($evenements, 'nb_inscrits'));
                                echo $total_inscrits;
                                ?>
                            </h4>
                        </div>
                    </div>
                </div>
                
                <!-- LÉGENDE -->
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bi bi-bookmark"></i> Légende</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <img src="http://maps.google.com/mapfiles/ms/icons/green-dot.png" width="20" class="me-2">
                            <small>Événements actifs</small>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <img src="http://maps.google.com/mapfiles/ms/icons/red-dot.png" width="20" class="me-2">
                            <small>Événements annulés</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <img src="http://maps.google.com/mapfiles/ms/icons/blue-dot.png" width="20" class="me-2">
                            <small>Événements terminés</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CARTE -->
            <div class="col-lg-9">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div id="map" style="height: 700px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- LISTE DES ÉVÉNEMENTS SUR LA CARTE -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-pin-map"></i> Événements géolocalisés</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Titre</th>
                                        <th>Lieu</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Inscrits</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="eventsTable">
                                    <?php foreach ($evenements as $event): ?>
                                        <tr data-event-id="<?php echo $event['id']; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($event['titre']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($event['categorie_nom']); ?></small>
                                            </td>
                                            <td>
                                                <i class="bi bi-geo-alt text-danger"></i> 
                                                <?php echo htmlspecialchars($event['lieu']); ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($event['date_evenement'])); ?></td>
                                            <td>
                                                <?php if ($event['statut'] === 'actif'): ?>
                                                    <span class="badge bg-success">Actif</span>
                                                <?php elseif ($event['statut'] === 'annule'): ?>
                                                    <span class="badge bg-danger">Annulé</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Terminé</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $event['nb_inscrits']; ?> / <?php echo $event['nb_max_participants']; ?>
                                            </td>
                                            <td>
                                                <a href="modifier_evenement.php?id=<?php echo $event['id']; ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="inscrits.php?event_id=<?php echo $event['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="bi bi-people"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&libraries=places"></script>

<script>
let map;
let markers = [];
let infoWindows = [];
const events = <?php echo $evenements_json; ?>;

// Initialiser la carte
function initMap() {
    const defaultCenter = { 
        lat: <?php echo MAP_DEFAULT_LAT; ?>, 
        lng: <?php echo MAP_DEFAULT_LNG; ?> 
    };
    
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: <?php echo MAP_DEFAULT_ZOOM; ?>,
        center: defaultCenter,
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true,
        mapTypeId: 'roadmap'
    });
    
    // Ajouter un marqueur pour chaque événement
    events.forEach((event, index) => {
        geocodeAddress(event, index);
    });
}

// Géocoder une adresse
function geocodeAddress(event, index) {
    const geocoder = new google.maps.Geocoder();
    const address = event.lieu;
    
    geocoder.geocode({ address: address + ', Maroc' }, (results, status) => {
        if (status === 'OK') {
            addMarker(results[0].geometry.location, event, index);
        } else {
            console.error('Geocoding failed for: ' + address);
        }
    });
}

// Ajouter un marqueur
function addMarker(location, event, index) {
    // Couleur selon le statut
    let iconColor = 'green'; // actif
    if (event.statut === 'annule') iconColor = 'red';
    if (event.statut === 'termine') iconColor = 'blue';
    
    const marker = new google.maps.Marker({
        position: location,
        map: map,
        title: event.titre,
        animation: google.maps.Animation.DROP,
        icon: {
            url: `http://maps.google.com/mapfiles/ms/icons/${iconColor}-dot.png`
        }
    });
    
    // Contenu de la fenêtre
    const contentString = `
        <div style="max-width: 350px;">
            <h6 class="mb-2">${event.titre}</h6>
            <p class="mb-1 small"><strong>Statut:</strong> 
                <span class="badge bg-${event.statut === 'actif' ? 'success' : event.statut === 'annule' ? 'danger' : 'secondary'}">
                    ${event.statut}
                </span>
            </p>
            <p class="mb-1 small"><strong>Catégorie:</strong> ${event.categorie_nom}</p>
            <p class="mb-1 small"><strong>📍 Lieu:</strong> ${event.lieu}</p>
            <p class="mb-1 small"><strong>📅 Date:</strong> ${formatDate(event.date_evenement)}</p>
            <p class="mb-1 small"><strong>🕐 Heure:</strong> ${event.heure_debut.substring(0, 5)}</p>
            <p class="mb-2 small"><strong>👥 Inscrits:</strong> ${event.nb_inscrits} / ${event.nb_max_participants}</p>
            <div class="btn-group w-100">
                <a href="modifier_evenement.php?id=${event.id}" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
                <a href="inscrits.php?event_id=${event.id}" class="btn btn-info btn-sm">
                    <i class="bi bi-people"></i> Inscrits
                </a>
            </div>
        </div>
    `;
    
    const infoWindow = new google.maps.InfoWindow({
        content: contentString
    });
    
    marker.addListener('click', () => {
        infoWindows.forEach(iw => iw.close());
        infoWindow.open(map, marker);
    });
    
    markers.push(marker);
    infoWindows.push(infoWindow);
}

// Formater la date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

// Appliquer les filtres
function applyFilters() {
    const statutFilter = document.getElementById('filterStatut').value;
    
    markers.forEach((marker, index) => {
        const event = events[index];
        
        if (statutFilter === '' || event.statut === statutFilter) {
            marker.setVisible(true);
        } else {
            marker.setVisible(false);
        }
    });
}

// Afficher/masquer les statistiques
document.getElementById('showStats')?.addEventListener('change', function() {
    const statsCard = document.getElementById('statsCard');
    if (this.checked) {
        statsCard.style.display = 'block';
    } else {
        statsCard.style.display = 'none';
    }
});

// Initialiser la carte
window.onload = initMap;
</script>

<style>
#map {
    border-radius: 8px;
}

.table tr:hover {
    cursor: pointer;
}
</style>

<?php include '../includes/footer.php'; ?>
