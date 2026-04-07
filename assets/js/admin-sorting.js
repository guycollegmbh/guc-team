/**
 * GUC Team Members – Admin Sorting JS
 * Handles: tab switching, SortableJS drag & drop, AJAX save.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initTabs();
    initSortable();
    initSaveButtons();
  });

  // -------------------------------------------------------------------------
  // Tab switching
  // -------------------------------------------------------------------------
  function initTabs() {
    var tabLinks = document.querySelectorAll('.guc-sorting-tab-nav a');
    tabLinks.forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        var targetId = link.getAttribute('href').replace('#', '');

        tabLinks.forEach(function (l) { l.classList.remove('active'); });
        link.classList.add('active');

        document.querySelectorAll('.guc-sorting-tab-panel').forEach(function (panel) {
          panel.classList.toggle('active', panel.id === targetId);
        });
      });
    });
  }

  // -------------------------------------------------------------------------
  // SortableJS drag & drop
  // -------------------------------------------------------------------------
  function initSortable() {
    document.querySelectorAll('.guc-sortable-list').forEach(function (list) {
      Sortable.create(list, {
        handle:    '.guc-drag-handle',
        animation: 150,
        ghostClass: 'guc-sortable-ghost',
        chosenClass: 'guc-sortable-chosen',
      });
    });
  }

  // -------------------------------------------------------------------------
  // Save buttons
  // -------------------------------------------------------------------------
  function initSaveButtons() {
    document.querySelectorAll('.guc-save-order').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var category  = btn.dataset.category;
        var panel     = btn.closest('.guc-sorting-tab-panel');
        var list      = panel.querySelector('.guc-sortable-list');
        var feedback  = panel.querySelector('.guc-save-feedback');
        var order     = [];

        list.querySelectorAll('.guc-sortable-item').forEach(function (item) {
          order.push(item.dataset.id);
        });

        btn.disabled      = true;
        btn.textContent   = gucTeamSorting.saving;
        feedback.style.display = 'none';
        feedback.className = 'guc-save-feedback';

        var formData = new FormData();
        formData.append('action',   'guc_team_save_order');
        formData.append('nonce',    gucTeamSorting.nonce);
        formData.append('category', category);
        order.forEach(function (id) {
          formData.append('order[]', id);
        });

        fetch(gucTeamSorting.ajaxUrl, {
          method: 'POST',
          body:   formData,
          credentials: 'same-origin',
        })
          .then(function (res) { return res.json(); })
          .then(function (json) {
            btn.disabled    = false;
            btn.textContent = gucTeamSorting.saved;
            setTimeout(function () {
              btn.textContent = 'Save Order';
            }, 2000);

            if (json.success) {
              feedback.textContent  = gucTeamSorting.saved;
              feedback.className    = 'guc-save-feedback guc-save-feedback--success';
            } else {
              feedback.textContent  = json.data && json.data.message ? json.data.message : gucTeamSorting.error;
              feedback.className    = 'guc-save-feedback guc-save-feedback--error';
            }
            feedback.style.display = 'inline-block';
          })
          .catch(function () {
            btn.disabled    = false;
            btn.textContent = 'Save Order';
            feedback.textContent  = gucTeamSorting.error;
            feedback.className    = 'guc-save-feedback guc-save-feedback--error';
            feedback.style.display = 'inline-block';
          });
      });
    });
  }

})();
