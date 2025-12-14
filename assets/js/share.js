// /gestion-evenements/assets/js/share.js

document.addEventListener('DOMContentLoaded', () => {
  const data = window.SHARE_DATA || {};
  const url = data.url || '';
  const title = data.title || 'Événement';

  const copyBtn = document.getElementById('copyShareLink');
  const linkInput = document.getElementById('shareLink');

  const wa = document.getElementById('shareWhatsapp');
  const ms = document.getElementById('shareMessenger');
  const em = document.getElementById('shareEmail');

  if (wa) {
    const text = encodeURIComponent(`${title}\n${url}`);
    wa.href = `https://wa.me/?text=${text}`;
  }

  if (ms) {
    ms.href = `https://www.facebook.com/dialog/share?display=popup&href=${encodeURIComponent(url)}`;
  }

  if (em) {
    const subject = encodeURIComponent(`Partager : ${title}`);
    const body = encodeURIComponent(`${title}\n\n${url}`);
    em.href = `mailto:?subject=${subject}&body=${body}`;
  }

  if (copyBtn && linkInput) {
    copyBtn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(linkInput.value);
        copyBtn.innerHTML = '<i class="bi bi-check2"></i>';
        setTimeout(() => (copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>'), 1200);
      } catch (e) {
        linkInput.select();
        document.execCommand('copy');
      }
    });
  }
});
