// frontend/services/post-event.js
$(document).on("submit", "#eventForm", function (e) {
  e.preventDefault();

  const formEl = this;
  const fd = new FormData(formEl);
  const url = Constants.PROJECT_BASE_URL + "/events";
  const $btn = $("#submitEventBtn");

  // category from form (must match sidebar values)
  const formCategory = (fd.get("category") || "ALL").toString().trim();

  console.log("[post-event] POST ->", url, "category:", formCategory);

  if ($btn.length) $btn.prop("disabled", true).text("Submitting...");

  $.ajax({
    url: url,
    type: "POST",
    data: fd,
    processData: false,
    contentType: false,
    beforeSend: function (xhr) {
      const token = localStorage.getItem("user_token");
      if (token) {
        xhr.setRequestHeader("Authorization", token);
        xhr.setRequestHeader("Authentication", token);
      } else {
        console.warn("[post-event] No user_token in localStorage");
      }
    },
    success: function (res) {
      console.log("[post-event] SUCCESS", res);

      if (window.toastr) toastr.success(res.message || "Event created!");
      else console.log(res.message || "Event created!");

      // Prefer backend response category, fallback to form
      const postedCategory = (res?.category || formCategory || "ALL").toString().trim();

      // Reset form
      try {
        formEl.reset();
      } catch (_) {}

      // Go back to main and reload list
      window.location.hash = "#main";

      // Give SPApp a tiny moment to ensure main is visible
      setTimeout(() => {
        if (typeof EventService !== "undefined") {
          // If category is weird, just go ALL
          if (!postedCategory || postedCategory === "ALL") {
            EventService.loadAll("ALL", 1);
          } else {
            EventService.loadAll(postedCategory, 1);
          }
        } else {
          console.error("[post-event] EventService not loaded.");
        }
      }, 150);
    },
    error: function (xhr) {
      console.error("[post-event] ERROR", xhr);
      const msg =
        xhr.responseJSON?.message ||
        xhr.responseJSON?.error ||
        xhr.responseText ||
        xhr.statusText ||
        "Error creating event.";

      if (window.toastr) toastr.error(msg);
      else console.error(msg);
    },
    complete: function () {
      if ($btn.length) $btn.prop("disabled", false).text("Submit Event");
    },
  });
});
