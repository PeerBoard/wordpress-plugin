export default () => {
    let user_sync_checkbox = document.querySelector('#peerboard_users_sync_enabled');
    let bulk_activate_email_notifications = document.querySelector('#peerboard_bulk_activate_email');
    let expose_user_data = document.querySelector('#expose_user_data');
    let manually_sync_users = document.querySelector('#sync_users');
    let manually_sync_users_image = document.querySelector('#sync_users img');

    // update sync settings
    function check_user_settings_sync() {

        if (!user_sync_checkbox.checked) {
            bulk_activate_email_notifications.disabled = true
            bulk_activate_email_notifications.checked = false

            expose_user_data.disabled = true
            expose_user_data.checked = false

            manually_sync_users.disabled = true;
        } else {
            bulk_activate_email_notifications.disabled = false
            expose_user_data.disabled = false
            manually_sync_users.disabled = false;
        }

    }

    user_sync_checkbox.onchange = (element) => {

        check_user_settings_sync()
    };

    window.onload = () => {
        check_user_settings_sync()
    }

    /**
     * On import button click
     * @returns 
     */
    manually_sync_users.onclick = () => {

        if(manually_sync_users.disabled){
            return;
        }

        manually_sync_users_image.className = 'rotating';
        
        fetch(window.peerboard_admin.user_sync_url, {
            method: 'POST',
            body: {},
            headers: {
                'X-WP-Nonce': document.querySelector('#_wp_rest_nonce').value
            },
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let response = data.data
                    manually_sync_users_image.classList.remove("rotating");

                    console.log(response)
                } else {
                    manually_sync_users_image.classList.remove("rotating");
                    console.error(data);
                }
            }).catch(console.error)
    }

}