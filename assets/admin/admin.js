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
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/js/admin.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/js/admin.js":
/*!*************************!*\
  !*** ./src/js/admin.js ***!
  \*************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _admin_feedback_form__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./admin/feedback-form */ "./src/js/admin/feedback-form.js");
/**
 * Feedback form
 */

Object(_admin_feedback_form__WEBPACK_IMPORTED_MODULE_0__["default"])();

/***/ }),

/***/ "./src/js/admin/feedback-form.js":
/*!***************************************!*\
  !*** ./src/js/admin/feedback-form.js ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (() => {
  function modal_init() {
    let deactivate_button = document.getElementById('deactivate-peerboard');

    if (!deactivate_button) {
      return;
    }

    let modal,
        close,
        modal_deactivation_button,
        deactivation_url = deactivate_button.href,
        reasons;
    /**
     * Open modal
     * @param {*} ev 
     */

    deactivate_button.onclick = ev => {
      ev.preventDefault();
      let formData = new FormData();
      formData.append('action', 'peerboard_add_deactivation_feedback_dialog_box');
      fetch(window.peerboard_admin.ajax_url, {
        method: 'POST',
        body: formData
      }).then(response => {
        if (response.ok) {
          return response.json();
        } else {
          console.log('Looks like there was a problem. Status Code: ' + response.status);
        }
      }).then(data => {
        if (data.success) {
          let response = data.data;
          document.querySelector('body').append(stringToHTML(response));
          modal = document.getElementById('peerboard-modal-deactivation-feedback');
          close = modal.querySelector('.button-close');
          modal_deactivation_button = modal.querySelector('.button-deactivate');
          reasons = modal.querySelectorAll('.reason');
          modal.classList.add('active');
          modal_deactivation_button.href = deactivation_url; // Close modal and remove it

          close.onclick = ev => {
            ev.preventDefault();
            modal.classList.remove('active');
            modal.remove();
          };

          reasons_logic();

          modal_deactivation_button.onclick = ev => {
            ev.preventDefault();
            modal_deactivation_button.disabled = true;
            send_feedback();
          };
        } else {
          console.log(console.error(data));
        }
      }).catch(console.error);
    };

    function reasons_logic() {
      /**
      * If reasons list have additional field show
      */
      reasons.forEach((elem, key) => {
        elem.onclick = ev => {
          // disable all actives 
          modal.querySelectorAll('.reason.active').forEach(elem => {
            elem.classList.remove('active');
            elem.querySelector('input.main_reason').checked = false;
          });
          elem.classList.add('active');
          elem.querySelector('input.main_reason').checked = true;
        };
      });
    }
    /**
     * Send feedback
     */


    function send_feedback() {
      let formData = new FormData(),
          main_reason_wrap = modal.querySelector('.reason.active');

      if (main_reason_wrap) {
        let main_reason = main_reason_wrap.querySelector('input.main_reason').value,
            additional_info = main_reason_wrap.querySelector('.additional_field input').value;
        formData.append('main_reason', main_reason);
        formData.append('additional_info', additional_info);
      }

      formData.append('action', 'peerboard_feedback_request');
      fetch(window.peerboard_admin.ajax_url, {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(data => {
        if (data.success) {
          let response = data.data;
          window.location.href = deactivation_url;
        } else {
          console.error(data);
        }
      }).catch(console.error);
    }
  }
  /**
  * Load after full page ready + some seconds 
  */


  custom_on_load(modal_init);
  /**
  * On load custom function
  * @param {*} callback 
  */

  function custom_on_load(callback) {
    if (window.addEventListener) window.addEventListener("load", callback, false);else if (window.attachEvent) window.attachEvent("onload", callback);else window.onload = callback;
  }
  /**
  * Convert a template string into HTML DOM nodes
  * @param  {String} str The template string
  * @return {Node}       The template HTML
  */


  function stringToHTML(str) {
    var dom = document.createElement('div');
    dom.innerHTML = str;
    return dom;
  }

  ;
});

/***/ })

/******/ });
//# sourceMappingURL=admin.js.map