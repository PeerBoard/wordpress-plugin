export default () => {
    let user_sync_checkbox = document.querySelector('#peerboard_users_sync_enabled');
    let bulk_activate_email_notifications = document.querySelector('#peerboard_bulk_activate_email');
    let expose_user_data = document.querySelector('#expose_user_data');
    let manually_sync_users = document.querySelector('#sync_users');
    let manually_sync_users_image = document.querySelector('#sync_users img');
    let users_amount_pages = document.querySelector('#sync-progress').getAttribute('page-count');
    let old_text = manually_sync_users.querySelector('.text').innerHTML;
    let new_text = 'Importing';


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

        if (manually_sync_users.disabled) {
            return;
        }

        // show progress bar
        document.querySelector('.sync-progress-wrap').style.display = 'block';
        document.querySelector('#result_message_success').style.display = 'none';
        document.querySelector('#result_message_waring').style.display = 'none';

        import_users()

    }

    function import_users() {

        manually_sync_users_image.className = 'rotating';
        manually_sync_users.disabled = true;


        manually_sync_users.querySelector('.text').innerHTML = new_text


        let every_step_persent = (100 / users_amount_pages);

        fetch(window.peerboard_admin.user_sync_url, {
            method: 'POST',
            body: JSON.stringify({
                'paged': document.querySelector('#sync-progress').getAttribute('current-page'),
                'expose_user_data': document.querySelector('#expose_user_data').checked,
                'peerboard_bulk_activate_email': document.querySelector('#peerboard_bulk_activate_email').checked
            }),
            headers: {
                'X-WP-Nonce': document.querySelector('#_wp_rest_nonce').value
            },
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let response = data.data
                    let current_page = document.querySelector('#sync-progress').getAttribute('current-page');

                    console.log(response);



                    document.querySelector('#sync-progress').setAttribute('current-page', parseInt(current_page) + 1);

                    let width = Math.round(every_step_persent * parseInt(current_page) + 1);

                    if (width > 100) {
                        width = 100;
                    }

                    document.querySelector('#bar').style.width = width + "%";
                    document.querySelector('#sync-progress #bar').innerHTML = width + "%";

                    // next page request
                    if (response.resume) {
                        import_users()
                    }

                    // after last page request is ready
                    if (!response.resume) {
                        importing_finished(data)
                    }
                } else {
                    importing_finished(data)
                }
            }).catch(console.error)

    }

    function importing_finished(data) {
        let response = data.data

        manually_sync_users_image.classList.remove("rotating");
        manually_sync_users.querySelector('.text').innerHTML = old_text

        if (data.success) {
            // show success message
            document.querySelector('#result_message_success').innerHTML = response.message
            document.querySelector('#result_message_success').style.display = 'block';
        } else {
            document.querySelector('#result_message_waring').innerHTML = response.message
            document.querySelector('#result_message_waring').style.display = 'block';
            manually_sync_users.disabled = false;
        }


        // hide progress bar
        document.querySelector('.sync-progress-wrap').style.display = 'none';
    }

}