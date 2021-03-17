if(typeof katapultVmService === 'object') {
    (function(vmService) {

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

        // Actually disable the elements
        disableElements();
    })(katapultVmService);
}

