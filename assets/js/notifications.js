document.addEventListener("DOMContentLoaded", () => {
  const bell = document.getElementById("notifBell");
  const panel = document.getElementById("notifPanel");
  const overlay = document.getElementById("notifPanelOverlay");
  const closeBtn = document.getElementById("notifClose");
  const badge = document.getElementById("notifBadge");

  if (!bell || !panel) return;

  const openPanel = async () => {
    panel.classList.add("open");
    overlay?.classList.add("open");
    panel.setAttribute("aria-hidden", "false");

    // ✅ marque tout comme lu dès l'ouverture (et badge -> 0)
    try {
      const res = await fetch("/gestion-evenements/user/api/notifications_actions.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=mark_all_read",
      });
      const data = await res.json();
      if (data?.ok) {
        if (badge) {
          badge.textContent = "0";
          badge.classList.add("d-none");
        }
        // Enlève le style unread visuellement
        panel.querySelectorAll(".notif-item.unread").forEach(el => el.classList.remove("unread"));
      }
    } catch (e) {}
  };

  const closePanel = () => {
    panel.classList.remove("open");
    overlay?.classList.remove("open");
    panel.setAttribute("aria-hidden", "true");
  };

  bell.addEventListener("click", (e) => {
    e.preventDefault();
    if (panel.classList.contains("open")) closePanel();
    else openPanel();
  });

  closeBtn?.addEventListener("click", closePanel);
  overlay?.addEventListener("click", closePanel);

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closePanel();
  });

  // ✅ quand tu cliques sur une notif : la marquer lue + naviguer
  panel.addEventListener("click", (e) => {
    const a = e.target.closest(".notif-item");
    if (!a) return;

    const id = a.getAttribute("data-id");
    if (!id) return;

    // keepalive pour ne pas bloquer la navigation
    fetch("/gestion-evenements/user/api/notifications_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "action=mark_read&id=" + encodeURIComponent(id),
      keepalive: true,
    }).catch(() => {});
  });
});
