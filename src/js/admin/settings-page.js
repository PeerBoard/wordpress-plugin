export default () => {
    let user_sync_checkbox = document.querySelector('#peerboard_users_sync_enabled');
    let bulk_activate_email_notifications = document.querySelector('#peerboard_bulk_activate_email');
    let expose_user_data = document.querySelector('#expose_user_data');

    // update sync settings
    function check_user_settings_sync() {

        if (!user_sync_checkbox.checked) {
            bulk_activate_email_notifications.disabled = true
            bulk_activate_email_notifications.checked = false

            expose_user_data.disabled = true
            expose_user_data.checked = false
        } else {
            bulk_activate_email_notifications.disabled = false
            expose_user_data.disabled = false
        }

    }

    user_sync_checkbox.onchange = (element) => {

        check_user_settings_sync()
    };

    window.onload = () => {
        check_user_settings_sync()

        // add class sub settings
        let email_notifications_parent_tr = bulk_activate_email_notifications.parentElement.parentElement;
        email_notifications_parent_tr.classList.add("sub_settings");

        let expose_user_data_parent_tr = expose_user_data.parentElement.parentElement;
        expose_user_data_parent_tr.classList.add("sub_settings");
        // add class sub settings

    }

}