if(typeof kvmService === 'object') {
    (function(vmService) {

        const passwordEl = document.getElementById('kvm_password')
        const passwordToggleEl = document.getElementById('kvm_password_toggle')
        const revealDuration = 15;

        const setDefaultRevealButton = () => {
            passwordToggleEl.innerText = 'Reveal for ' + revealDuration + 's';
            passwordToggleEl.disabled = false
        }

        setDefaultRevealButton();

        passwordToggleEl.addEventListener('click', () => {

            let timeRemaining = revealDuration;

            let handleShowingPassword = () => {
                timeRemaining--;

                if (timeRemaining < 1) {
                    passwordEl.type = 'password';
                    clearInterval(countdownInterval);
                    setDefaultRevealButton();
                } else {
                    passwordEl.type = 'text'
                    passwordToggleEl.innerText = 'Hiding in ' + timeRemaining + 's'
                    passwordToggleEl.disabled = true
                }
            };

            handleShowingPassword();

            let countdownInterval = setInterval(handleShowingPassword, 1000);
        });

    })(kvmService);
}

