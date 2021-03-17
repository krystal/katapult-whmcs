if(typeof katapultVmService === 'object') {
    (function(vmService) {

        let disableElements = () => {
            let elementsToDisable = [];

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

            // Remove any which weren't found
            elementsToDisable = elementsToDisable.filter(element => {
                return !!element;
            })

            elementsToDisable.forEach(element => {
                element.disabled = true;
            })
        };

        disableElements();
    })(katapultVmService);
}

