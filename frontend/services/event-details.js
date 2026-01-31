// frontend/services/event-details.js
(function () {
  // Wait until the event view DOM exists, but:
  // - only while we're on #event
  // - stop silently if user navigates away
  // - no console spam
  function renderWhenDomReady(cb) {
    const maxFrames = 180; // ~3 seconds at 60fps
    let frames = 0;

    function tick() {
      // If user navigated away, stop (no error)
      if (window.location.hash !== "#event") return;

      frames++;

      if ($("#eventTitle").length > 0) {
        cb();
        return;
      }

      if (frames >= maxFrames) {
        // Still on #event but DOM not there -> warn once (not error)
        console.warn("Event details DOM not ready yet (#eventTitle missing). Check event.html IDs.");
        return;
      }

      requestAnimationFrame(tick);
    }

    requestAnimationFrame(tick);
  }

  function setAvailabilityUI(e) {
    const $avail = $("#eventAvailability");
    const $reserve = $("#reserveBtn");
    const $buy = $("#buyBtn");

    if (!$avail.length) return;

    const limit = parseInt(e?.ticket_limit ?? 0, 10);

    if (!limit || limit <= 0) {
      $avail.text("Unlimited");
      if ($reserve.length) $reserve.prop("disabled", false);
      if ($buy.length) $buy.removeClass("disabled").removeAttr("aria-disabled");
      return;
    }

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
      $avail.text("Limited");
      if ($reserve.length) $reserve.prop("disabled", false);
      if ($buy.length) $buy.removeClass("disabled").removeAttr("aria-disabled");
    }
  }

  // Prevent duplicate loads firing too often (hashchange + document.ready)
  let isLoading = false;

  function loadEventDetails() {
    // Only act when we are on #event
    if (window.location.hash !== "#event") return;

    const eventId = localStorage.getItem("selected_event_id");
    if (!eventId) {
      window.location.hash = "#main";
      return;
    }

    // Avoid overlapping calls if hashchange fires twice quickly
    if (isLoading) return;
    isLoading = true;

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

          $("#eventPrice").text(`${e.price ?? "-"} €`);

          if (e.image) {
            $("#eventImage").attr("src", `/frontend/assets/img/${e.image}`);
          } else {
            $("#eventImage").attr("src", "/frontend/assets/img/default.jpg");
          }

          // Fetch availability (unchanged behavior)
          RestClient.get(
            `/events/${eventId}/availability`,
            function (a) {
              if (a && a.used_qty !== undefined && a.used_qty !== null) {
                e.used_qty = a.used_qty;
              }
              setAvailabilityUI(e);
              isLoading = false;
            },
            function () {
              setAvailabilityUI(e);
              isLoading = false;
            }
          );
        },
        function (xhr) {
          const msg = xhr.responseJSON?.message || "Failed to load event details.";
          if (window.toastr) toastr.error(msg);
          else console.error(msg);

          isLoading = false;
          window.location.hash = "#main";
        }
      );
    });
  }

  // Keep your API exactly the same
  window.EventDetails = {
    load: loadEventDetails,
  };

  // Run on navigation changes
  window.addEventListener("hashchange", loadEventDetails);

  // Initial load (only does anything if hash is #event)
  $(document).ready(loadEventDetails);
})();
