/**
 * Função reutilizável para inicializar um medidor de força de senha.
 * * @param {string} inputId - O ID do campo <input type="password">.
 * @param {string} barId - O ID do <div> da barra de progresso.
 * @param {string} textId - O ID do <span> ou <p> que exibe o texto de ajuda.
 */
function initializePasswordStrength(inputId, barId, textId) {

    const passwordInput = document.getElementById(inputId);
    const strengthBar = document.getElementById(barId);
    const strengthText = document.getElementById(textId);

    // Se algum dos elementos não for encontrado, para a execução
    if (!passwordInput || !strengthBar || !strengthText) {
        console.warn('Elementos do medidor de força de senha não encontrados.');
        return;
    }

    // Salva o texto de ajuda original (com as regras)
    const initialHelpText = strengthText.innerHTML;

    passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        const score = checkPasswordStrength(password);

        let width = '0%';
        let colorClass = '';
        let text = '';

        if (password.length === 0) {
            // Se a senha estiver vazia, reseta tudo e mostra as regras
            text = initialHelpText; // Volta o texto para a lista de regras
            width = '0%';
            strengthBar.style.width = width;
            strengthBar.className = 'progress-bar'; // Limpa as cores
            strengthText.innerHTML = text; // Usa innerHTML para manter a formatação
            strengthText.className = 'form-text small'; // Reseta a cor do texto
            return; // Para a execução aqui
        }

        switch (score) {
            case 0:
            case 1:
                width = '20%';
                colorClass = 'bg-danger';
                text = 'Fraca';
                break;
            case 2:
                width = '40%';
                colorClass = 'bg-warning';
                text = 'Média';
                break;
            case 3:
                width = '60%';
                colorClass = 'bg-warning';
                text = 'Razoável';
                break;
            case 4:
                width = '80%';
                colorClass = 'bg-success';
                text = 'Forte';
                break;
            case 5:
                width = '100%';
                colorClass = 'bg-success';
                text = 'Muito Forte';
                break;
        }

        // Atualiza a barra de progresso
        strengthBar.style.width = width;
        strengthBar.className = 'progress-bar';
        strengthBar.classList.add(colorClass);

        // Atualiza o texto
        strengthText.textContent = text;
        strengthText.className = (score <= 2) ? 'form-text small text-danger' : 'form-text small text-muted';
    });
}

/**
 * Função auxiliar que calcula a "pontuação" da senha.
 * Esta função é usada internamente por initializePasswordStrength.
 */
function checkPasswordStrength(password) {
    let score = 0;
    // Critério 1: Mínimo de 8 caracteres
    if (password.length >= 8) score++;
    // Critério 2: Contém pelo menos uma letra maiúscula
    if (/[A-Z]/.test(password)) score++;
    // Critério 3: Contém pelo menos uma letra minúscula
    if (/[a-z]/.test(password)) score++;
    // Critério 4: Contém pelo menos um número
    if (/[0-9]/.test(password)) score++;
    // Critério 5: Contém pelo menos um símbolo
    if (/[\W_]/.test(password)) score++; // \W é "não-palavra", _ é underscore

    return score;
}