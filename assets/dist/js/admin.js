if(typeof katapultVmService === 'object') {
    (function(vmService) {

        /**
         * This is fetching all of the action buttons which are available in the client area, for use later in this file
         */
        let actionElements = [];

        const fetchActionElements = () => {
            actionElements = [];

            actionElements.push(document.getElementById('btnStop_VM'))
            actionElements.push(document.getElementById('btnReset_VM'))
            actionElements.push(document.getElementById('btnShutdown_VM'))
            actionElements.push(document.getElementById('btnStart_VM'))

            actionElements = actionElements.filter(element => {
                return !!element;
            })
        }

        /**
         * This function will disable certain buttons in the admin area when the VM is in certain states. For example, not allowing it to be started when it's already started
         */
        let disableElements = () => {
            let elementsToDisable = [];

            // Check for certain states, and if one matches, select the buttons/elements we want to disable
            switch(vmService.vm.state) {
                case 'started':
                    elementsToDisable.push(document.getElementById('btnCreate'))
                    elementsToDisable.push(document.getElementById('btnStart_VM'))
                    break;

                case 'stopped':
                    elementsToDisable.push(document.getElementById('btnCreate'))
                    elementsToDisable.push(document.getElementById('btnStop_VM'))
                    elementsToDisable.push(document.getElementById('btnShutdown_VM'))
                    elementsToDisable.push(document.getElementById('btnReset_VM'))
                    break;

                case 'building':
                    elementsToDisable.push(document.getElementById('btnChange_Package'))
                    elementsToDisable.push(document.getElementById('btnShutdown_VM'))
                    elementsToDisable.push(document.getElementById('btnStop_VM'))
                    elementsToDisable.push(document.getElementById('btnStart_VM'))
                    elementsToDisable.push(document.getElementById('btnReset_VM'))
                    break;
            }

            // Remove any which weren't found. They should all be though...
            elementsToDisable = elementsToDisable.filter(element => {
                return !!element;
            })

            // Disable those elements
            elementsToDisable.forEach(element => {
                element.disabled = true;
            })
        };

        /**
         * This will add listeners to the action buttons to confirm the user wishes to proceed. By default, WHMCS wouldn't question the user when they ask to reset their VM. This stops those accidents.
         */
        const confirmUserActions = () => {
            actionElements.forEach(element => {

                if (element.classList.contains('katapult_confirmed')) {
                    return;
                }

                let originalOnClick = element.onclick;

                element.onclick = () => {
                    confirm('Are you sure?') && originalOnClick()
                }

                element.classList.add('katapult_confirmed')
            })
        };

        // Run those functions
        fetchActionElements();
        disableElements();
        confirmUserActions();

        // This fires when the page changes, which WHMCS does when a module command completes. We have to then rebind/re-modify the page as we need.
        document.getElementById('servicecontent').addEventListener("DOMNodeInserted", () => {
            fetchActionElements();
            confirmUserActions();
            //disableElements(); // Disabling elements isn't enforced here, because the VM might not of transitioned to the new state yet, and it's frustrating to have 'start' greyed out when you just stopped a VM.
        }, false);

    })(katapultVmService);
}

