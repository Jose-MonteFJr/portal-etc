document.addEventListener('DOMContentLoaded', () => {
            const toggleButton = document.getElementById('toggle-btn');
            const sidebar = document.getElementById('sidebar');

            toggleButton.addEventListener('click', () => {
                sidebar.classList.toggle('expand');
            });
            
            // Lógica para dropdowns
            document.querySelectorAll('[data-toggle="collapse"]').forEach(link => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    
                    const targetId = link.getAttribute('data-target');
                    const targetEl = document.querySelector(targetId);
                    
                    // Fecha outros dropdowns no mesmo nível
                    document.querySelectorAll('.collapse.show').forEach(el => {
                        if (el !== targetEl && el.parentElement === targetEl.parentElement) {
                            el.classList.remove('show');
                            const correspondingLink = document.querySelector(`[data-target="#${el.id}"]`);
                            if (correspondingLink) {
                                correspondingLink.classList.add('collapsed');
                            }
                        }
                    });
                    
                    // Abre/fecha o dropdown clicado
                    targetEl.classList.toggle('show');
                    link.classList.toggle('collapsed');
                });
            });
        });
