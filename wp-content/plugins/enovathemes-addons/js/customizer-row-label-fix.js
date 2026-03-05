(function ($, api) {
  "use strict";

  // Debounce helper
  function debounce(fn, wait) {
    let t;
    return function () {
      clearTimeout(t);
      t = setTimeout(fn, wait);
    };
  }

  function getLabelValue($row) {
    // Try your selector first; fall back to name-pattern (common in repeaters)
    const $input = $row.find('input[data-field="link_text"], input[name*="[link_text]"]').first();
    const val = ($input.val() || "").trim();
    return val.length ? val : "Row";
  }

  function applyLabel($row) {
    const $label = $row.find(".repeater-row-label");
    if (!$label.length) return;

    const txt = getLabelValue($row);
    if ($label.text().trim() !== txt) {
      $label.text(txt);
    }
  }

  function updateAll(context) {
    const $root = context ? $(context) : $(document);
    $root.find(".repeater-row").each(function () {
      applyLabel($(this));
    });
  }

  // Run updates *after* Customizer/Backbone rerenders
  const scheduleUpdate = debounce(function () {
    // ensure we run after any synchronous DOM writes
    requestAnimationFrame(() => updateAll());
  }, 80);

  // Initial pass when controls load
  $(updateAll);

  // Update when the specific input changes (delegated for future rows)
  $(document).on(
    "input change keyup",
    'input[data-field="link_text"], input[name*="[link_text]"]',
    scheduleUpdate
  );

  // Observe DOM mutations (rows added/removed, labels re-rendered)
  const observer = new MutationObserver(scheduleUpdate);
  observer.observe(document.body, { childList: true, subtree: true });

  // Also hook Customizer ready (some controls mount a bit later)
  if (api && api.bind) {
    api.bind("ready", scheduleUpdate);
  }
})(jQuery, window.wp && wp.customize);
