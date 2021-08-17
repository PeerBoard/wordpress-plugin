<?php
if (substr(get_site_url(), 0, 5) === "http:" && getenv("PEERBOARD_ENV") !== 'local') : ?>
    <div id='peerboard-forum' class='disabled'>
        Hello. Because we provide full hosting for our boards, we don't serve it for insecure protocols, such as HTTP.
        <br /><br />
        Consider switching to HTTPS. For most admin panels, it's a one-click action.
        <br />
        <b>Then reactivate the plugin, and that's it.</b>
        <br /><br />
        Another option is to connect PeerBoard as a subdomain for your blog. You can find it in the hosting section of your board.
        <br /><br />
        If you don't have one yet, you can create it here.
        <br /><br />
        We'll be happy to answer any questions dropped to <a href='mailto:support_wp@peerboard.com'>support_wp@peerboard.com</a>
        <br /><br />
    </div>
<?php else : ?>
    <div id='peerboard-forum'></div>
<?php endif;
remove_filter('the_content', 'wpautop');
?>