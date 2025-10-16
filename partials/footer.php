  </div>

  <div style="display: none;">
      <?php csrf_input(); ?>
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
                      icon.className = (theme === 'dark') ?
                          'bi bi-moon-stars-fill' :
                          'bi bi-brightness-high-fill';
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

      document.addEventListener('DOMContentLoaded', function() {
          // 1. Seleciona todos os menus que podem ser abertos/fechados
          const collapsibleMenus = document.querySelectorAll('.sidebar-dropdown.collapse');

          collapsibleMenus.forEach(menu => {
              // 2. Ouve o evento "show.bs.collapse", que acontece QUANDO O MENU COMEÇA A ABRIR
              menu.addEventListener('show.bs.collapse', function() {
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
              menu.addEventListener('hide.bs.collapse', function() {
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

      // Modal de excluir
      document.addEventListener('DOMContentLoaded', function() {
          const confirmDeleteModal = document.getElementById('confirmDeleteModal');
          if (confirmDeleteModal) {
              // Evento disparado QUANDO o modal está prestes a ser mostrado
              confirmDeleteModal.addEventListener('show.bs.modal', event => {
                  // O botão que acionou o modal
                  const button = event.relatedTarget;

                  // Pega os dados dos atributos data-* do botão
                  const formAction = button.getAttribute('data-form-action');
                  const itemId = button.getAttribute('data-item-id');
                  const itemName = button.getAttribute('data-item-name');
                  const idField = button.getAttribute('data-id-field');

                  // Personaliza a mensagem do modal
                  const modalBody = confirmDeleteModal.querySelector('#modalBodyMessage');
                  modalBody.textContent = `Você tem certeza que deseja excluir o item "${itemName}"? Esta ação não pode ser desfeita.`;

                  // Configura o botão de confirmação final
                  const confirmBtn = confirmDeleteModal.querySelector('#confirmDeleteButton');

                  // Armazena os dados no próprio botão de confirmação
                  confirmBtn.dataset.formAction = formAction;
                  confirmBtn.dataset.itemId = itemId;
                  confirmBtn.dataset.idField = idField;
              });

              // Evento de clique para o botão de confirmação DENTRO do modal
              const finalConfirmBtn = document.getElementById('confirmDeleteButton');
              finalConfirmBtn.addEventListener('click', function() {
                  // Pega os dados que armazenamos
                  const formAction = this.dataset.formAction;
                  const itemId = this.dataset.itemId;
                  const idField = this.dataset.idField;

                  // Pega o token CSRF de um dos formulários existentes na página
                  const csrfTokenInput = document.querySelector('input[name="csrf_token"]');

                  if (!formAction || !itemId || !idField || !csrfTokenInput) {
                      alert('Erro de configuração. Não foi possível excluir.');
                      return;
                  }

                  // Cria um formulário dinamicamente em memória
                  const form = document.createElement('form');
                  form.method = 'post';
                  form.action = formAction;

                  // Adiciona o campo de ID
                  const idInput = document.createElement('input');
                  idInput.type = 'hidden';
                  idInput.name = idField;
                  idInput.value = itemId;
                  form.appendChild(idInput);

                  // Adiciona o campo CSRF
                  form.appendChild(csrfTokenInput.cloneNode());

                  // Adiciona o formulário à página e o envia
                  document.body.appendChild(form);
                  form.submit();
              });
          }
      });

      // Sistema de notificações
      // Função para notificações
      function fetchNotifications() {
          fetch('/portal-etc/notificacao/notificacoes_ajax.php')
              .then(response => response.json())
              .then(data => {
                  const countBadge = document.getElementById('notification-count');
                  const notificationList = document.getElementById('notification-list');

                  // Atualiza o contador (lógica existente)
                  if (data.unread_count > 0) {
                      countBadge.textContent = data.unread_count;
                      countBadge.style.display = 'block';
                  } else {
                      countBadge.style.display = 'none';
                  }

                  // Limpa a lista
                  notificationList.innerHTML = '';

                  // NOVO: Verifica se existe alguma notificação na lista
                  const hasNotifications = data.notifications && data.notifications.length > 0;

                  if (hasNotifications) {
                      data.notifications.forEach(notif => {
                          const listItem = document.createElement('li');

                          // Adiciona uma classe se a notificação não foi lida
                          const isUnreadClass = notif.status === 'nao lida' ? 'unread-notification' : '';

                          // Constrói o HTML do item da lista
                          let itemHTML = `
                        <div class="dropdown-item-wrapper ${isUnreadClass}">
                            <a class="dropdown-item" href="${notif.link || '#'}">
                                <p class="mb-0 small">${notif.mensagem}</p>
                                <small class="text-muted">${new Date(notif.created_at).toLocaleString('pt-BR')}</small>
                            </a>`;

                          // NOVO: Adiciona o botão "marcar como lida" APENAS se não estiver lida
                          if (notif.status === 'nao lida') {
                              itemHTML += `<button class="btn btn-sm btn-light mark-as-read-btn" data-id="${notif.id_notificacao}" title="Marcar como lida">
                                        <i class="bi bi-check-circle"></i>
                                     </button>`;
                          }

                          itemHTML += `</div>`;
                          listItem.innerHTML = itemHTML;
                          notificationList.appendChild(listItem);
                      });
                  } else {
                      notificationList.innerHTML = '<li><span class="dropdown-item text-muted text-center">Nenhuma notificação.</span></li>';
                  }

                  // ALTERADO: O rodapé agora é construído com base na verificação
                  const footerHTML = `
    <li><hr class="dropdown-divider"></li>
    
    <li>
        <a class="dropdown-item text-center text-muted small ${!hasNotifications ? 'disabled' : ''}" 
           href="#" 
           id="clear-notifications-btn">
            <i class="bi bi-check2-all"></i> Limpar notificações lidas
        </a>
    </li>
    
    <li><hr class="dropdown-divider"></li>
    
    <li>
        <a class="dropdown-item text-center" href="/portal-etc/notificacao/historico_notificacoes.php">
            Ver todas as notificações
        </a>
    </li>
`;
                  notificationList.insertAdjacentHTML('beforeend', footerHTML);
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
                  fetch('/portal-etc/notificacao/marcar_como_lido_ajax.php')
                      .then(() => {
                          // Esconde o contador visualmente na hora
                          countBadge.style.display = 'none';
                      });
              }
          });
      }


      // Lógica para o botão de limpar notificações (versão com delegação de eventos)
      document.addEventListener('click', function(event) {

          // Verifica se o elemento clicado (ou um pai próximo) é o nosso botão de limpar
          const clearBtn = event.target.closest('#clear-notifications-btn');

          if (clearBtn) {
              event.preventDefault(); // Impede que o link '#' recarregue a página

              if (confirm('Tem certeza que deseja limpar todas as notificações já lidas?')) {
                  fetch('/portal-etc/notificacao/limpar_notificacoes_ajax.php', {
                          method: 'POST'
                      })
                      .then(response => response.json())
                      .then(data => {
                          if (data.success) {
                              showToast('Notificações lidas foram limpas.', 'success');
                              fetchNotifications(); // Atualiza a lista
                          } else {
                              showToast('Ocorreu um erro ao limpar as notificações.', 'danger');
                          }
                      })
                      .catch(error => {
                          console.error('Erro na requisição de limpeza:', error);
                          showToast('Erro de conexão.', 'danger');
                      });
              }
          }
      });

      document.addEventListener('click', function(event) {
          const markBtn = event.target.closest('.mark-as-read-btn');
          if (markBtn) {
              event.stopPropagation();
              const notificationId = markBtn.dataset.id;

              const formData = new FormData();
              formData.append('id_notificacao', notificationId);

              fetch('/portal-etc/notificacao/marcar_uma_lida_ajax.php', {
                      method: 'POST',
                      body: formData
                  })
                  .then(() => fetchNotifications()); // Apenas atualiza a lista
          }
      });

      // Sistema do feed/avisos
      // Curtida
      document.addEventListener('click', function(event) {
          const likeBtn = event.target.closest('.like-btn');
          if (likeBtn) {
              event.preventDefault();

              const avisoId = likeBtn.dataset.avisoId;
              const formData = new FormData();
              formData.append('id_aviso', avisoId);

              // Faz a chamada AJAX
              fetch('/portal-etc/like_toggle_ajax.php', {
                      method: 'POST',
                      body: formData
                  })
                  .then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          // Atualiza a interface do botão clicado
                          const icon = likeBtn.querySelector('i');
                          const text = likeBtn.querySelector('.like-text');
                          const count = likeBtn.querySelector('.like-count');

                          count.textContent = data.new_like_count;

                          if (data.liked) {
                              // Se o usuário agora curte o post
                              likeBtn.classList.add('text-danger');
                              likeBtn.classList.remove('text-muted');
                              icon.className = 'bi bi-heart-fill';
                              text.textContent = 'Curtido';
                          } else {
                              // Se o usuário removeu a curtida
                              likeBtn.classList.remove('text-danger');
                              likeBtn.classList.add('text-muted');
                              icon.className = 'bi bi-heart';
                              text.textContent = 'Curtir';
                          }

                          const postCard = likeBtn.closest('.card');
                          if (postCard) {
                              const totalCountSpan = postCard.querySelector('.total-curtidas-count');
                              if (totalCountSpan) {
                                  totalCountSpan.textContent = data.new_like_count;
                              }
                          }
                      } else {
                          alert(data.error || 'Ocorreu um erro.');
                      }
                  })
                  .catch(error => console.error('Erro no AJAX de curtida:', error));
          }
      });

      // Salvar
      document.addEventListener('click', function(event) {
          // CORRIGIDO: Seleciona o botão de SALVAR
          const saveBtn = event.target.closest('.save-btn');

          if (saveBtn) {
              event.preventDefault();

              const avisoId = saveBtn.dataset.avisoId;
              const formData = new FormData();
              formData.append('id_aviso', avisoId);

              // Faz a chamada AJAX para o script de SALVAR
              fetch('/portal-etc/save_toggle_ajax.php', {
                      method: 'POST',
                      body: formData
                  })
                  .then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          // Atualiza a interface do botão de SALVAR
                          const icon = saveBtn.querySelector('i');
                          const text = saveBtn.querySelector('.save-text');

                          // CORRIGIDO: Verifica 'data.saved' (enviado pelo PHP)
                          if (data.saved) {
                              // Se o usuário agora SALVOU o post
                              saveBtn.classList.add('text-primary');
                              saveBtn.classList.remove('text-muted');
                              icon.className = 'bi bi-bookmark-fill'; // Ícone de SALVO
                              text.textContent = 'Salvo';
                          } else {
                              // Se o usuário removeu o post dos salvos
                              saveBtn.classList.remove('text-primary');
                              saveBtn.classList.add('text-muted');
                              icon.className = 'bi bi-bookmark'; // Ícone de SALVAR
                              text.textContent = 'Salvar';
                          }
                      } else {
                          alert(data.error || 'Ocorreu um erro ao salvar o aviso.');
                      }
                  })
                  .catch(error => console.error('Erro no AJAX de salvar:', error));
          }
      });


      // Adicione este script no seu footer.php, junto com os outros de like e save
      // Comentar
      // Substitua seu 'addEventListener' para 'submit' por este
      document.addEventListener('submit', function(event) {
          const commentForm = event.target.closest('.comment-form');
          if (commentForm) {
              event.preventDefault();

              const formData = new FormData(commentForm);
              const commentInput = commentForm.querySelector('input[name="conteudo"]');
              const submitButton = commentForm.querySelector('button[type="submit"]');

              commentInput.disabled = true;
              submitButton.disabled = true;

              fetch('/portal-etc/comentar_ajax.php', {
                      method: 'POST',
                      body: formData
                  })
                  .then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          const postCard = commentForm.closest('.card');
                          const commentList = postCard.querySelector('.comment-list');

                          // CORREÇÃO: Lógica para exibir o novo comentário
                          // Se a lista de comentários estava com a mensagem "Nenhum comentário",
                          // primeiro limpa essa mensagem.
                          if (commentList.querySelector('.text-muted')) {
                              commentList.innerHTML = '';
                          }

                          // Adiciona o HTML do novo comentário (retornado pelo PHP) ao final da lista
                          commentList.insertAdjacentHTML('beforeend', data.comment_html);

                          // Limpa o campo de texto
                          commentInput.value = '';
                        //   showToast('Comentário publicado!', 'success');

                          // Atualiza o contador de comentários
                          const commentCountSpan = postCard.querySelector('.total-comentarios-count');
                          if (commentCountSpan) {
                              commentCountSpan.textContent = data.new_comment_count;
                          }

                      } else {
                          showToast(data.error || 'Não foi possível publicar o comentário.', 'danger');
                      }
                  })
                  .catch(error => console.error('Erro no AJAX de comentário:', error))
                  .finally(() => {
                      commentInput.disabled = false;
                      submitButton.disabled = false;
                      commentInput.focus(); // Coloca o cursor de volta no campo para um novo comentário
                  });
          }
      });

      // SUBSTITUA O SEU BLOCO 'addEventListener' PARA '.view-comments-btn' POR ESTE

      document.addEventListener('DOMContentLoaded', function() {

          // Seleciona TODAS as seções de comentário que podem ser abertas
          const commentSections = document.querySelectorAll('.comments-section.collapse');

          commentSections.forEach(section => {
              // Ouve o evento do Bootstrap que dispara QUANDO a seção começa a abrir
              section.addEventListener('show.bs.collapse', function() {

                  const commentList = section.querySelector('.comment-list');

                  // Verifica se os comentários já foram carregados usando o nosso atributo
                  if (commentList.dataset.commentsLoaded === 'false') {
                      const avisoId = section.id.split('-')[2]; // Pega o ID do aviso a partir do ID da div

                      commentList.innerHTML = '<p class="text-muted small text-center">Carregando...</p>';

                      fetch(`/portal-etc/ver_comentarios_ajax.php?id_aviso=${avisoId}`)
                          .then(response => response.text())
                          .then(html => {
                              commentList.innerHTML = html;
                              // Marca que os comentários foram carregados para não buscar de novo
                              commentList.dataset.commentsLoaded = 'true';
                          })
                          .catch(error => {
                              commentList.innerHTML = '<p class="text-danger small">Erro ao carregar comentários.</p>';
                          });
                  }
                  // Se os comentários já foram carregados, não faz nada, apenas deixa o Bootstrap abrir a seção.
              });
          });
      });

    // Mensagem
    // Script para rolar para a última mensagem (que você já tem)
    document.addEventListener('DOMContentLoaded', function() {
        const chatBox = document.getElementById('chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // --- NOVA LÓGICA: Enviar com "Enter" ---
        const messageTextarea = document.querySelector('textarea[name="conteudo"]');
        const messageForm = messageTextarea.closest('form');

        if (messageTextarea && messageForm) {
            messageTextarea.addEventListener('keydown', function(event) {
                // Verifica se a tecla pressionada foi "Enter" E se a tecla "Shift" NÃO estava pressionada
                if (event.key === 'Enter' && !event.shiftKey) {
                    // Impede o comportamento padrão (que é criar uma nova linha)
                    event.preventDefault();
                    // Envia o formulário
                    messageForm.submit();
                }
                // Se o usuário apertar Shift + Enter, ele ainda conseguirá quebrar a linha.
            });
        }
    });

  </script>
  </body>

  </html>