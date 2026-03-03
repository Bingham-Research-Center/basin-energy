document.addEventListener("DOMContentLoaded", function () {
  const body = document.body;
  const popup = document.querySelector(".form-popup");
  const overlay = document.querySelector(".blur-bg-overlay");

  if (!popup) return;

  const loginTrigger = document.getElementById("login-trigger");
  const verifyTrigger = document.getElementById("verify-trigger");
  const closeBtn = popup.querySelector(".close-btn");

  /* ==============================
     reCAPTCHA (explicit render)
  ============================== */
  const recaptcha = {
    login: { id: "recaptcha-login", widgetId: null, rendered: false },
    signup: { id: "recaptcha-register", widgetId: null, rendered: false },
  };

  function currentView() {
    if (popup.classList.contains("show-signup")) return "signup";
    return "login";
  }

  function ensureRecaptcha(view) {
    if (!window.grecaptcha) {
      setTimeout(() => ensureRecaptcha(view), 80);
      return;
    }

    const cfg = recaptcha[view];
    if (!cfg) return;

    const el = document.getElementById(cfg.id);
    if (!el) return;

    if (cfg.rendered) {
      try {
        grecaptcha.reset(cfg.widgetId);
      } catch (_) {}
      return;
    }

    const sitekey = el.getAttribute("data-sitekey");
    if (!sitekey) return;

    try {
      cfg.widgetId = grecaptcha.render(el, { sitekey });
      cfg.rendered = true;
    } catch (e) {
      setTimeout(() => ensureRecaptcha(view), 120);
    }
  }

  /* ==============================
     HELPERS
  ============================== */
  function clearAllErrors() {
    popup.querySelectorAll(".field-error, .form-error").forEach((el) => el.remove());
    popup.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
  }

  function resetButtons() {
    popup.querySelectorAll(".auth-submit-btn").forEach((btn) => {
      if (btn.dataset.originalText) btn.innerHTML = btn.dataset.originalText;
      btn.classList.remove("loading");
      btn.disabled = false;
    });
  }

  function resetButton(btn) {
    if (!btn) return;
    if (btn.dataset.originalText) btn.innerHTML = btn.dataset.originalText;
    btn.classList.remove("loading");
    btn.disabled = false;
  }

  function showErrors(form, errors) {
    if (!form || !errors) return;
    form.querySelectorAll(".field-error, .form-error").forEach((el) => el.remove());
    form.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
    const showTopError = (msg) => {
      let top = form.querySelector(".form-error");
      if (!top) {
        top = document.createElement("div");
        top.className = "form-error";
        form.prepend(top);
      }
      top.textContent = msg;
    };

    Object.keys(errors).forEach((field) => {
      const message = errors[field]?.[0] || "Invalid value.";
      if (field === "g-recaptcha-response") {
        const captchaBox =
          form.querySelector("#recaptcha-login") ||
          form.querySelector("#recaptcha-register") ||
          form.querySelector(".g-recaptcha");

        if (captchaBox) {
          const err = document.createElement("div");
          err.className = "field-error";
          err.textContent = message;
          captchaBox.insertAdjacentElement("afterend", err);
        } else {
          showTopError(message);
        }
        return;
      }

      const input = form.querySelector(`[name="${field}"]`);
      if (!input) {
        showTopError(message);
        return;
      }

      const wrapper = input.closest(".input-field") || input.parentNode;

      const error = document.createElement("div");
      error.classList.add("field-error");
      error.innerText = message;
      wrapper.insertAdjacentElement("afterend", error);
      input.classList.add("is-invalid");
    });
  }

  function clearErrors(form) {
    if (!form) return;
    form.querySelectorAll(".field-error, .form-error").forEach((el) => el.remove());
    form.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
  }

  function setView(view) {
    popup.classList.remove("show-signup", "show-reset", "show-verify");
    if (view === "signup") popup.classList.add("show-signup");
    if (view === "reset") popup.classList.add("show-reset");
    if (view === "verify") popup.classList.add("show-verify");
    if (view === "login" || view === "signup") {
      setTimeout(() => ensureRecaptcha(view), 0);
    }
  }

  /* ==============================
     OPEN POPUP
  ============================== */
  if (loginTrigger) {
    loginTrigger.addEventListener("click", function (e) {
      e.preventDefault();
      body.classList.add("show-popup");
      clearAllErrors();
      resetButtons();
      setView("login");
      setTimeout(() => ensureRecaptcha("login"), 0);
    });
  }
  if (verifyTrigger) {
    verifyTrigger.addEventListener("click", function (e) {
      e.preventDefault();
      body.classList.add("show-popup");
      clearAllErrors();
      resetButtons();
      setView("verify");
    });
  }

  /* ==============================
     CLOSE POPUP
  ============================== */
  function closePopup() {
    body.classList.remove("show-popup");
    setView("login");
    clearAllErrors();
    resetButtons();
  }

  if (closeBtn) closeBtn.addEventListener("click", closePopup);
  if (overlay) overlay.addEventListener("click", closePopup);

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") closePopup();
  });

  /* ==============================
     FORM SWITCHING (Delegated)
  ============================== */
  document.addEventListener("click", function (e) {
    if (e.target.matches("#signup-link")) {
      e.preventDefault();
      clearAllErrors();
      resetButtons();
      setView("signup");
      return;
    }

    if (e.target.matches("#login-link")) {
      e.preventDefault();
      clearAllErrors();
      resetButtons();
      setView("login");
      return;
    }

    if (e.target.matches(".forgot-pass-link")) {
      e.preventDefault();
      clearAllErrors();
      resetButtons();
      setView("reset");
      return;
    }

    if (e.target.matches("#back-to-login")) {
      e.preventDefault();
      clearAllErrors();
      resetButtons();
      setView("login");
      return;
    }

    if (e.target.matches("#back-to-login-from-verify")) {
      e.preventDefault();
      clearAllErrors();
      resetButtons();
      setView("login");
      return;
    }
  });

  /* ==============================
     AJAX AUTH FORMS (Delegated)
  ============================== */
  popup.addEventListener("submit", async function (e) {
    const form = e.target.closest("form");
    if (!form) return;

    e.preventDefault();

    const btn = form.querySelector(".auth-submit-btn");
    if (!btn || btn.classList.contains("loading")) return;

    clearErrors(form);

    btn.classList.add("loading");
    btn.disabled = true;

    const loadingText = btn.getAttribute("data-loading-text");
    if (loadingText) {
      btn.dataset.originalText = btn.innerHTML;
      btn.innerHTML = loadingText;
    }

    const formData = new FormData(form);

    try {
      const response = await fetch(form.action, {
        method: (form.method || "POST").toUpperCase(),
        headers: {
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: formData,
      });

      const data = await response.json().catch(() => ({}));

      if (response.status === 422) {
        showErrors(form, data.errors || {});
        resetButton(btn);
        const view = currentView();
        if (view === "login" || view === "signup") ensureRecaptcha(view);
        return;
      }

      if (!response.ok) {
        showErrors(form, { _error: [data.message || "Something went wrong."] });
        resetButton(btn);

        const view = currentView();
        if (view === "login" || view === "signup") ensureRecaptcha(view);
        return;
      }

      if (data.verify) {
        setView("verify");

        const emailInput = document.getElementById("verify-email");
        if (emailInput && data.email) emailInput.value = data.email;

        const msg = document.getElementById("verify-message");
        if (msg) msg.textContent = data.message || "Please check your email for a verification link.";

        resetButton(btn);
        return;
      }

      if (data.redirect) {
        window.location.href = data.redirect;
        return;
      }

      resetButton(btn);
      closePopup();
    } catch (error) {
      console.error(error);
      showErrors(form, { _error: ["Network error. Please try again."] });
      resetButton(btn);

      const view = currentView();
      if (view === "login" || view === "signup") ensureRecaptcha(view);
    }
  });

  /* ==============================
     RESEND VERIFICATION (Delegated)
  ============================== */
  document.addEventListener("click", async function (e) {
    if (!e.target.matches("#resend-verification")) return;

    const resendBtn = e.target;
    const email = document.getElementById("verify-email")?.value || "";

    resendBtn.disabled = true;
    resendBtn.innerText = "Sending...";

    const msg = document.getElementById("verify-message");

    try {
      const resendUrl = document.getElementById("resend-url")?.value || "/email/resend";
      const response = await fetch(resendUrl, {
        method: "POST",
        headers: {
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
        },
        body: new URLSearchParams({ email }),
      });

      const data = await response.json().catch(() => ({}));

      if (!response.ok) {
        if (msg) msg.textContent = data.message || "Error. Try Again.";
        resendBtn.innerText = "Resend Verification Email";
        resendBtn.disabled = false;
        return;
      }

      if (msg) msg.textContent = data.message || "Verification email sent.";
      resendBtn.innerText = "Resend Verification Email";
      resendBtn.disabled = false;
    } catch (err) {
      if (msg) msg.textContent = "Error. Try Again.";
      resendBtn.innerText = "Resend Verification Email";
      resendBtn.disabled = false;
    }
  });
});