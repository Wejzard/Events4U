let EventService = {
  currentCategory: "ALL",

  loadAll: function (category = "ALL", page = 1) {
    this.currentCategory = category;

    const url =
      (!category || category === "ALL")
        ? `/events?page=${page}&limit=9`
        : `/events/category/${encodeURIComponent(category)}?page=${page}`;

    RestClient.get(
      url,
      function (events) {
        // If backend returns {data: [...]} for /events
        const list = events?.data ? events.data : events;

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
    <div class="card h-100 event-card">
      <img src="${img}" class="card-img-top event-img" alt="${title}">
      <div class="card-body text-center">
        <h5 class="card-title">${title}</h5>
        <p class="card-text">${location} – ${date}</p>
        <p class="card-text"><strong>${price} €</strong></p>
        <button type="button"
                class="btn btn-primary event-open"
                data-id="${id}">
          Enter
        </button>
      </div>
    </div>
  </div>`;
          container.append(card);
        });

        // ✅ Pagination only for ALL
        if (EventService.currentCategory === "ALL") {
          $("#pagination").show();
          EventService.renderPagination(page);
        } else {
          $("#pagination").hide();
        }
      },
      function (xhr) {
        const msg =
          xhr.responseJSON?.message ||
          xhr.responseJSON?.error ||
          xhr.responseText ||
          "Could not load events.";
        if (window.toastr) toastr.error(msg);
        else console.error(msg);
      }
    );
  },

  renderPagination: function (currentPage = 1) {
    let pagination = $("#pagination");
    const cat = EventService.currentCategory;

    // ✅ If not ALL, do nothing (extra safety)
    if (cat !== "ALL") {
      pagination.hide();
      return;
    }

    pagination.html(`
      <li class="page-item ${currentPage === 1 ? "disabled" : ""}">
        <a class="page-link page-prev" href="#">Previous</a>
      </li>
      <li class="page-item active"><a class="page-link" href="#">${currentPage}</a></li>
      <li class="page-item">
        <a class="page-link page-next" href="#">Next</a>
      </li>
    `);

    $(".page-prev")
      .off("click")
      .on("click", function (e) {
        e.preventDefault();
        if (currentPage > 1) EventService.loadAll(cat, currentPage - 1);
      });

    $(".page-next")
      .off("click")
      .on("click", function (e) {
        e.preventDefault();
        EventService.loadAll(cat, currentPage + 1);
      });
  },
};

// ✅ One handler for dynamically created buttons
$(document).on("click", ".event-open", function (e) {
  e.preventDefault();
  const eventId = $(this).data("id");

  if (!eventId) {
    if (window.toastr) toastr.error("Cannot open event details (missing event id).");
    else console.error("Cannot open event details (missing event id).");
    return;
  }

  localStorage.setItem("selected_event_id", eventId);

  // Navigate to event view
  window.location.hash = "#event";

  // ✅ Trigger details load after SPApp injects event.html
  setTimeout(() => {
    if (window.EventDetails && typeof window.EventDetails.load === "function") {
      window.EventDetails.load();
    } else {
      console.warn("EventDetails.load not available yet. Make sure event-details.js is updated and included.");
    }
  }, 150);
});
