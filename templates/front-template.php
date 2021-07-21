<?php
if (substr(get_site_url(), 0, 5) === "http:" && getenv("PEERBOARD_ENV") !== 'local') : ?>
    <div id='peerboard-forum' class='disabled'>
        Hello, because we are providing full hosting for our boards - we don't serve it for unsecure protocols, such as HTTP.
        <br /><br />
        Consider switching to HTTPS - for most admin panels it's one click action.
        <br />
        <b>Then reactivate plugin and thats it.</b>
        <br /><br />
        Another option is to connect PeerBoard as a subdomain for your blog, it can be found in hosting section of your board.
        <br /><br />
        If you don't have one yet - you can create it here
        <br /><br />
        Will be happy to answer questions dropped to <a href='mailto:integrations@peerboard.com'>integrations@peerboard.com</a>
        <br /><br />
    </div>
<?php else : ?>
    <div id='peerboard-forum'></div>
<?php endif;
remove_filter('the_content', 'wpautop');
?>
