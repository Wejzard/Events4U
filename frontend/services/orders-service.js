// frontend/services/orders-service.js

let OrdersService = {
  reserveNow: function () {
    const eventId = localStorage.getItem("selected_event_id");
    const qty = parseInt($("#ticketQuantity").val(), 10) || 1;

    if (!eventId) return toastr.error("No event selected.");

    RestClient.post(
      "/orders",
      { event_id: eventId, quantity: qty, action: "RESERVE", currency: "KM" },
      function () {
        toastr.success("Reservation created!");
        window.location.hash = "#settings";
        setTimeout(() => OrdersService.loadMine(), 200);
      },
      function (xhr) {
        toastr.error(xhr.responseJSON?.message || "Reservation failed.");
      }
    );
  },

  prepareBuy: function () {
    const eventId = localStorage.getItem("selected_event_id");
    const qty = parseInt($("#ticketQuantity").val(), 10) || 1;

    if (!eventId) {
      toastr.error("No event selected.");
      return;
    }

    localStorage.setItem("pending_buy_event_id", String(eventId));
    localStorage.setItem("pending_buy_qty", String(qty));
    localStorage.setItem("pending_buy_currency", "KM");
  },

  payNow: function () {
    const eventId = localStorage.getItem("pending_buy_event_id");
    const qty = parseInt(localStorage.getItem("pending_buy_qty"), 10) || 1;
    const currency = localStorage.getItem("pending_buy_currency") || "KM";

    if (!eventId) {
      toastr.error("No pending purchase found.");
      window.location.hash = "#main";
      return;
    }

    RestClient.post(
      "/orders",
      { event_id: eventId, quantity: qty, action: "BUY", currency },
      function () {
        toastr.success("Payment successful! Ticket purchased.");

        localStorage.removeItem("pending_buy_event_id");
        localStorage.removeItem("pending_buy_qty");
        localStorage.removeItem("pending_buy_currency");

        window.location.hash = "#settings";
        setTimeout(() => OrdersService.loadMine(), 200);
      },
      function (xhr) {
        toastr.error(xhr.responseJSON?.message || "Payment failed.");
      }
    );
  },

  cancelReservation: function (orderId) {
    if (!orderId) return;

    const $btn = $("#confirmCancelReservationBtn");
    $btn.prop("disabled", true).text("Cancelling...");

    RestClient.delete(
      `/orders/${orderId}/cancel`,
      {},
      function (res) {
        $("#cancelReservationModal").modal("hide");
        toastr.success(res?.message || "Reservation cancelled.");
        OrdersService.loadMine();
      },
      function (xhr) {
        toastr.error(xhr.responseJSON?.message || "Cancel failed.");
      }
    );

    setTimeout(() => {
      $btn.prop("disabled", false).text("Yes, cancel");
    }, 800);
  },

  loadMine: function () {
    RestClient.get(
      "/orders/me",
      function (rows) {
        const list = rows?.data ? rows.data : rows;
        const $tbody = $("#myOrdersTbody");
        if (!$tbody.length) return;

        $tbody.empty();

        if (!list || !list.length) {
          $tbody.append(`<tr><td colspan="8" class="text-center text-muted">No orders yet.</td></tr>`);
          return;
        }

        list.forEach((o) => {
          const dt = `${o.event_date || "-"} ${o.event_time || ""}`.trim();
          const status = (o.status || "").toLowerCase();

          let type = "-";
          if (status === "paid") type = "BUY";
          else if (status === "pending") type = "RESERVE";
          else if (status === "cancelled") type = "CANCELLED";

          let actionCell = "-";
          if (status === "pending") {
            actionCell = `<button class="btn btn-sm btn-danger cancel-res-btn" data-id="${o.order_id}">Cancel</button>`;
          } else if (status === "paid") {
            actionCell = `<span class="badge badge-success">paid</span>`;
          } else if (status === "cancelled") {
            actionCell = `<span class="badge badge-secondary">cancelled</span>`;
          } else {
            actionCell = `<span class="badge badge-secondary">${status || "-"}</span>`;
          }

          $tbody.append(`
            <tr>
              <td>#${o.order_id}</td>
              <td>${o.title || "-"}</td>
              <td>${o.category || "-"}</td>
              <td>${dt}</td>
              <td>${o.quantity ?? 1}</td>
              <td>${type}</td>
              <td>${o.total_price ?? "-"} KM</td>
              <td>${actionCell}</td>
            </tr>
          `);
        });
      },
      function (xhr) {
        toastr.error(xhr?.responseJSON?.message || "Failed to load your orders.");
      }
    );
  },
};

// -------------------- DOM + view lifecycle helpers --------------------

function waitForEl(selector, cb, maxTries = 40) {
  let tries = 0;
  const t = setInterval(() => {
    tries++;
    if ($(selector).length) {
      clearInterval(t);
      cb();
    } else if (tries >= maxTries) {
      clearInterval(t);
      console.error(`OrdersService: DOM not ready for ${selector}`);
    }
  }, 100);
}

function loadOrdersWhenSettingsVisible() {
  if (window.location.hash !== "#settings") return;
  waitForEl("#myOrdersTbody", () => OrdersService.loadMine());
}

// Reserve button in event view
$(document).on("click", "#reserveBtn", function () {
  OrdersService.reserveNow();
});

// Buy button (store pending data before going to credit-card view)
$(document).on("click", "#buyBtn", function () {
  OrdersService.prepareBuy();
});

// Pay form inside credit-card view
$(document).on("submit", "#paymentForm", function (e) {
  e.preventDefault();
  OrdersService.payNow();
});

// Open cancel modal
$(document).on("click", ".cancel-res-btn", function () {
  const id = $(this).data("id");
  $("#cancelOrderId").val(id);
  $("#cancelReservationModal").modal("show");
});

// Confirm cancel inside modal
$(document).on("click", "#confirmCancelReservationBtn", function () {
  const id = $("#cancelOrderId").val();
  OrdersService.cancelReservation(id);
});

// ✅ Load when user opens settings (navigation)
window.addEventListener("hashchange", loadOrdersWhenSettingsVisible);

// ✅ Also if user refreshes while already on #settings
$(document).ready(loadOrdersWhenSettingsVisible);
