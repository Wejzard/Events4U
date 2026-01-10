// frontend/services/event-details.js

(function () {
  function renderWhenDomReady(cb) {
    // SPApp loads views async, so wait until event.html elements exist
    const maxTries = 30; // 30 * 100ms = 3 seconds
    let tries = 0;

    const timer = setInterval(() => {
      tries++;

      if ($("#eventTitle").length > 0) {
        clearInterval(timer);
        cb();
      }

      if (tries >= maxTries) {
        clearInterval(timer);
        console.error("Event details DOM not found (#eventTitle missing). Check event.html IDs.");
      }
    }, 100);
  }

  function setAvailabilityUI(e) {
    // Requires event.html to have:
    // #eventAvailability, #reserveBtn, #buyBtn
    const $avail = $("#eventAvailability");
    const $reserve = $("#reserveBtn");
    const $buy = $("#buyBtn");

    if (!$avail.length) return; // safety if you didn't add it yet

    const limit = parseInt(e?.ticket_limit ?? 0, 10);

    // If not set or 0 => unlimited
    if (!limit || limit <= 0) {
      $avail.text("Unlimited");
      if ($reserve.length) $reserve.prop("disabled", false);
      if ($buy.length) $buy.removeClass("disabled").removeAttr("aria-disabled");
      return;
    }

    // If backend provides used_qty, show exact remaining
    const usedProvided = e?.used_qty !== undefined && e?.used_qty !== null;
    const used = usedProvided ? parseInt(e.used_qty, 10) : null;

    if (usedProvided && !Number.isNaN(used)) {
      const available = Math.max(limit - used, 0);

      $avail.text(available > 0 ? `${available} left` : "SOLD OUT");

      if (available <= 0) {
        if ($reserve.length) $reserve.prop("disabled", true);
        if ($buy.length) $buy.addClass("disabled").attr("aria-disabled", "true");
      } else {
        if ($reserve.length) $reserve.prop("disabled", false);
        if ($buy.length) $buy.removeClass("disabled").removeAttr("aria-disabled");
      }
    } else {
      // We know it's limited, but don't know remaining (still enforced on backend)
      $avail.text("Limited");
      if ($reserve.length) $reserve.prop("disabled", false);
      if ($buy.length) $buy.removeClass("disabled").removeAttr("aria-disabled");
    }
  }

  function loadEventDetails() {
    const eventId = localStorage.getItem("selected_event_id");

    // Only act when we are on #event
    if (window.location.hash !== "#event") return;

    if (!eventId) {
      // No toastr here to avoid blank box; just return to main
      window.location.hash = "#main";
      return;
    }

    renderWhenDomReady(() => {
      RestClient.get(
        `/events/${eventId}`,
        function (res) {
          const e = res?.data ? res.data : res;

          $("#eventTitle").text(e.title || "Untitled event");
          $("#eventDesc").text(e.description || "No description available.");
          $("#eventLocation").text(e.location || "-");

          const dateTime = `${e.event_date || "-"} ${e.event_time || ""}`.trim();
          $("#eventDateTime").text(dateTime);

          $("#eventPrice").text(`${e.price ?? "-"} KM`);

          if (e.image) {
            $("#eventImage").attr("src", `frontend/assets/img/${e.image}`);
          } else {
            $("#eventImage").attr("src", "frontend/assets/img/default.jpg");
          }

          // ✅ NEW: fetch availability so we can show SOLD OUT properly
          RestClient.get(
            `/events/${eventId}/availability`,
            function (a) {
              // Inject used_qty into the same event object,
              // so your existing setAvailabilityUI(e) works unchanged.
              if (a && a.used_qty !== undefined && a.used_qty !== null) {
                e.used_qty = a.used_qty;
              }
              // (Optional) if you ever want remaining directly:
              // e.remaining_qty = a.remaining_qty;

              setAvailabilityUI(e);
            },
            function () {
              // If availability endpoint fails, fallback to old behavior
              setAvailabilityUI(e);
            }
          );
        },
        function (xhr) {
          const msg = xhr.responseJSON?.message || "Failed to load event details.";
          if (window.toastr) toastr.error(msg);
          else console.error(msg);
          window.location.hash = "#main";
        }
      );
    });
  }

  // Expose so we can call it directly from the click handler too
  window.EventDetails = {
    load: loadEventDetails,
  };

  // Run when hash changes (SPApp navigation)
  window.addEventListener("hashchange", loadEventDetails);

  // Also try once on initial load (if page opened directly on #event)
  $(document).ready(loadEventDetails);
})();
