export default () => {
    let deactivate_button = document.getElementById('deactivate-peerboard'),
        modal = document.getElementById('peerboard-modal-deactivation-feedback'),
        close = modal.querySelector('.button-close'),
        modal_deactivation_button = modal.querySelector('.button-deactivate'),
        deactivation_url = deactivate_button.href,
        reasons = modal.querySelectorAll('.reason')

    /**
     * Open modal
     * @param {*} ev 
     */
    deactivate_button.onclick = (ev) => {
        ev.preventDefault()
        modal.classList.add('active')
        modal_deactivation_button.href = deactivation_url

        reasons_logic()
    }

    /**
     * Close modal
     */
    close.onclick = () => {
        modal.classList.remove('active')
    }

    function reasons_logic() {
        /**
             * If reasons list have additional field show
             */
        reasons.forEach((elem, key) => {
            elem.onclick = (ev) => {
                // disable all actives 
                modal.querySelectorAll('.additional_field.active').forEach((elem)=>{
                    elem.classList.remove('active')
                })

                let additional_field = elem.querySelector('.additional_field')

                if (additional_field) {
                    additional_field.classList.add('active')
                }

            }
        })
    }



}