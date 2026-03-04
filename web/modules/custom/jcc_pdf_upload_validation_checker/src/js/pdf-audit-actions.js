(function (Drupal, once) {
  Drupal.behaviors.pdfAuditActions = {
    attach(context) {
      once('pdf-audit-actions', '.js-pdf-audit-pending', context).forEach((el) => {
        el.addEventListener('click', async (e) => {
          e.preventDefault();

          const fid = el.getAttribute('data-fid');
          const url = el.getAttribute('href');

          try {
            const url = el.getAttribute('href');

            const tokenResp = await fetch('/session/token', { credentials: 'same-origin' });
            const csrfToken = (await tokenResp.text()).trim();

            const resp = await fetch(url, {
              method: 'POST',
              credentials: 'same-origin',
              headers: {
                'X-CSRF-Token': csrfToken,
                'Accept': 'application/json',
              },
            });

            const json = await resp.json().catch(() => ({}));

            if (!resp.ok || json.ok === false) {
              alert(json.message || `Failed to queue audit for fid ${fid}.`);
              return;
            }

            location.reload();
          }
          catch (err) {
            console.error(err);
            alert('Error queueing audit.');
          }
        });
      });
    },
  };
})(Drupal, once);
