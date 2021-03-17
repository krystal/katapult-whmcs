if(typeof kvmService === 'object') {
    (function(vmService) {

        let passwordToggling = () => {
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
        };

        let disableElements = () => {
            let elementsToDisable = [];

            switch(vmService.vm.state) {
                case 'started':
                    elementsToDisable.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Start_VM'))
                    break;

                case 'stopped':
                    elementsToDisable.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Stop_VM'))
                    elementsToDisable.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Reset_VM'))
                    break;

                case 'building':
                    elementsToDisable.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Shutdown_VM'))
                    elementsToDisable.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Stop_VM'))
                    elementsToDisable.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Start_VM'))
                    elementsToDisable.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Reset_VM'))
                    break;
            }

            // Remove any which weren't found
            elementsToDisable = elementsToDisable.filter(element => {
                return !!element;
            })

            elementsToDisable.forEach(element => {
                element.disabled = true;
                element.classList.add('k_disabled')

                if (element.tagName == 'A') {
                    element.href = 'javascript:void(0)'
                }
            })
        };

        disableElements();
        passwordToggling();

    })(kvmService);
}

