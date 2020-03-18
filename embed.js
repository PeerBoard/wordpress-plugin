(function () {
  'use strict';

  const env = {"BASE_URL":"http://local.is"};

  function isForumNavEvent(e) {
      return e.type === 'forum-navigation';
  }
  function isForumTitleEvent(e) {
      return e.type === 'forum-title';
  }

  (() => {
      const DATA_ATTR_ID = 'data-forum-id';
      const DATA_ATTR_PREFIX = 'data-forum-prefix';
      const DATA_ATTR_HIDE_MENU = 'data-forum-hide-menu';
      const DATA_ATTR_CONTAINER_ID = 'data-forum-container-id';
      const DATA_ATTR_WP_LOGIN = 'data-forum-wp-login';
      const thisScriptTag = document.currentScript;
      const communityID = thisScriptTag.getAttribute(DATA_ATTR_ID);
      if (!communityID) {
          console.error(`${DATA_ATTR_ID} must be defined`);
          return;
      }
      const prefix = thisScriptTag.getAttribute(DATA_ATTR_PREFIX) || '';
      const baseURL = env.BASE_URL;
      const prefixRgx = new RegExp(`^/${communityID}`);
      const forumURL = new URL(baseURL);
      const wpLoginPayload = thisScriptTag.getAttribute(DATA_ATTR_WP_LOGIN);
      if (wpLoginPayload) {
          forumURL.href = `${forumURL.protocol}//login.${forumURL.host}/${communityID}/login/signed/${wpLoginPayload}`;
      }
      else {
          const { pathname } = window.location;
          const strippedPath = prefix ? pathname.replace(`/${prefix}`, '') : pathname;
          forumURL.pathname = `/${communityID}${strippedPath}`;
      }
      const hideMenu = thisScriptTag.hasAttribute(DATA_ATTR_HIDE_MENU);
      if (hideMenu) {
          forumURL.searchParams.set('hideTopMenu', 'true');
      }
      const iframe = document.createElement('iframe');
      iframe.src = forumURL.href;
      iframe.style.border = 'none';
      iframe.style.width = '100%';
      iframe.style.height = '100%';
      const containerID = thisScriptTag.getAttribute(DATA_ATTR_CONTAINER_ID);
      if (containerID) {
          const container = document.getElementById(containerID);
          if (container === null) {
              console.error('forum: container tag not found');
              return;
          }
          container.appendChild(iframe);
      }
      else {
          thisScriptTag.insertAdjacentElement('afterend', iframe);
      }
      const sendMessage = (msg) => {
          const w = iframe.contentWindow;
          if (!w) {
              console.error('no child window');
              return;
          }
          w.postMessage(msg, baseURL);
      };
      window.addEventListener('popstate', () => sendMessage({
          type: 'forum-popstate',
      }));
      window.addEventListener('message', event => {
          if (event.origin !== forumURL.origin) {
              return; // some other window sent this event
          }
          const payload = event.data;
          if (isForumNavEvent(payload)) {
              const location = `${payload.pathname.replace(prefixRgx, prefix ? `/${prefix}` : '')}${payload.search}`;
              history.pushState(null, '', location);
              return;
          }
          if (isForumTitleEvent(payload)) {
              document.title = payload.title;
              return;
          }
          if (payload.type === 'forum-ready') {
              const msg = {
                  type: 'forum-referrer',
                  referrer: document.referrer,
              };
              sendMessage(msg);
          }
      });
  })();

}());
//# sourceMappingURL=embed.js.map
