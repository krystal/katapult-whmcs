if(typeof kvmService === 'object') {
    (function(vmService) {

        /**
         * This is fetching all of the action buttons which are available in the client area, for use later in this file
         */
        let actionElements = [];

        actionElements.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Shutdown_VM'))
        actionElements.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Stop_VM'))
        actionElements.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Start_VM'))
        actionElements.push(document.getElementById('Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Reset_VM'))

        actionElements = actionElements.filter(element => {
            return !!element;
        })

        /**
         * This controls the password toggling functionality
         */
        const passwordToggling = () => {

            // Assign the password controls to constants to be used later
            const passwordEl = document.getElementById('kvm_password')
            const passwordToggleEl = document.getElementById('kvm_password_toggle')
            const revealDuration = 15;

            // Function to set the default text
            const setDefaultRevealButton = () => {
                passwordToggleEl.innerText = 'Reveal for ' + revealDuration + 's';
                passwordToggleEl.disabled = false
            }

            // Sets the default text
            setDefaultRevealButton();

            // Watches for the toggle button to be clicked
            passwordToggleEl.addEventListener('click', () => {

                // Handles showing the password for a period of time
                let timeRemaining = revealDuration;

                // Reduces the countdown and shows/hides the password
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

        /**
         * This function will disable certain buttons in the admin area when the VM is in certain states. For example, not allowing it to be started when it's already started
         */
        const disableElements = () => {
            let elementsToDisable = [];

            // Check for certain states, and if one matches, select the buttons/elements we want to disable
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

            // Disable those elements, with a special case for links
            elementsToDisable.forEach(element => {
                element.disabled = true;
                element.classList.add('k_disabled')

                if (element.tagName == 'A') {
                    element.href = 'javascript:void(0)'
                }
            })
        };

        /**
         * This will add listeners to the action buttons to confirm the user wishes to proceed. By default, WHMCS wouldn't question the user when they ask to reset their VM. This stops those accidents.
         */
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

        /**
         * This adds the replay tokens to the action button URLs. Those tokens will be checked when the request is made to ensure it hasn't already been used.
         */
        const addReplayTokensToActionButtons = () => {
            actionElements.forEach(element => {
                element.href += (element.href.indexOf('?') !== -1 ? '&' : '?') + 'knrp=' + knrpToken
            })
        };

        /**
         * Invoke the above functions
         */
        disableElements();
        passwordToggling();
        confirmUserActions();
        addReplayTokensToActionButtons();

    })(kvmService);
}

