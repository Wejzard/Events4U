
$(document).on("submit", "#eventForm", function (e) {
  e.preventDefault();

  const fd  = new FormData(this);
  const url = Constants.PROJECT_BASE_URL + "/events";
  const $btn = $("#submitEventBtn");

  console.log("[post-event] POST ->", url);

  // optional: prevent double-click submits
  if ($btn.length) $btn.prop("disabled", true);

  $.ajax({
    url: url,
    type: "POST",
    data: fd,
    processData: false,
    contentType: false,
    beforeSend: function (xhr) {
      const token = localStorage.getItem("user_token");
      if (token) xhr.setRequestHeader("Authentication", token); // matches RestClient
      else console.warn("[post-event] No user_token in localStorage");
    },
    success: function (res) {
      console.log("[post-event] SUCCESS", res);
      toastr.success(res.message || "Event created!");

      // Determine category to show (prefer backend response, fallback to form)
      const category = (res && res.category) ? res.category : (fd.get("category") || "POP");

      // Navigate to main view (SPApp) and load that category
      window.location.hash = "#main";
      if (typeof EventService !== "undefined") {
        EventService.loadAll(category, 1);
      }

      // optional: reset the form
      try { document.getElementById("eventForm").reset(); } catch (e) {}
    },
    error: function (xhr) {
      console.error("[post-event] ERROR", xhr);
      toastr.error(xhr.responseJSON?.message || xhr.statusText || "Error creating event.");
    },
    complete: function () {
      if ($btn.length) $btn.prop("disabled", false);
    }
  });
});


/*
OLD POST-EVENT.JS

// Delegated so it works with SPApp-injected content
$(document).on("submit", "#eventForm", function (e) {
  e.preventDefault();

  const fd = new FormData(this);
  const url = Constants.PROJECT_BASE_URL + "/events";
  console.log("[post-event] POST ->", url);

  $.ajax({
    url: url,
    type: "POST",
    data: fd,
    processData: false,
    contentType: false,
    beforeSend: function (xhr) {
      const token = localStorage.getItem("user_token");
      if (token) xhr.setRequestHeader("Authentication", token); // matches RestClient
      else console.warn("[post-event] No user_token in localStorage");
    },
    success: function (res) {
      console.log("[post-event] SUCCESS", res);
      toastr.success(res.message || "Event created!");
      if (typeof EventService !== "undefined") EventService.loadAll("POP", 1);
    },
    error: function (xhr) {
      console.error("[post-event] ERROR", xhr);
      toastr.error(xhr.responseJSON?.message || xhr.statusText || "Error creating event.");
    }
  });
});

*/ 