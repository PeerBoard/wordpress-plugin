var script = document.createElement('script');
script.src = _peerboardSettings['embed-url'];
script.setAttribute("async", "");
script.setAttribute("data-skip-init", "");

script.onload = function () {
  // TODO: detect no id
  // TODO: detect if went wrong loading and show alert
  window.PeerboardSDK.createForum(_peerboardSettings['board-id'], document.getElementById('circles-forum'), _peerboardSettings)
};

script.onerror = function (e) {
  console.error('failed to download sdk:', e);
};
document.head.append(script);
