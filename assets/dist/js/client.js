if(typeof kvmService === 'object') {
    (function(vmService) {

        let actionElements = [];

        actionElements.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Shutdown_VM'))
        actionElements.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Stop_VM'))
        actionElements.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Start_VM'))
        actionElements.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Reset_VM'))

        actionElements = actionElements.filter(element => {
            return !!element;
        })

        const passwordToggling = () => {
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

        const disableElements = () => {
            let elementsToDisable = [];

            switch(vmService.vm.state) {
                case 'started':
                    elementsToDisable.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Start_VM'))
                    break;

                case 'stopped':
                    elementsToDisable.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Stop_VM'))
                    elementsToDisable.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Reset_VM'))
                    elementsToDisable.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Shutdown_VM'))
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

        const confirmUserActions = () => {
            actionElements.forEach(element => {
                element.addEventListener('click', (e) => {
                    if (element.disabled || !confirm('Are you sure?')) {
                        e.preventDefault();
                        return false;
                    }
                })
            })
        };

        const addReplayTokensToActionButtons = () => {
            actionElements.forEach(element => {
                element.href += (element.href.indexOf('?') !== -1 ? '&' : '?') + 'knrp=' + knrpToken
            })
        };

        disableElements();
        passwordToggling();
        confirmUserActions();
        addReplayTokensToActionButtons();

    })(kvmService);
}

