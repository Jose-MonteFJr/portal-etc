  </div>
  <div aria-live="polite" aria-atomic="true" class="position-relative">
    <div id="toastArea" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>
  </div>
  <script src="/portal-etc/partials/js/script.js"></script>
  <script src="/portal-etc/partials/js/bootstrap.bundle.min.js"></script>
  <script>
    // O tema é armazenado em localStorage
// Lógica unificada para o tema (troca de tema, ícone e notificação)
(function() {
    const themeToggleBtn = document.getElementById('themeToggle');
    const htmlElement = document.documentElement;

    // Função unificada que aplica o tema E o ícone correto
    const applyThemeAndIcon = (theme) => {
        // 1. Aplica o tema (dark/light) na tag <html>
        htmlElement.setAttribute('data-bs-theme', theme);

        // 2. Troca o ícone dentro do botão
        if (themeToggleBtn) {
            const icon = themeToggleBtn.querySelector('i');
            if (icon) {
                icon.className = (theme === 'dark') 
                    ? 'bi bi-moon-stars-fill' 
                    : 'bi bi-brightness-high-fill';
            }
        }
    };

    // 3. Verifica o tema salvo no navegador ao carregar a página
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyThemeAndIcon(savedTheme);

    // 4. Adiciona o evento de clique ao botão
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function() {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';

            // Aplica o novo tema e ícone
            applyThemeAndIcon(newTheme);

            // Salva a preferência no navegador
            localStorage.setItem('theme', newTheme);
            
            // Exibe a notificação que você já tinha
            showToast('Tema alterado para: ' + (newTheme === 'dark' ? 'Escuro' : 'Claro'), 'success');
        });
    }
})();

    // Essa função esta relacionada no helpers.php
    function showToast(message, type) {
      var toastArea = document.getElementById('toastArea');
      if (!toastArea) return;
      var bg = 'text-bg-primary';
      if (type === 'success') bg = 'text-bg-success';
      else if (type === 'warning') bg = 'text-bg-warning';
      else if (type === 'danger' || type === 'error') bg = 'text-bg-danger';
      var el = document.createElement('div');
      el.className = 'toast align-items-center ' + bg;
      el.role = 'status';
      el.ariaLive = 'polite';
      el.ariaAtomic = 'true';
      el.innerHTML = '<div class="d-flex"><div class="toast-body">' + message + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
      toastArea.appendChild(el);
      var t = new bootstrap.Toast(el, {
        delay: 3500
      });
      t.show();
    }

    // Se o script renderizar ai aparece um toast
    //Link do que é toast no bootstrap https://getbootstrap.com/docs/5.3/components/toasts/
    document.addEventListener('DOMContentLoaded', function() {
      var script = document.getElementById('flashToastsScript');
      if (script && script.textContent) {
        try {
          (new Function(script.textContent))();
        } catch (e) {}
      }
    });
</script>
  </body>

  </html>