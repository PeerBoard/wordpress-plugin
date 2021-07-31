<div id="peerboard-modal-deactivation-feedback" class="peerboard-modal peerboard-modal-deactivation-feedback">
    <div class="peerboard-modal-dialog">
        <div class="peerboard-modal-header">
            <h4><?php _e('Quick Feedback', 'peerboard') ?></h4>
            <small><?php printf(__('Note, that your board is still available at peerboard.com/%s', 'peerboard'),$board_id) ?></small>
        </div>
        <div class="peerboard-modal-body">
            <div class="peerboard-modal-panel active" data-panel-id="reasons">
                <h3><strong><?php _e('If you have a moment, please let us know why you are deactivating:', 'peerboard') ?></strong></h3>
                <ul id="reasons-list">
                    <?php foreach ($reasons as $reason) : ?>
                        <li class="reason reason_<?php echo $reason['id'] ?>">
                            <label>
                                <span>
                                    <input type="radio" name="reason_<?php echo $reason['id'] ?>" class="main_reason" value="<?php esc_html_e($reason['text']) ?>" />
                                </span>
                                <span><?php esc_html_e($reason['text']) ?></span>
                                <?php if (!empty($reason['input_text'])) : ?>
                                    <div class="additional_field"><input type="text" placeholder="<?php esc_html_e($reason['input_text']) ?>"></div>
                                <?php endif; ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="peerboard-modal-footer">
            <button class="button button-secondary button-deactivate"><?php _e('Deactivate', 'peerboard') ?></button>
            <button class="button button-secondary button-close"><?php _e('Cancel', 'peerboard') ?></button>
        </div>
    </div>
</div>