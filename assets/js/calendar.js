// /gestion-evenements/assets/js/calendar.js

document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');
  if (!calendarEl) return;

  const modalEl = document.getElementById('eventModal');
  const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

  const titleEl = document.getElementById('eventModalTitle');
  const dateEl = document.getElementById('eventModalDate');
  const timeEl = document.getElementById('eventModalTime');
  const locEl = document.getElementById('eventModalLocation');
  const catEl = document.getElementById('eventModalCategory');
  const descEl = document.getElementById('eventModalDesc');

  const detailsBtn = document.getElementById('eventModalDetailsBtn');

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    locale: (document.documentElement.lang || 'fr'),
    navLinks: true,
    nowIndicator: true,
    height: 'auto',

    events: async function (info, successCallback, failureCallback) {
      try {
        const url = `/gestion-evenements/user/api/calendar_events.php?start=${encodeURIComponent(info.startStr)}&end=${encodeURIComponent(info.endStr)}`;
        const res = await fetch(url, { credentials: 'same-origin' });
        const data = await res.json();
        if (!Array.isArray(data)) throw new Error('Bad JSON');
        successCallback(data);
      } catch (e) {
        console.error(e);
        failureCallback(e);
      }
    },

    eventClick: function (arg) {
      // on ne navigue pas automatiquement
      if (arg.jsEvent) arg.jsEvent.preventDefault();

      const ext = arg.event.extendedProps || {};

      // ✅ on récupère TOUJOURS l'id de l’événement
      const eventId =
        (typeof ext.event_id !== 'undefined' && ext.event_id !== null) ? ext.event_id :
        (typeof arg.event.id !== 'undefined' && arg.event.id !== null) ? arg.event.id :
        null;

      const href = eventId ? `/gestion-evenements/user/details_evenement.php?id=${encodeURIComponent(eventId)}` : '';

      if (titleEl) titleEl.textContent = arg.event.title || '';
      if (dateEl) dateEl.textContent = ext.date_label || (ext.date_evenement || '');
      if (timeEl) timeEl.textContent = ext.time_label || (ext.heure_debut ? `${ext.heure_debut}${ext.heure_fin ? ' - ' + ext.heure_fin : ''}` : '');
      if (locEl) locEl.textContent = ext.lieu || '';
      if (catEl) catEl.textContent = ext.categorie || '';
      if (descEl) descEl.textContent = ext.description || '';

      // ✅ on stocke le lien dans le bouton
      if (detailsBtn) {
        detailsBtn.dataset.href = href;
        detailsBtn.disabled = !href;
      }

      if (modal) modal.show();
    }
  });

  calendar.render();

  // ✅ Navigation FORCÉE (ne dépend pas de href="#")
  if (detailsBtn) {
    detailsBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const href = detailsBtn.dataset.href || '';
      if (!href) return;

      // option: fermer le modal puis naviguer
      try {
        if (modal) modal.hide();
      } catch (_) {}

      window.location.href = href;
    });
  }
});
