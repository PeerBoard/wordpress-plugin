/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/js/frontend.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./node_modules/@peerboard/core/dist/peerboard-core.cjs.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@peerboard/core/dist/peerboard-core.cjs.js ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, '__esModule', { value: true });

/*! *****************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */

var __assign = function() {
    __assign = Object.assign || function __assign(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};

var PEERBOARD_EMBED_SDK_URL = 'https://static.peerboard.com/embed/embed.js';
var trimLeftSlash = function (str) {
    return str.startsWith('/') ? str.substr(1) : str;
};
var forumSDK = null;
var loadingSDK = null;
var loadSdk = function (embedSDKURL) {
    if (forumSDK !== null) {
        return Promise.resolve();
    }
    if (loadingSDK !== null) {
        return loadingSDK;
    }
    return loadingSDK = new Promise(function (resolve, reject) {
        var script = document.createElement('script');
        script.src = embedSDKURL || PEERBOARD_EMBED_SDK_URL;
        script.setAttribute("async", "");
        script.setAttribute("data-skip-init", "");
        script.onload = function () {
            forumSDK = window.PeerboardSDK;
            resolve();
        };
        script.onerror = function () {
            console.error('failed to download sdk');
            reject();
            loadingSDK = null;
        };
        document.head.append(script);
    });
};
var defaultOptions = {
    resize: true,
    hideMenu: true,
    baseURL: "https://peerboard." + window.document.location.hostname,
    sdkURL: PEERBOARD_EMBED_SDK_URL,
    onTitleChanged: function (title) { return window.document.title = title; },
    onPathChanged: function (newPath) { return window.history.replaceState({}, window.document.title, newPath); }
};
var createForum = function (forumID, container, options) {
    var opts = __assign(__assign({}, defaultOptions), { scrollToTopOnNavigationChanged: true });
    if (!opts.usePathFromQs) {
        // Auto resolve redirect
        opts.path = ((options.prefix && options.prefix !== "/")
            ? document.location.pathname.replace(new RegExp("^/" + trimLeftSlash(options.prefix)), '')
            : document.location.pathname) + document.location.search + document.location.hash;
    }
    Object.assign(opts, options);
    return loadSdk(options.sdkURL).then(function () {
        if (!forumSDK) {
            throw new Error("Forum should be loaded at the moment.");
        }
        return new Promise(function (resolve, reject) {
            var api = forumSDK.createForum(forumID, container, __assign(__assign({}, opts), { onFail: function () {
                    if (opts.onFail) {
                        opts.onFail();
                    }
                    reject(new Error("failed to initialize PeerBoard iframe internals"));
                }, onReady: function () {
                    if (opts.onReady) {
                        opts.onReady();
                    }
                    resolve(api);
                } }));
        });
    }).catch(function (err) {
        console.error("Error creating forum: ", err);
        if (options.onFail) {
            options.onFail();
        }
        throw err;
    });
};

exports.loadSdk = loadSdk;
exports.createForum = createForum;
//# sourceMappingURL=peerboard-core.cjs.js.map


/***/ }),

/***/ "./src/js/frontend.js":
/*!****************************!*\
  !*** ./src/js/frontend.js ***!
  \****************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _functions_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./functions.js */ "./src/js/functions.js");
/* harmony import */ var _functions_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_functions_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _peerboard_core__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @peerboard/core */ "./node_modules/@peerboard/core/dist/peerboard-core.cjs.js");
/* harmony import */ var _peerboard_core__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_peerboard_core__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _inc_create_forum__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./inc/create-forum */ "./src/js/inc/create-forum.js");
/**
 * Gutenberg Blocks
 *
 * All blocks related JavaScript files should be imported here.
 * You can create a new block folder in this dir and include code
 * for that block here as well.
 *
 * All blocks should be included here since this is the file that
 * Webpack is compiling as the input file.
 */


/**
 * Create forum
 */


Object(_inc_create_forum__WEBPACK_IMPORTED_MODULE_2__["default"])(_peerboard_core__WEBPACK_IMPORTED_MODULE_1__["createForum"]);

/***/ }),

/***/ "./src/js/functions.js":
/*!*****************************!*\
  !*** ./src/js/functions.js ***!
  \*****************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * Vanilla js serialize array function
 * @param {*} form 
 */
var serializeArray = function serializeArray(form) {
  var arr = [];
  Array.prototype.slice.call(form.elements).forEach(function (field) {
    if (!field.name || field.disabled || ['file', 'reset', 'submit', 'button'].indexOf(field.type) > -1) return;

    if (field.type === 'select-multiple') {
      Array.prototype.slice.call(field.options).forEach(function (option) {
        if (!option.selected) return;
        arr.push({
          name: field.name,
          value: option.value
        });
      });
      return;
    }

    if (['checkbox', 'radio'].indexOf(field.type) > -1 && !field.checked) return;
    arr.push({
      name: field.name,
      value: field.value
    });
  });
  return arr;
};

/***/ }),

/***/ "./src/js/inc/create-forum.js":
/*!************************************!*\
  !*** ./src/js/inc/create-forum.js ***!
  \************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (createForum) {
  var setWaitingForReady = function setWaitingForReady(timeout) {
    return new Promise(function (resolve, reject) {
      _peerboardSettings['onReady'] = function () {
        resolve();
      };

      setTimeout(function () {
        reject();
      }, timeout);
    });
  };

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
    var target = document.getElementById('peerboard-forum');

    if (target === null) {
      return;
    }

    document.querySelector("link[rel=canonical]").setAttribute("href", document.location.origin + document.location.pathname);

    try {
      document.querySelector("meta[name=description]").remove();
    } catch (_) {}

    ;
  }

  _peerboardSettings['onTitleChanged'] = function (title) {
    window.document.title = title;
  };

  _peerboardSettings['onPathChanged'] = function (location) {
    return history.replaceState(null, '', location);
  };

  _peerboardSettings['minHeight'] = window.innerHeight + "px";

  _peerboardSettings['onLogout'] = function () {
    document.cookie = 'wp-peerboard-auth=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;';
  };

  _peerboardSettings['onFail'] = function () {
    console.error('Failed to load forum - please contact us at support_wp@peerboard.com');
    alert("Something really unexpected happened - please contact us at support_wp@peerboard.com");
  };

  docReady(function () {
    var target = document.getElementById('peerboard-forum');

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
});

/***/ })

/******/ });
//# sourceMappingURL=frontend.js.map