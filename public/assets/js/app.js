// Customer notes row toggles (Bootstrap 5 Collapse)
// - Rotates chevron when notes open/close
// - Auto-closes other open notes rows

document.addEventListener("DOMContentLoaded", () => {
  if (!window.bootstrap || !window.bootstrap.Collapse) return;

  const noteRows = Array.from(document.querySelectorAll('.collapse[id^="cust-notes-"]'));

  const getButton = (row) =>
    document.querySelector(`[data-bs-target="#${CSS.escape(row.id)}"]`);

  const setChevron = (btn, isOpen) => {
    if (!btn) return;
    const icon = btn.querySelector(".js-cust-notes-chevron");
    if (!icon) return;

    icon.classList.toggle("is-open", isOpen);
    btn.title = isOpen ? "Hide notes" : "Show notes";
    btn.setAttribute("aria-expanded", isOpen ? "true" : "false");
  };

  noteRows.forEach((row) => {
    row.addEventListener("show.bs.collapse", () => {
      // Auto-close others
      noteRows.forEach((other) => {
        if (other !== row && other.classList.contains("show")) {
          new bootstrap.Collapse(other, { toggle: false }).hide();
          setChevron(getButton(other), false);
        }
      });

      setChevron(getButton(row), true);
    });

    row.addEventListener("hide.bs.collapse", () => {
      setChevron(getButton(row), false);
    });

    // Initialize state on load
    setChevron(getButton(row), row.classList.contains("show"));
  });
});

// Bootstrap 5 custom form validation
(() => {
  'use strict';
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
})();