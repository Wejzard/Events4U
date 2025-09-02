let EventService = {
  loadAll: function (category = "POP", page = 1) {
    const url = `/events/category/${category}?page=${page}`;

    RestClient.get(url, function (events) {
      console.log("Loaded events:", events);
      let container = $("#events-container");
      container.empty();

      events.forEach(event => {
        const card = `
  <div class="col">
    <div class="card h-100">
      <img src="frontend/assets/img/${event.image}" class="card-img-top" alt="${event.title}">
      <div class="card-body text-center">
        <h5 class="card-title">${event.title}</h5>
        <p class="card-text">${event.location} – ${event.event_date}</p>
        <p class="card-text"><strong>${event.price} KM</strong></p>
        <a href="#event?id=${event.event_id}" class="btn btn-primary">Enter</a>
      </div>
    </div>
  </div>`;
        container.append(card);
      });

      EventService.renderPagination(page); // Simple placeholder
    }, function (xhr) {
      toastr.error(xhr.responseJSON?.message || "Could not load events.");
    });
  },

  renderPagination: function (currentPage = 1) {
    let pagination = $("#pagination");
    pagination.html(`
      <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="EventService.loadAll('POP', ${currentPage - 1})">Previous</a>
      </li>
      <li class="page-item active"><a class="page-link" href="#">${currentPage}</a></li>
      <li class="page-item">
        <a class="page-link" href="#" onclick="EventService.loadAll('POP', ${currentPage + 1})">Next</a>
      </li>
    `);
  }
};


/*let EventService = {
  loadAll: function(category = "POP") {
    const url = `/events/category/${category}`;

    RestClient.get(url, function(events) {
      console.log("Events loaded:", events);

      Utils.datatable("events-table", [
        { data: 'title', title: 'Title' },
        { data: 'date', title: 'Event Date' },
        { data: 'location', title: 'Location' },
        { data: 'price', title: 'Price' },
        { data: 'category', title: 'Category' }
      ], events, 10);
    }, function(xhr) {
      toastr.error(xhr.responseJSON?.message || "Could not load events.");
    });
  }
};*/