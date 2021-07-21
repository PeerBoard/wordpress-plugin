export default () => {
    function modal_init() {
        let deactivate_button = document.getElementById('deactivate-peerboard');

        if (!deactivate_button) {
            return;
        }

        let modal,
            close,
            modal_deactivation_button,
            deactivation_url = deactivate_button.href,
            reasons;

        /**
         * Open modal
         * @param {*} ev 
         */
        deactivate_button.onclick = (ev) => {
            ev.preventDefault()

            let formData = new FormData();

            formData.append('action', 'peerboard_add_deactivation_feedback_dialog_box');

            fetch(window.peerboard_admin.ajax_url, {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (response.ok) {
                        return response.json()
                    } else {
                        console.log('Looks like there was a problem. Status Code: ' + response.status);
                    }
                })
                .then(data => {
                    if (data.success) {
                        let response = data.data

                        document.querySelector('body').append(stringToHTML(response))

                        modal = document.getElementById('peerboard-modal-deactivation-feedback')
                        close = modal.querySelector('.button-close')
                        modal_deactivation_button = modal.querySelector('.button-deactivate')
                        reasons = modal.querySelectorAll('.reason')

                        modal.classList.add('active')
                        modal_deactivation_button.href = deactivation_url

                        // Close modal and remove it
                        close.onclick = (ev) => {
                            ev.preventDefault()
                            modal.classList.remove('active')
                            modal.remove()
                        }

                        reasons_logic()

                        modal_deactivation_button.onclick = (ev) => {
                            ev.preventDefault()
                            modal_deactivation_button.disabled = true
                            send_feedback()
                        }
                    } else {
                        console.log(console.error(data));
                    }
                }).catch(console.error)
        }

        function reasons_logic() {
            /**
            * If reasons list have additional field show
            */
            reasons.forEach((elem, key) => {
                elem.onclick = (ev) => {
                    // disable all actives 
                    modal.querySelectorAll('.reason.active').forEach((elem) => {
                        elem.classList.remove('active')
                        elem.querySelector('input.main_reason').checked = false
                    })

                    elem.classList.add('active')
                    elem.querySelector('input.main_reason').checked = true
                }
            })
        }

        /**
         * Send feedback
         */
        function send_feedback() {
            let formData = new FormData(),
                main_reason_wrap = modal.querySelector('.reason.active');
            if (main_reason_wrap) {
                let main_reason = main_reason_wrap.querySelector('input.main_reason').value,
                    additional_info = main_reason_wrap.querySelector('.additional_field input').value
                formData.append('main_reason', main_reason);
                formData.append('additional_info', additional_info);
            }

            formData.append('action', 'peerboard_feedback_request');

            fetch(window.peerboard_admin.ajax_url, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let response = data.data
                        window.location.href = deactivation_url
                    } else {
                        console.error(data);
                    }
                }).catch(console.error)
        }
    }

    /**
    * Load after full page ready + some seconds 
    */
    custom_on_load(modal_init);

    /**
    * On load custom function
    * @param {*} callback 
    */
    function custom_on_load(callback) {
        if (window.addEventListener)
            window.addEventListener("load", callback, false);
        else if (window.attachEvent)
            window.attachEvent("onload", callback);
        else window.onload = callback;
    }


    /**
    * Convert a template string into HTML DOM nodes
    * @param  {String} str The template string
    * @return {Node}       The template HTML
    */
    function stringToHTML(str) {
        var dom = document.createElement('div');
        dom.innerHTML = str;
        return dom;
    };
}