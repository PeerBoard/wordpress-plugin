
/**
 * Feedback form
 */
import feedback_form from './admin/feedback-form';
import settings_page from './admin/settings-page';

let admin_path = window.location.href;

// if plugin activation or deactivation page
if (admin_path.includes('plugins.php')) {
    feedback_form()
}

// if plugin activation or deactivation page
if (admin_path.includes('page=peerboard')) {
    settings_page()
}
