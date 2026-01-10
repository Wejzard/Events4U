// frontend/services/search.js
(function () {
  function renderEvents(list) {
    let container = $("#events-container");
    container.empty();

    (list || []).forEach((event) => {
      const id = event.event_id ?? event.id;

      const img = event.image
        ? `frontend/assets/img/${event.image}`
        : "frontend/assets/img/default.jpg";

      const title = event.title ?? "Untitled event";
      const location = event.location ?? "-";
      const date = event.event_date ?? "-";
      const price = event.price ?? "-";

      const card = `
        <div class="col">
          <div class="card h-100">
            <img src="${img}" class="card-img-top" alt="${title}">
            <div class="card-body text-center">
              <h5 class="card-title">${title}</h5>
              <p class="card-text">${location} – ${date}</p>
              <p class="card-text"><strong>${price} KM</strong></p>
              <button type="button" class="btn btn-primary event-open" data-id="${id}">
                Enter
              </button>
            </div>
          </div>
        </div>`;
      container.append(card);
    });
  }

  function doSearch(term) {
    const q = (term || "").trim();

    // Empty search => reset to ALL events
    if (!q) {
      $("#pagination").show();
      if (window.EventService) EventService.loadAll("ALL", 1);
      return;
    }

    // During search: hide pagination (since search endpoint not paginated)
    $("#pagination").hide();

    RestClient.get(
      `/events/search/${encodeURIComponent(q)}`,
      function (res) {
        const list = res?.data ? res.data : res;

        // Optional: if nothing found, show info toast
        if (!list || list.length === 0) {
          if (window.toastr) toastr.info("No events found.");
          renderEvents([]);
          return;
        }

        renderEvents(list);
      },
      function (xhr) {
        const msg =
          xhr.responseJSON?.message ||
          xhr.responseJSON?.error ||
          xhr.responseText ||
          "Search failed.";
        if (window.toastr) toastr.error(msg);
        else console.error(msg);
      }
    );
  }

  function bindSearchUI() {
    const $form = $("#eventSearchForm");
    const $input = $("#eventSearchInput");

    if ($form.length === 0 || $input.length === 0) {
      console.warn("Search UI not found. Did you add IDs to the topbar search?");
      return;
    }

    // Submit (button click or Enter)
    $form.off("submit").on("submit", function (e) {
      e.preventDefault();
      doSearch($input.val());
      window.location.hash = "#main"; // make sure user is on main view to see results
    });

    // Optional: ESC clears + resets
    $input.off("keydown").on("keydown", function (e) {
      if (e.key === "Escape") {
        $input.val("");
        doSearch("");
      }
    });
  }

  // Bind after page load
  $(document).ready(bindSearchUI);

  // Re-bind on hash navigation too (SPApp sometimes re-renders header in some setups)
  window.addEventListener("hashchange", bindSearchUI);

  // Expose for debugging (optional)
  window.EventSearch = { doSearch };
})();
