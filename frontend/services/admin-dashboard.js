// frontend/services/admin-dashboard.js
(function () {
  // Prevent double init
  if (window.__adminDashboardInitDone) return;
  window.__adminDashboardInitDone = true;

  const BASE = Constants.PROJECT_BASE_URL;

  function token() {
    return localStorage.getItem("user_token");
  }

  function parsePossiblyDirtyJson(txt) {
    // handle BOM + whitespace + accidental newlines before JSON
    let s = String(txt ?? "");
    s = s.replace(/^\uFEFF/, ""); // remove UTF-8 BOM if present
    s = s.trim();

    // If there is still junk BEFORE the first "{" or "[", cut it off
    const firstObj = s.indexOf("{");
    const firstArr = s.indexOf("[");
    let start = -1;
    if (firstObj >= 0 && firstArr >= 0) start = Math.min(firstObj, firstArr);
    else start = Math.max(firstObj, firstArr);

    if (start > 0) s = s.slice(start);

    return JSON.parse(s);
  }

  function adminGetText(url, ok, fail) {
    $.ajax({
      url: BASE + url,
      type: "GET",
      dataType: "text", // IMPORTANT: always get text, then parse ourselves
      beforeSend: function (xhr) {
        const t = token();
        if (t) {
          xhr.setRequestHeader("Authorization", t);
          xhr.setRequestHeader("Authentication", t);
        }
      },
      success: function (txt) {
        try {
          const parsed = parsePossiblyDirtyJson(txt);
          ok(parsed);
        } catch (e) {
          if (fail) fail({ message: "Invalid JSON returned from server.", raw: txt, error: e });
          else console.error("Invalid JSON:", txt, e);
        }
      },
      error: function (xhr) {
        if (fail) fail(xhr);
        else console.error(xhr);
      },
    });
  }

  // ---------- Ensure we only run when admin dashboard DOM exists ----------
  function whenAdminDomReady(cb) {
    const maxTries = 40; // 4s
    let tries = 0;

    const timer = setInterval(function () {
      tries++;

      if ($("#eventsTbody").length || $("#ordersTbody").length || $("#paymentsTbody").length) {
        clearInterval(timer);
        cb();
        return;
      }

      if (tries >= maxTries) {
        clearInterval(timer);
      }
    }, 100);
  }

  // ---------------- Confirm modal helper ----------------
  let confirmCb = null;

  function openConfirm(title, body, onYes) {
    $("#confirmActionTitle").text(title || "Confirm");
    $("#confirmActionBody").text(body || "Are you sure?");
    confirmCb = typeof onYes === "function" ? onYes : null;

    const modalEl = document.getElementById("confirmActionModal");
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }

  $(document)
    .off("click.adminDash", "#confirmActionYesBtn")
    .on("click.adminDash", "#confirmActionYesBtn", function () {
      const modalEl = document.getElementById("confirmActionModal");
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.hide();

      if (confirmCb) confirmCb();
      confirmCb = null;
    });

  // ---------------- Events ----------------
  function renderEvents(events) {
    const tbody = $("#eventsTbody");
    if (!tbody.length) return;

    tbody.empty();

    if (!events || !events.length) {
      tbody.append('<tr><td colspan="9" class="text-muted text-center">No events found.</td></tr>');
      return;
    }

    const rows = events
      .map((e) => {
        const id = e.event_id ?? e.id ?? "";
        return `
          <tr>
            <td>${id}</td>
            <td>${e.title ?? ""}</td>
            <td>${e.category ?? ""}</td>
            <td>${e.event_date ?? ""}</td>
            <td>${e.event_time ?? ""}</td>
            <td>${e.location ?? ""}</td>
            <td>${e.price ?? ""}</td>
            <td>${e.ticket_limit ?? 0}</td>
            <td class="text-nowrap">    
              <button class="btn btn-sm btn-outline-danger admin-del-event" data-id="${id}">Delete</button>
            </td>
          </tr>
        `;
      })
      .join("");

    tbody.html(rows);
  }

  function loadAllEvents() {
    const tbody = $("#eventsTbody");
    if (!tbody.length) return;

    tbody.html('<tr><td colspan="9" class="text-muted text-center">Loading...</td></tr>');

    const pageSize = 50; // backend caps at 50 anyway
    const acc = [];

    function fetchPage(page) {
      adminGetText(
        `/events?page=${page}&page_size=${pageSize}`,
        function (res) {
          // Expected: { data: [...], total_pages: N, ... }
          const list = res && Array.isArray(res.data) ? res.data : Array.isArray(res) ? res : [];
          acc.push(...list);

          const totalPages = Number(res?.total_pages ?? 1);

          // safety stop if backend sends weird value
          if (page < totalPages && page < 50) {
            fetchPage(page + 1);
          } else {
            renderEvents(acc);
          }
        },
        function (err) {
          tbody.html('<tr><td colspan="9" class="text-danger text-center">Failed to load.</td></tr>');
          const msg =
            err?.responseJSON?.message ||
            err?.message ||
            err?.responseText ||
            "Failed to load events.";
          if (window.toastr) toastr.error(msg);
        }
      );
    }

    fetchPage(1);
  }

  // Delete Event
  $(document)
    .off("click.adminDash", ".admin-del-event")
    .on("click.adminDash", ".admin-del-event", function () {
      const id = $(this).data("id");
      openConfirm("Delete event?", `Are you sure you want to delete event #${id}?`, function () {
        RestClient.delete(
          `/events/${id}`,
          {},
          function () {
            if (window.toastr) toastr.success("Event deleted.");
            loadAllEvents();
          },
          function (xhr) {
            const msg = xhr?.responseJSON?.message || xhr?.responseText || "Delete failed.";
            if (window.toastr) toastr.error(msg);
          }
        );
      });
    });

  // ---------------- Orders ----------------
  function loadOrders() {
    const tbody = $("#ordersTbody");
    if (!tbody.length) return;

    tbody.html('<tr><td colspan="6" class="text-muted text-center">Loading...</td></tr>');

    // orders route usually returns clean JSON; keep RestClient as-is
    RestClient.get(
      "/orders",
      function (res) {
        const list = res?.data ? res.data : res;
        tbody.empty();

        if (!list || !list.length) {
          tbody.append('<tr><td colspan="6" class="text-muted text-center">No orders.</td></tr>');
          return;
        }

        list.forEach((o) => {
          tbody.append(`
            <tr>
              <td>${o.order_id ?? "-"}</td>
              <td>${o.user_id ?? "-"}</td>
              <td>${o.total_price ?? "-"}</td>
              <td>${o.status ?? "-"}</td>
              <td>${o.order_date ?? "-"}</td>
              <td>
                <button class="btn btn-sm btn-outline-danger admin-del-order" data-id="${o.order_id}">Delete</button>
              </td>
            </tr>
          `);
        });
      },
      function (xhr) {
        const msg = xhr?.responseJSON?.message || xhr?.responseText || "Failed to load orders.";
        if (window.toastr) toastr.error(msg);
        tbody.html('<tr><td colspan="6" class="text-muted text-center">Failed to load.</td></tr>');
      }
    );
  }

  $(document)
    .off("click.adminDash", ".admin-del-order")
    .on("click.adminDash", ".admin-del-order", function () {
      const id = $(this).data("id");
      openConfirm("Delete order?", `Delete order #${id}?`, function () {
        RestClient.delete(
          `/orders/${id}`,
          {},
          function () {
            if (window.toastr) toastr.success("Order deleted.");
            loadOrders();
          },
          function (xhr) {
            const msg = xhr?.responseJSON?.message || xhr?.responseText || "Delete failed.";
            if (window.toastr) toastr.error(msg);
          }
        );
      });
    });

  // ---------------- Payments ----------------
  function loadPayments() {
    const tbody = $("#paymentsTbody");
    if (!tbody.length) return;

    tbody.html('<tr><td colspan="5" class="text-muted text-center">Loading...</td></tr>');

    RestClient.get(
      "/payments",
      function (res) {
        const list = res?.data ? res.data : res;
        tbody.empty();

        if (!list || !list.length) {
          tbody.append('<tr><td colspan="5" class="text-muted text-center">No payments.</td></tr>');
          return;
        }

        list.forEach((p) => {
          tbody.append(`
            <tr>
              <td>${p.payment_id ?? "-"}</td>
              <td>${p.order_id ?? "-"}</td>
              <td>${p.currency ?? "-"}</td>
              <td>${p.amount ?? "-"}</td>
              <td>
                <button class="btn btn-sm btn-outline-danger admin-del-payment" data-id="${p.payment_id}">Delete</button>
              </td>
            </tr>
          `);
        });
      },
      function (xhr) {
        const msg = xhr?.responseJSON?.message || xhr?.responseText || "Failed to load payments.";
        if (window.toastr) toastr.error(msg);
        tbody.html('<tr><td colspan="5" class="text-muted text-center">Failed to load.</td></tr>');
      }
    );
  }

  $(document)
    .off("click.adminDash", ".admin-del-payment")
    .on("click.adminDash", ".admin-del-payment", function () {
      const id = $(this).data("id");
      openConfirm("Delete payment?", `Delete payment #${id}?`, function () {
        RestClient.delete(
          `/payments/${id}`,
          {},
          function () {
            if (window.toastr) toastr.success("Payment deleted.");
            loadPayments();
          },
          function (xhr) {
            const msg = xhr?.responseJSON?.message || xhr?.responseText || "Delete failed.";
            if (window.toastr) toastr.error(msg);
          }
        );
      });
    });

  // ---------------- Buttons + initial load ----------------
  function bindButtonsOnce() {
    $(document)
      .off("click.adminDash", "#reloadEventsBtn")
      .on("click.adminDash", "#reloadEventsBtn", function (e) {
        e.preventDefault();
        loadAllEvents();
      });

    $(document)
      .off("click.adminDash", "#reloadOrdersBtn")
      .on("click.adminDash", "#reloadOrdersBtn", function (e) {
        e.preventDefault();
        loadOrders();
      });

    $(document)
      .off("click.adminDash", "#reloadPaymentsBtn")
      .on("click.adminDash", "#reloadPaymentsBtn", function (e) {
        e.preventDefault();
        loadPayments();
      });
  }

  whenAdminDomReady(function () {
    bindButtonsOnce();
    loadAllEvents();
    loadOrders();
    loadPayments();
  });
})();
