<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
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
$evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$evenements_json = json_encode($evenements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div id="map" style="height: 600px; width: 100%; border-radius: 8px;"></div>
                    </div>
                </div>
                <div class="small text-muted mt-2">
                    Astuce : entrez votre adresse à droite pour afficher les événements les plus proches.
                </div>
            </div>

            <div class="col-lg-4">

                <!-- ✅ Recherche par adresse -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-geo"></i> Événements proches</h6>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Votre adresse</label>
                        <input id="userAddress" type="text" class="form-control"
                               placeholder="Ex: Avenue Mohammed V, Casablanca">

                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <label class="form-label">Rayon (km)</label>
                                <select id="radiusKm" class="form-select">
                                    <option value="2">2 km</option>
                                    <option value="5">5 km</option>
                                    <option value="10" selected>10 km</option>
                                    <option value="20">20 km</option>
                                    <option value="50">50 km</option>
                                </select>
                            </div>
                            <div class="col-6 d-flex align-items-end gap-2">
                                <button id="btnSearchNearby" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i>
                                </button>
                                <button id="btnUseMyLocation" class="btn btn-outline-secondary w-100" title="Utiliser ma position">
                                    <i class="bi bi-crosshair"></i>
                                </button>
                            </div>
                        </div>

                        <div id="nearbyInfo" class="mt-2 small text-muted"></div>
                    </div>
                </div>

                <!-- ✅ Liste des événements filtrés -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul"></i>
                            <span id="eventsCountLabel">Événements (<?= count($evenements); ?>)</span>
                        </h5>
                    </div>

                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <div id="eventsList" class="list-group list-group-flush">
                            <?php if (empty($evenements)): ?>
                                <div class="list-group-item">
                                    <p class="text-muted mb-0">Aucun événement disponible</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($evenements as $index => $event): ?>
                                    <a href="#" class="list-group-item list-group-item-action event-item"
                                       data-index="<?= (int)$index ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($event['titre']); ?></h6>
                                            <small class="badge bg-primary"><?= htmlspecialchars($event['categorie_nom'] ?? ''); ?></small>
                                        </div>
                                        <p class="mb-1 small">
                                            <i class="bi bi-geo-alt text-danger"></i> <?= htmlspecialchars($event['lieu']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($event['date_evenement'])); ?>
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

// --- MAP INIT
const map = L.map('map').setView([33.5731, -7.5898], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors',
  maxZoom: 19
}).addTo(map);

// --- ICONS
const eventIcon = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

const userIcon = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

// Stockage des marqueurs (index = index event)
const markers = new Array(events.length).fill(null);
let userMarker = null;

// --- Ajout markers events (avec fallback géocodage)
events.forEach((ev, index) => {
  if (ev.latitude && ev.longitude) {
    addEventMarker(parseFloat(ev.latitude), parseFloat(ev.longitude), ev, index);
  } else {
    geocodeEventAddress(ev, index);
  }
});

async function geocodeEventAddress(ev, index) {
  const address = encodeURIComponent((ev.lieu || '') + ', Maroc');
  const url = `https://nominatim.openstreetmap.org/search?q=${address}&format=json&limit=1`;
  try {
    const response = await fetch(url);
    const data = await response.json();
    if (data && data.length > 0) {
      const lat = parseFloat(data[0].lat);
      const lng = parseFloat(data[0].lon);
      addEventMarker(lat, lng, ev, index);
    }
  } catch (e) {
    console.error('Erreur géocodage event:', e);
  }
}

function addEventMarker(lat, lng, ev, index) {
  const marker = L.marker([lat, lng], { icon: eventIcon }).addTo(map);

  const popupContent = `
    <div style="min-width: 250px;">
      <h6><strong>${escapeHtml(ev.titre || '')}</strong></h6>
      <p class="mb-1 small"><span class="badge bg-primary">${escapeHtml(ev.categorie_nom || '')}</span></p>
      <p class="mb-1 small"><i class="bi bi-geo-alt"></i> ${escapeHtml(ev.lieu || '')}</p>
      <p class="mb-1 small"><i class="bi bi-calendar3"></i> ${formatDate(ev.date_evenement)}</p>
      <p class="mb-1 small"><i class="bi bi-clock"></i> ${(ev.heure_debut || '').substring(0, 5)}</p>
      <p class="mb-2 small"><i class="bi bi-people"></i> ${ev.nb_inscrits ?? 0} / ${ev.nb_max_participants ?? 0}</p>
      <a href="details_evenement.php?id=${ev.id}" class="btn btn-primary btn-sm w-100">
        <i class="bi bi-info-circle"></i> Voir détails
      </a>
    </div>
  `;

  marker.bindPopup(popupContent);
  markers[index] = { marker, lat, lng, ev };
}

// --- Helpers
function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('fr-FR', { year:'numeric', month:'long', day:'numeric' });
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, s => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[s]));
}

