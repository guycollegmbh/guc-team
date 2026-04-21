/**
 * GUC Team Members – Frontend JS
 * Handles: card rendering, category filtering, modal open/close/navigation.
 */
(function () {
  'use strict';

  // -------------------------------------------------------------------------
  // State
  // -------------------------------------------------------------------------
  var modal = null;
  var modalState = {
    instance:       null,  // which shortcode wrapper opened the modal
    categorySlug:   'all',
    memberIndex:    0,     // index within current category member list
  };

  // -------------------------------------------------------------------------
  // Init: run after DOM is ready
  // -------------------------------------------------------------------------
  document.addEventListener('DOMContentLoaded', function () {
    // Find all shortcode wrappers and initialise each.
    var wrappers = document.querySelectorAll('.guc-team-wrapper');
    wrappers.forEach(function (wrapper) {
      var instance = parseInt(wrapper.dataset.instance, 10);
      var data     = window['gucTeamData_' + instance];
      if (!data) return;
      initWrapper(wrapper, data);
    });

    // Modal is shared (only one in the DOM).
    modal = document.getElementById('guc-team-modal');
    if (modal) {
      initModal();
    }
  });

  // -------------------------------------------------------------------------
  // Per-wrapper initialisation
  // -------------------------------------------------------------------------
  function initWrapper(wrapper, data) {
    // Build a fast member lookup: id => member object
    var memberMap = {};
    data.members.forEach(function (m) {
      memberMap[m.id] = m;
    });

    // Build card DOM nodes using a <template> (one node per member, cloned per group).
    var cardTemplate = buildCardTemplate();

    // Render all groups.
    var groups = wrapper.querySelectorAll('.guc-team-group');
    groups.forEach(function (groupEl) {
      var slug      = groupEl.dataset.category;
      var groupData = data.groups.find(function (g) { return g.slug === slug; });
      if (!groupData) return;

      var grid = document.createElement('div');
      grid.className = 'guc-team-grid';
      groupEl.appendChild(grid);

      groupData.members.forEach(function (memberId) {
        var member = memberMap[memberId];
        if (!member) return;
        var card = buildCard(cardTemplate, member);
        card.addEventListener('click', function () {
          openModal(wrapper, data, memberMap, slug, memberId);
        });
        card.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openModal(wrapper, data, memberMap, slug, memberId);
          }
        });
        grid.appendChild(card);
      });
    });

    // Filter button click handlers.
    var filterBtns = wrapper.querySelectorAll('.guc-team-filter__btn');
    filterBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var category = btn.dataset.category;
        // Update active button.
        filterBtns.forEach(function (b) {
          b.classList.toggle('is-active', b === btn);
          b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
        });
        // Show/hide groups.
        groups.forEach(function (g) {
          var isActive = g.dataset.category === category;
          g.classList.toggle('is-active', isActive);
          g.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });
      });
    });
  }

  // -------------------------------------------------------------------------
  // Card building
  // -------------------------------------------------------------------------
  function buildCardTemplate() {
    var tpl = document.createElement('article');
    tpl.className = 'guc-team-card';
    tpl.setAttribute('tabindex', '0');
    tpl.setAttribute('role', 'button');
    tpl.innerHTML = [
      '<div class="guc-team-card__photo-wrap">',
      '  <img class="guc-team-card__photo" src="" alt="" loading="lazy">',
      '  <div class="guc-team-card__photo-placeholder" aria-hidden="true"></div>',
      '</div>',
      '<div class="guc-team-card__info">',
      '  <strong class="guc-team-card__name"></strong>',
      '  <span class="guc-team-card__function"></span>',
      '</div>',
    ].join('');
    return tpl;
  }

  function buildCard(template, member) {
    var card = template.cloneNode(true);
    var img  = card.querySelector('.guc-team-card__photo');
    var ph   = card.querySelector('.guc-team-card__photo-placeholder');

    if (member.photoGrid) {
      img.src = member.photoGrid;
      img.alt = member.fullName;
      ph.style.display = 'none';
    } else {
      img.style.display = 'none';
    }

    card.querySelector('.guc-team-card__name').textContent     = member.fullName;
    card.querySelector('.guc-team-card__function').textContent = member.function || '';
    card.dataset.memberId = member.id;

    return card;
  }

  // -------------------------------------------------------------------------
  // Modal
  // -------------------------------------------------------------------------
  function initModal() {
    // Close on backdrop click.
    modal.querySelector('.guc-team-modal__backdrop').addEventListener('click', closeModal);

    // Close button.
    modal.querySelector('.guc-team-modal__close').addEventListener('click', closeModal);

    // Keyboard: Escape = close, Arrow keys = navigate.
    document.addEventListener('keydown', function (e) {
      if (modal.hidden) return;
      if (e.key === 'Escape') {
        closeModal();
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        navigateModal(-1);
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        navigateModal(1);
      }
    });

    // Nav buttons.
    modal.querySelector('.guc-team-modal__nav--prev').addEventListener('click', function () {
      navigateModal(-1);
    });
    modal.querySelector('.guc-team-modal__nav--next').addEventListener('click', function () {
      navigateModal(1);
    });
  }

  function openModal(wrapper, data, memberMap, categorySlug, memberId) {
    var group = data.groups.find(function (g) { return g.slug === categorySlug; });
    if (!group) return;

    var idx = group.members.indexOf(memberId);
    if (idx === -1) return;

    modalState.instance     = { wrapper: wrapper, data: data, memberMap: memberMap };
    modalState.categorySlug = categorySlug;
    modalState.memberIndex  = idx;

    renderModal(group, memberMap);

    modal.hidden = false;
    document.body.classList.add('guc-modal-open');

    // Focus the close button for accessibility.
    modal.querySelector('.guc-team-modal__close').focus();
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove('guc-modal-open');
    modalState.instance = null;
  }

  function navigateModal(direction) {
    if (!modalState.instance) return;
    var data      = modalState.instance.data;
    var memberMap = modalState.instance.memberMap;
    var group     = data.groups.find(function (g) { return g.slug === modalState.categorySlug; });
    if (!group) return;

    var newIdx = modalState.memberIndex + direction;
    if (newIdx < 0 || newIdx >= group.members.length) return;

    modalState.memberIndex = newIdx;
    renderModal(group, memberMap);
  }

  function renderModal(group, memberMap) {
    var idx    = modalState.memberIndex;
    var member = memberMap[group.members[idx]];
    if (!member) return;

    // Main content.
    var img = modal.querySelector('.guc-team-modal__img');
    if (member.photoModal) {
      img.src = member.photoModal;
      img.alt = member.fullName;
      img.style.display = '';
    } else {
      img.style.display = 'none';
    }

    modal.querySelector('.guc-team-modal__name').textContent     = member.fullName;
    modal.querySelector('.guc-team-modal__function').textContent = member.function || '';

    // Description.
    var descEl = modal.querySelector('.guc-team-modal__description');
    if (member.description) {
      descEl.innerHTML  = member.description; // already sanitized server-side via wp_kses_post
      descEl.style.display = '';
    } else {
      descEl.innerHTML  = '';
      descEl.style.display = 'none';
    }

    // Phone.
    var phoneLink = modal.querySelector('.guc-team-modal__phone');
    if (member.phone) {
      phoneLink.href = 'tel:' + member.phone.replace(/\s/g, '');
      phoneLink.querySelector('.guc-team-modal__phone-text').textContent = member.phone;
      phoneLink.style.display = '';
    } else {
      phoneLink.style.display = 'none';
    }

    // Email.
    var emailLink = modal.querySelector('.guc-team-modal__email');
    if (member.email) {
      emailLink.href = 'mailto:' + member.email;
      emailLink.querySelector('.guc-team-modal__email-text').textContent = member.email;
      emailLink.style.display = '';
    } else {
      emailLink.style.display = 'none';
    }

    // Prev nav.
    var prevBtn = modal.querySelector('.guc-team-modal__nav--prev');
    prevBtn.disabled         = idx <= 0;
    prevBtn.style.visibility = idx <= 0 ? 'hidden' : '';

    // Next nav.
    var nextBtn = modal.querySelector('.guc-team-modal__nav--next');
    nextBtn.disabled         = idx >= group.members.length - 1;
    nextBtn.style.visibility = idx >= group.members.length - 1 ? 'hidden' : '';
  }

})();
