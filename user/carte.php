<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: /gestion-evenements/login.php');
    exit;
}

require_once '../classes/Evenement.php';

$evenementObj = new Evenement();

$sql = "SELECT e.*, c.nom as categorie_nom, 
        (SELECT COUNT(*) FROM inscriptions WHERE evenement_id = e.id AND statut = 'confirme') as nb_inscrits
        FROM evenements e 
        LEFT JOIN categories c ON e.categorie_id = c.id 
        WHERE e.statut = 'actif'
        ORDER BY e.date_evenement ASC";

$pdo = $evenementObj->getConnection();
$stmt = $pdo->query($sql);
$evenements = $stmt->fetchAll();

$evenements_json = json_encode($evenements);

$pageTitle = 'Carte des Événements';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="main-content">
    <div class="container-fluid my-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4"><i class="bi bi-geo-alt"></i> Carte des Événements</h2>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div id="map" style="height: 600px; width: 100%; border-radius: 8px;"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Événements (<?php echo count($evenements); ?>)</h5>
                    </div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <div class="list-group list-group-flush">
                            <?php if (empty($evenements)): ?>
                                <div class="list-group-item">
                                    <p class="text-muted mb-0">Aucun événement disponible</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($evenements as $index => $event): ?>
                                    <a href="#" class="list-group-item list-group-item-action event-item" 
                                       data-index="<?php echo $index; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($event['titre']); ?></h6>
                                            <small class="badge bg-primary"><?php echo htmlspecialchars($event['categorie_nom']); ?></small>
                                        </div>
                                        <p class="mb-1 small">
                                            <i class="bi bi-geo-alt text-danger"></i> <?php echo htmlspecialchars($event['lieu']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3"></i> <?php echo date('d/m/Y', strtotime($event['date_evenement'])); ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const events = <?php echo $evenements_json; ?>;

const map = L.map('map').setView([33.5731, -7.5898], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
}).addTo(map);

const customIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

const markers = [];

// Ajouter les marqueurs
events.forEach((event, index) => {
    if (event.latitude && event.longitude) {
        // Utiliser les coordonnées de la BDD
        addMarker(parseFloat(event.latitude), parseFloat(event.longitude), event, index);
    } else {
        // Fallback: géocoder l'adresse
        geocodeAddress(event, index);
    }
});

async function geocodeAddress(event, index) {
    const address = encodeURIComponent(event.lieu + ', Maroc');
    const url = `https://nominatim.openstreetmap.org/search?q=${address}&format=json&limit=1`;
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data && data.length > 0) {
            addMarker(parseFloat(data[0].lat), parseFloat(data[0].lon), event, index);
        }
    } catch (error) {
        console.error('Erreur géocodage:', error);
    }
}

function addMarker(lat, lon, event, index) {
    const marker = L.marker([lat, lon], { icon: customIcon }).addTo(map);
    
    const popupContent = `
        <div style="min-width: 250px;">
            <h6><strong>${event.titre}</strong></h6>
            <p class="mb-1 small"><span class="badge bg-primary">${event.categorie_nom}</span></p>
            <p class="mb-1 small"><i class="bi bi-geo-alt"></i> ${event.lieu}</p>
            <p class="mb-1 small"><i class="bi bi-calendar3"></i> ${formatDate(event.date_evenement)}</p>
            <p class="mb-1 small"><i class="bi bi-clock"></i> ${event.heure_debut.substring(0, 5)}</p>
            <p class="mb-2 small"><i class="bi bi-people"></i> ${event.nb_inscrits} / ${event.nb_max_participants}</p>
            <a href="details_evenement.php?id=${event.id}" class="btn btn-primary btn-sm w-100">
                <i class="bi bi-info-circle"></i> Voir détails
            </a>
        </div>
    `;
    
    marker.bindPopup(popupContent);
    markers.push({ marker: marker, event: event });
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

document.querySelectorAll('.event-item').forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        const index = parseInt(item.dataset.index);
        
        if (markers[index]) {
            map.setView(markers[index].marker.getLatLng(), 15);
            markers[index].marker.openPopup();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
