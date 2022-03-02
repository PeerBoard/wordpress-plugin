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
/* harmony import */ var _admin_settings_page__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./admin/settings-page */ "./src/js/admin/settings-page.js");
/**
 * Feedback form
 */


var admin_path = window.location.href; // if plugin activation or deactivation page

if (admin_path.includes('plugins.php')) {
  Object(_admin_feedback_form__WEBPACK_IMPORTED_MODULE_0__["default"])();
} // if plugin activation or deactivation page


if (admin_path.includes('page=peerboard')) {
  Object(_admin_settings_page__WEBPACK_IMPORTED_MODULE_1__["default"])();
}

/***/ }),

/***/ "./src/js/admin/feedback-form.js":
/*!***************************************!*\
  !*** ./src/js/admin/feedback-form.js ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function () {
  function modal_init() {
    var deactivate_button = document.getElementById('deactivate-peerboard');

    if (!deactivate_button) {
      return;
    }

    var modal,
        close,
        modal_deactivation_button,
        deactivation_url = deactivate_button.href,
        reasons;
    /**
     * Open modal
     * @param {*} ev 
     */

    deactivate_button.onclick = function (ev) {
      ev.preventDefault();
      var formData = new FormData();
      formData.append('action', 'peerboard_add_deactivation_feedback_dialog_box');
      fetch(window.peerboard_admin.ajax_url, {
        method: 'POST',
        body: formData
      }).then(function (response) {
        if (response.ok) {
          return response.json();
        } else {
          console.log('Looks like there was a problem. Status Code: ' + response.status);
        }
      }).then(function (data) {
        if (data.success) {
          var response = data.data;
          document.querySelector('body').append(stringToHTML(response));
          modal = document.getElementById('peerboard-modal-deactivation-feedback');
          close = modal.querySelector('.button-close');
          modal_deactivation_button = modal.querySelector('.button-deactivate');
          reasons = modal.querySelectorAll('.reason');
          modal.classList.add('active');
          modal_deactivation_button.href = deactivation_url;
          modal_deactivation_button.disabled = true; // Close modal and remove it

          close.onclick = function (ev) {
            ev.preventDefault();
            modal.classList.remove('active');
            modal.remove();
          };

          reasons_logic();

          modal_deactivation_button.onclick = function (ev) {
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
      reasons.forEach(function (elem, key) {
        // on reason click
        elem.onclick = function (ev) {
          // disable all actives 
          modal.querySelectorAll('.reason.active').forEach(function (elem) {
            elem.classList.remove('active');
            elem.querySelector('input.main_reason').checked = false;
          });
          elem.classList.add('active');
          elem.querySelector('input.main_reason').checked = true;

          if (is_form_valid()) {
            modal_deactivation_button.disabled = false;
          } else {
            modal_deactivation_button.disabled = true;
          }
        };
      }); // additional field changes

      modal.querySelectorAll('.additional_field input').forEach(function (elem, key) {
        elem.oninput = function (ev) {
          if (is_form_valid()) {
            modal_deactivation_button.disabled = false;
          } else {
            modal_deactivation_button.disabled = true;
            elem.style.borderColor = "red";
          }
        };
      });
    }

    function is_form_valid() {
      var reason = modal.querySelector('.reason.active .additional_field input');

      if (reason) {
        if (reason.value === null || reason.value === "") {
          return false;
        }

        reason.style.borderColor = "black";
        return true;
      }

      return true;
    }
    /**
     * Send feedback
     */


    function send_feedback() {
      var formData = new FormData(),
          main_reason_wrap = modal.querySelector('.reason.active');

      if (main_reason_wrap) {
        var main_reason = main_reason_wrap.querySelector('input.main_reason').value,
            additional_info = main_reason_wrap.querySelector('.additional_field input');

        if (additional_info) {
          additional_info = additional_info.value;
          formData.append('additional_info', additional_info);
        }

        formData.append('main_reason', main_reason);
      }

      formData.append('action', 'peerboard_feedback_request');
      fetch(window.peerboard_admin.ajax_url, {
        method: 'POST',
        body: formData
      }).then(function (response) {
        return response.json();
      }).then(function (data) {
        if (data.success) {
          var response = data.data;
          window.location.href = deactivation_url;
        } else {
          console.error(data);
          window.location.href = deactivation_url;
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

/***/ }),

/***/ "./src/js/admin/settings-page.js":
/*!***************************************!*\
  !*** ./src/js/admin/settings-page.js ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function () {
  var user_sync_checkbox = document.querySelector('#peerboard_users_sync_enabled');
  var bulk_activate_email_notifications = document.querySelector('#peerboard_bulk_activate_email');
  var expose_user_data = document.querySelector('#expose_user_data');
  var manually_sync_users = document.querySelector('#sync_users');
  var manually_sync_users_image = document.querySelector('#sync_users img');
  var users_amount_pages = document.querySelector('#sync-progress').getAttribute('page-count');
  var old_text = manually_sync_users.querySelector('.text').innerHTML;
  var new_text = 'Importing'; // update sync settings

  function check_user_settings_sync() {
    if (!user_sync_checkbox.checked) {
      bulk_activate_email_notifications.disabled = true;
      bulk_activate_email_notifications.checked = false;
      expose_user_data.disabled = true;
      expose_user_data.checked = false;
      manually_sync_users.disabled = true;
    } else {
      bulk_activate_email_notifications.disabled = false;
      expose_user_data.disabled = false;
      manually_sync_users.disabled = false;
    }
  }

  user_sync_checkbox.onchange = function (element) {
    check_user_settings_sync();
  };

  window.onload = function () {
    check_user_settings_sync();
  };
  /**
   * On import button click
   * @returns 
   */


  manually_sync_users.onclick = function () {
    if (manually_sync_users.disabled) {
      return;
    } // show progress bar


    document.querySelector('.sync-progress-wrap').style.display = 'block';
    document.querySelector('#result_message_success').style.display = 'none';
    document.querySelector('#result_message_waring').style.display = 'none';
    import_users();
  };

  function import_users() {
    manually_sync_users_image.className = 'rotating';
    manually_sync_users.disabled = true;
    manually_sync_users.querySelector('.text').innerHTML = new_text;
    var every_step_persent = 100 / users_amount_pages;
    fetch(window.peerboard_admin.user_sync_url, {
      method: 'POST',
      body: JSON.stringify({
        'paged': document.querySelector('#sync-progress').getAttribute('current-page'),
        'expose_user_data': document.querySelector('#expose_user_data').checked,
        'peerboard_bulk_activate_email': document.querySelector('#peerboard_bulk_activate_email').checked
      }),
      headers: {
        'X-WP-Nonce': document.querySelector('#_wp_rest_nonce').value
      }
    }).then(function (response) {
      return response.json();
    }).then(function (data) {
      if (data.success) {
        var response = data.data;
        var current_page = document.querySelector('#sync-progress').getAttribute('current-page');
        console.log(response);
        document.querySelector('#sync-progress').setAttribute('current-page', parseInt(current_page) + 1);
        var width = Math.round(every_step_persent * parseInt(current_page) + 1);

        if (width > 100) {
          width = 100;
        }

        document.querySelector('#bar').style.width = width + "%";
        document.querySelector('#sync-progress #bar').innerHTML = width + "%"; // next page request

        if (response.resume) {
          import_users();
        } // after last page request is ready


        if (!response.resume) {
          importing_finished(data);
        }
      } else {
        importing_finished(data);
      }
    }).catch(console.error);
  }

  function importing_finished(data) {
    var response = data.data;
    manually_sync_users_image.classList.remove("rotating");
    manually_sync_users.querySelector('.text').innerHTML = old_text;

    if (data.success) {
      // show success message
      document.querySelector('#result_message_success').innerHTML = response.message;
      document.querySelector('#result_message_success').style.display = 'block';
    } else {
      document.querySelector('#result_message_waring').innerHTML = response.message;
      document.querySelector('#result_message_waring').style.display = 'block';
      manually_sync_users.disabled = false;
    } // hide progress bar


    document.querySelector('.sync-progress-wrap').style.display = 'none';
  }
});

/***/ })

/******/ });
//# sourceMappingURL=admin.js.map