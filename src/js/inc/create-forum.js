export default (createForum) => {

  const setWaitingForReady = (timeout) => new Promise((resolve, reject) => {
    _peerboardSettings['onReady'] = () => {
      resolve();
    }
    setTimeout(() => {
      reject();
    }, timeout)
  });

  function docReady(fn) {
    // see if DOM is already available
    if (document.readyState === "complete" || document.readyState === "interactive") {
      // call on next available tick
      setTimeout(fn, 1);
    } else {
      document.addEventListener("DOMContentLoaded", fn);
    }
  }

  /**
   * Temporary solution for changing page meta
   */
  function fix_page_meta() {

    let target = document.getElementById('peerboard-forum');

    if (target === null) {return}

    document.querySelector("link[rel=canonical]").setAttribute("href", document.location.origin + document.location.pathname);

    try { document.querySelector("meta[name=description]").remove(); } catch (_) { };
  }

  _peerboardSettings['onTitleChanged'] = (title) => {
    window.document.title = title
  };

  _peerboardSettings['onPathChanged'] = location => history.replaceState(null, '', location);
  _peerboardSettings['minHeight'] = window.innerHeight + "px";
  _peerboardSettings['onLogout'] = () => {
    document.cookie = 'wp-peerboard-auth=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;';
  }
  _peerboardSettings['onFail'] = () => {
    console.error('Failed to load forum - please contact us at support_wp@peerboard.com')
    alert("Something really unexpected happened - please contact us at support_wp@peerboard.com")
  }

  docReady(function () {
    let target = document.getElementById('peerboard-forum');
    if (target === null) {
      // Means that we have no the_content tag
      // Just embed inside the body
      target = document.body;
      document.body.innerHTML = '';
    } else {
      if (target.className === 'disabled') {
        return;
      }
    }

    createForum(_peerboardSettings['board-id'], target, _peerboardSettings);
  });
}
