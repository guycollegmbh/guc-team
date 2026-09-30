/**
 * GUC Team Members – Admin Settings JS
 * Handles: copy shortcode to clipboard.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initShortcodeCopy();
  });

  // -------------------------------------------------------------------------
  // Copy shortcode to clipboard
  // -------------------------------------------------------------------------
  function initShortcodeCopy() {
    document.querySelectorAll('.guc-shortcode-copy').forEach(function (wrap) {
      var field    = wrap.querySelector('input');
      var btn      = wrap.querySelector('.guc-copy-button');
      var feedback = wrap.querySelector('.guc-copy-feedback');
      var resetTimer;

      function showFeedback(message, modifier) {
        feedback.textContent = message;
        feedback.className   = 'guc-copy-feedback guc-copy-feedback--' + modifier;

        clearTimeout(resetTimer);
        resetTimer = setTimeout(function () {
          feedback.textContent = '';
        }, 3000);
      }

      btn.addEventListener('click', function () {
        copyFieldValue(field)
          .then(function () {
            showFeedback(gucTeamSettings.copied, 'success');
          })
          .catch(function () {
            // Leave the shortcode selected so it can be copied manually.
            field.focus();
            field.select();
            showFeedback(gucTeamSettings.copyError, 'error');
          });
      });

      // Clicking into the field selects the whole shortcode.
      field.addEventListener('click', function () {
        field.select();
      });
    });
  }

  // The Clipboard API only exists on HTTPS; sites on plain HTTP fall back to execCommand.
  function copyFieldValue(field) {
    if (navigator.clipboard) {
      return navigator.clipboard.writeText(field.value);
    }
    field.focus();
    field.select();
    return document.execCommand('copy') ? Promise.resolve() : Promise.reject();
  }

})();
