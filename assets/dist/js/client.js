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
         * Watch for the console launch so we can launch it in a window or iframe
         */
        const watchForConsoleLaunch = () => {
            const consoleContainer = document.getElementById('kvm-console-container')
            const launchForm = document.getElementById('kvm-console-launcher')
            const launchButton = launchForm.querySelector('button')

            // This function determines how the console will launch.
            // Right now it's pretty basic, just checking against the available width of the window.
            // If the console was inlined on a small screen, it would cause bad UX.
            const determineLaunchMethod = () =>  {

                // Default to a new window.
                let method = 'window';

                if(window.innerWidth > 500) {
                    method = 'inline'
                }

                return method;
            }

            // The button in the form doesn't actually submit the form, it triggered this event, which allows us to set the correct launch method before submitting the form
            launchButton.addEventListener('click', () => {

                // Fetch the launch method. Used as a function so the screen size when they click the button is used, not when the user loaded the page
                const consoleLaunchMethod = determineLaunchMethod()

                // Manipulate the form to suit the launch method. Omitted a default intentionally.
                switch(consoleLaunchMethod) {
                    case 'inline':
                        launchForm.target = 'kvm_console'
                        break;

                    case 'window':
                        launchForm.target = '_blank'
                        break;
                }

                // Submit the launch form
                launchForm.submit();

                // Fire an event for it
                let launchEvent = new Event('console-launched');
                launchEvent.launchMethod = consoleLaunchMethod
                launchForm.dispatchEvent(launchEvent);
            })

            launchForm.addEventListener('console-launched', (e) => {
                launchForm.classList.add('d-none', 'hidden')

                switch(e.launchMethod) {
                    case 'inline':
                        consoleContainer.classList.remove('d-none', 'hidden')
                        consoleContainer.scrollIntoView()
                        break;
                }
            })
        };

        /**
         * Invoke the above functions
         */
        disableElements();
        passwordToggling();
        confirmUserActions();
        addReplayTokensToActionButtons();
        watchForConsoleLaunch();

    })(kvmService);
}

