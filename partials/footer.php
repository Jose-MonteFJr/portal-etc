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


    // MUDANÇA DE ÍCONE DO DROPDOWN DO PORTAL HEADER

    document.addEventListener('DOMContentLoaded', function () {
    // 1. Seleciona todos os menus que podem ser abertos/fechados
    const collapsibleMenus = document.querySelectorAll('.sidebar-dropdown.collapse');

    collapsibleMenus.forEach(menu => {
        // 2. Ouve o evento "show.bs.collapse", que acontece QUANDO O MENU COMEÇA A ABRIR
        menu.addEventListener('show.bs.collapse', function () {
            // Encontra o link <a> que controla este menu
            const toggleLink = document.querySelector(`[data-bs-target="#${menu.id}"]`);
            if (toggleLink) {
                // Encontra o ícone dentro do link
                const icon = toggleLink.querySelector('.dropdown-icon');
                if (icon) {
                    // Troca a classe de 'mais' para 'menos'
                    icon.classList.remove('bi-plus-lg');
                    icon.classList.add('bi-dash-lg');
                }
            }
        });

        // 3. Ouve o evento "hide.bs.collapse", que acontece QUANDO O MENU COMEÇA A FECHAR
        menu.addEventListener('hide.bs.collapse', function () {
            // Encontra o link <a> que controla este menu
            const toggleLink = document.querySelector(`[data-bs-target="#${menu.id}"]`);
            if (toggleLink) {
                // Encontra o ícone dentro do link
                const icon = toggleLink.querySelector('.dropdown-icon');
                if (icon) {
                    // Troca a classe de 'menos' de volta para 'mais'
                    icon.classList.remove('bi-dash-lg');
                    icon.classList.add('bi-plus-lg');
                }
            }
        });
    });
});


// Script sistema de notificação

function fetchNotifications() {
    fetch('/portal-etc/notificacoes_ajax.php')
        .then(response => response.json())
        .then(data => {
            const countBadge = document.getElementById('notification-count');
            const notificationList = document.getElementById('notification-list');

            // Atualiza o contador
            if (data.unread_count > 0) {
                countBadge.textContent = data.unread_count;
                countBadge.style.display = 'block';
            } else {
                countBadge.style.display = 'none';
            }

            // Atualiza a lista de notificações
            notificationList.innerHTML = ''; // Limpa a lista antiga
            if (data.notifications.length > 0) {
                data.notifications.forEach(notif => {
                    const listItem = document.createElement('li');
                    listItem.innerHTML = `<a class="dropdown-item" href="${notif.link}">
                                            <p class="mb-0 small">${notif.mensagem}</p>
                                            <small class="text-muted">${new Date(notif.created_at).toLocaleString('pt-BR')}</small>
                                          </a>`;
                    notificationList.appendChild(listItem);
                });
            } else {
                notificationList.innerHTML = '<li><span class="dropdown-item text-muted">Nenhuma notificação.</span></li>';
            }
        })
        .catch(error => console.error('Erro ao buscar notificações:', error));
}

// Chama a função quando a página carrega
document.addEventListener('DOMContentLoaded', fetchNotifications);

// E chama a função a cada 30 segundos
setInterval(fetchNotifications, 30000);

// Contagem da notificação

const dropdownElement = document.getElementById('notificationDropdown');
if (dropdownElement) {
    dropdownElement.addEventListener('click', function() {
        // Pega o contador
        const countBadge = document.getElementById('notification-count');
        
        // Se houver notificações não lidas, faz a chamada AJAX para marcá-las como lidas
        if (countBadge.style.display !== 'none' && parseInt(countBadge.textContent, 10) > 0) {
            fetch('/portal-etc/marcar_como_lido_ajax.php')
                .then(() => {
                    // Esconde o contador visualmente na hora
                    countBadge.style.display = 'none';
                });
        }
    });
}

</script>
  </body>

  </html>