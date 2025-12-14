<?php
// /gestion-evenements/user/calendrier.php

session_start();

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'user')) {
    header('Location: /gestion-evenements/login.php');
    exit;
}

$pageTitle = 'Calendrier';
$cssPath = '/gestion-evenements/assets/css/style.css';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<link rel="stylesheet" href="/gestion-evenements/assets/css/calendar.css">

<div class="main-content">
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="bi bi-calendar-week"></i> Calendrier</h2>
            <a href="/gestion-evenements/user/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal détails -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="eventModalTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">

          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="fw-semibold mb-2"><i class="bi bi-calendar3"></i> Date</div>
              <div id="eventModalDate"></div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="fw-semibold mb-2"><i class="bi bi-clock"></i> Horaire</div>
              <div id="eventModalTime"></div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="fw-semibold mb-2"><i class="bi bi-geo-alt"></i> Lieu</div>
              <div id="eventModalLocation"></div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="fw-semibold mb-2"><i class="bi bi-tags"></i> Catégorie</div>
              <div id="eventModalCategory"></div>
            </div>
          </div>

          <div class="col-12">
            <div class="border rounded p-3">
              <div class="fw-semibold mb-2"><i class="bi bi-card-text"></i> Description</div>
              <div id="eventModalDesc" class="small"></div>
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <!-- ✅ bouton (PAS <a>) -->
        <button type="button"
                class="btn btn-primary"
                id="eventModalDetailsBtn"
                data-href="">
          <i class="bi bi-box-arrow-up-right"></i> VIEW_EVENT_PAGE
        </button>

        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CLOSE</button>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>
<script src="/gestion-evenements/assets/js/calendar.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
