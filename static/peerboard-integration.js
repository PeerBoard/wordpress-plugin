var script = document.createElement('script');
script.src = _peerboardSettings['embed-url'];
script.setAttribute("async", "");
script.setAttribute("data-skip-init", "");

const setWaitingForReady = (timeout) => new Promise((resolve, reject) => {
  _peerboardSettings['onReady'] = () => {
    resolve();
  }
  setTimeout(() => {
    reject();
  }, timeout)
});

_peerboardSettings['onTitleChanged'] = (title) => window.document.title = "Forum: " + title;
_peerboardSettings['onPathChanged'] = location => history.replaceState(null, '', location);
_peerboardSettings['minHeight'] = window.innerHeight + "px";

script.onload = function () {
  let target = document.getElementById('peerboard-forum');
  if (target === null) {
    // Means that we have no the_content tag
    // Just embed inside the body
    target = document.body;
    document.body.innerHTML = '';
  }
  // Detect that all works within 10 sec
  setWaitingForReady(10000).then().catch(() => {
    alert("Something really unexpected happened - please contact us at integrations@peerboard.com");
  });

  window.PeerboardSDK.createForum(_peerboardSettings['board-id'], target, _peerboardSettings);
};

script.onerror = function (e) {
  console.error('failed to download sdk:', e);
};
document.head.append(script);
