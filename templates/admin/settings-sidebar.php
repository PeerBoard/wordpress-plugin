<?php

namespace PEBO;
?>

<div class="peerboard-sidebar">
    <h2><?php _e('What’s new:','peerboard')?></h2>
    <p><?php _e('Now we can import WordPress member roles as PeerBoard group membership! Enable the checkboxes down below and click the Import button to do a full reimport.','peerboard')?></p>

    <h3><?= __('See our ','peerboard') ?><a href="https://community.peerboard.com/space/303964679"><?= __('product updates','peerboard')?></a></h3>

    <?php
    $contact_email = "<a href='mailto:support_wp@peerboard.com' target='_blank'>support_wp@peerboard.com</a>";
    $sitemap_url = home_url('/') . Sitemap::$sitemap_path;
    $comm_id = self::$peerboard_options["community_id"];
    ?>

    <p><?php 
        _e("For more information please check our ", 'peerboard');
        printf("<a href='https://community.peerboard.com/post/396436794' target='_blank'>%s</a>", __('How-To guide for WordPress', 'peerboard')); ?>
    </p>
    <p>
        <?php printf(__("If you have experienced any problems during the setup, please don't hesitate to contact us at %s.", 'peerboard'), $contact_email); ?>
    </p>
    <p><strong>Sitemap:</strong><a href="<?= $sitemap_url ?>" target="_blank"> <?= $sitemap_url ?></a></p>
    <p><strong>Community ID:</strong> <?= $comm_id ?></p>


</div>