function haversineKm(lat1, lon1, lat2, lon2) {
  const R = 6371;
  const toRad = (x) => x * Math.PI / 180;
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a =
    Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
    Math.sin(dLon/2) * Math.sin(dLon/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}

// --- UI list render (filtré)
function renderList(indices, withDistanceMap = null) {
  const list = document.getElementById('eventsList');
  list.innerHTML = '';

  document.getElementById('eventsCountLabel').textContent = `Événements (${indices.length})`;

  if (indices.length === 0) {
    list.innerHTML = `
      <div class="list-group-item">
        <p class="text-muted mb-0">Aucun événement dans ce rayon</p>
      </div>
    `;
    return;
  }

  indices.forEach((idx) => {
    const ev = events[idx];
    const dist = withDistanceMap ? withDistanceMap.get(idx) : null;

    const a = document.createElement('a');
    a.href = '#';
    a.className = 'list-group-item list-group-item-action event-item';
    a.dataset.index = String(idx);

    a.innerHTML = `
      <div class="d-flex w-100 justify-content-between">
        <h6 class="mb-1">${escapeHtml(ev.titre || '')}</h6>
        <small class="badge bg-primary">${escapeHtml(ev.categorie_nom || '')}</small>
      </div>
      <p class="mb-1 small">
        <i class="bi bi-geo-alt text-danger"></i> ${escapeHtml(ev.lieu || '')}
      </p>
      <small class="text-muted">
        <i class="bi bi-calendar3"></i> ${new Date(ev.date_evenement).toLocaleDateString('fr-FR')}
        ${dist !== null ? ` • <span class="fw-semibold">${dist.toFixed(2)} km</span>` : ''}
      </small>
    `;

    a.addEventListener('click', (e) => {
      e.preventDefault();
      const obj = markers[idx];
      if (obj) {
        map.setView(obj.marker.getLatLng(), 15);
        obj.marker.openPopup();
      }
    });

    list.appendChild(a);
  });
}

// --- Recherche par adresse
async function geocodeUserAddress(address) {
  const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(address)}`;
  const res = await fetch(url);
  const data = await res.json();
  if (!data || !data.length) return null;
  return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
}

function setUserMarker(lat, lng, label = "Votre position") {
  if (userMarker) map.removeLayer(userMarker);
  userMarker = L.marker([lat, lng], { icon: userIcon }).addTo(map);
  userMarker.bindPopup(`<strong>${label}</strong>`).openPopup();
  map.setView([lat, lng], 13);
}

function filterNearby(lat, lng, radiusKm) {
  const distMap = new Map();
  const indices = [];

  for (let i = 0; i < markers.length; i++) {
    const obj = markers[i];
    if (!obj) continue; // pas encore géocodé
    const d = haversineKm(lat, lng, obj.lat, obj.lng);
    if (d <= radiusKm) {
      distMap.set(i, d);
      indices.push(i);
    }
  }

  indices.sort((a, b) => distMap.get(a) - distMap.get(b));
  renderList(indices, distMap);

  return { count: indices.length, distMap };
}

document.getElementById('btnSearchNearby').addEventListener('click', async () => {
  const address = document.getElementById('userAddress').value.trim();
  const radius = parseFloat(document.getElementById('radiusKm').value || '10');

  if (!address) {
    alert("Veuillez saisir votre adresse.");
    return;
  }

  document.getElementById('nearbyInfo').textContent = "Recherche de l'adresse...";
  const coords = await geocodeUserAddress(address);

  if (!coords) {
    document.getElementById('nearbyInfo').textContent = "";
    alert("Adresse introuvable. Essayez une adresse plus précise.");
    return;
  }

  setUserMarker(coords.lat, coords.lng, "Votre adresse");
  const r = filterNearby(coords.lat, coords.lng, radius);

  document.getElementById('nearbyInfo').textContent =
    `${r.count} événement(s) dans un rayon de ${radius} km.`;
});

document.getElementById('btnUseMyLocation').addEventListener('click', () => {
  if (!navigator.geolocation) {
    alert("La géolocalisation n'est pas supportée par ce navigateur.");
    return;
  }

  document.getElementById('nearbyInfo').textContent = "Récupération de votre position...";
  navigator.geolocation.getCurrentPosition((pos) => {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;
    const radius = parseFloat(document.getElementById('radiusKm').value || '10');

    setUserMarker(lat, lng, "Votre position (GPS)");
    const r = filterNearby(lat, lng, radius);

    document.getElementById('nearbyInfo').textContent =
      `${r.count} événement(s) dans un rayon de ${radius} km.`;
  }, (err) => {
    document.getElementById('nearbyInfo').textContent = "";
    alert("Impossible de récupérer votre position : " + err.message);
  }, { enableHighAccuracy: true, timeout: 8000 });
});

// Au chargement : liste complète (comme avant)
renderList(events.map((_, idx) => idx));
</script>

<?php include '../includes/footer.php'; ?>

