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

  _peerboardSettings['onTitleChanged'] = (title) => window.document.title = "Forum: " + title;
  _peerboardSettings['onPathChanged'] = location => history.replaceState(null, '', location);
  _peerboardSettings['minHeight'] = window.innerHeight * 0.5 + "px";
  _peerboardSettings['onLogout'] = () => {
    document.cookie = 'wp-peerboard-auth=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;';
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
    // Detect that all works within 10 sec
    setWaitingForReady(30000).then().catch(() => {
      alert("Something really unexpected happened - please contact us at integrations@peerboard.com");
    });

    createForum(_peerboardSettings['board-id'], target, _peerboardSettings);
  });
}
