<div class="peerboard-modal peerboard-modal-deactivation-feedback<?php echo empty($confirmation_message) ? ' no-confirmation-message' : ''; ?>">
    <div class="peerboard-modal-dialog">
        <div class="peerboard-modal-header">
            <h4><?php _e('Quick Feedback', 'peerboard') ?></h4>
        </div>
        <div class="peerboard-modal-body">
            <div class="peerboard-modal-panel active" data-panel-id="reasons">
                <h3><strong><?php _e('If you have a moment, please let us know why you are deactivating:', 'peerboard') ?></strong></h3>
                <ul id="reasons-list">
                    <?php foreach ($reasons as $reason) : ?>
                        <li class="reason_<?php echo $reason['id'] ?>" <?php isset($reason['input_text'])??esc_html_e('data-input-type="text"') ?> <?php echo isset($reason['input_text'])??sprintf('data-input-placeholder="%s"',$reason['input_text']) ?>>
                            <label>
                                <span>
                                    <input type="radio" name="selected-reason" value="<?php esc_html_e($reason['id']) ?>" />
                                </span>
                                <span><?php esc_html_e($reason['text']) ?></span>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="peerboard-modal-footer">
            <a href="#" class="button button-secondary button-deactivate"></a>
            <a href="#" class="button button-secondary button-close"><?php _e('Cancel', 'peerboard') ?></a>
        </div>
    </div>
</div>