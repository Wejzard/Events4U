// frontend/services/events-owner-stats.js

let OwnerEventsStats = {
  loadMine: function () {
    RestClient.get(
      "/events/mine",
      function (res) {
        const list = res?.data ? res.data : res;

        const $tbody = $("#mySalesTbody");
        if (!$tbody.length) return;

        $tbody.empty();

        if (!list || !list.length) {
          $tbody.append(
            `<tr><td colspan="7" class="text-center text-muted">You haven't posted any events yet.</td></tr>`
          );
          return;
        }

        list.forEach((e) => {
          const dt = `${e.event_date || "-"} ${e.event_time || ""}`.trim();
          const limit = (e.ticket_limit ?? e.total_tickets ?? 0);

          $tbody.append(`
            <tr>
              <td>${e.title || "-"}</td>
              <td>${e.category || "-"}</td>
              <td>${dt}</td>
              <td>${limit}</td>
              <td>${e.sold_qty ?? 0}</td>
              <td>${e.reserved_qty ?? 0}</td>
              <td>${e.remaining_qty ?? 0}</td>
            </tr>
          `);
        });
      },
      function (xhr) {
        toastr.error(xhr.responseJSON?.message || "Failed to load your event stats.");
      }
    );
  },
};

function waitForEl(selector, cb, maxTries = 40) {
  let tries = 0;
  const t = setInterval(() => {
    tries++;
    if ($(selector).length) {
      clearInterval(t);
      cb();
    } else if (tries >= maxTries) {
      clearInterval(t);
      console.error(`OwnerEventsStats: DOM not ready for ${selector}`);
    }
  }, 100);
}

function loadSalesWhenSettingsVisible() {
  if (window.location.hash !== "#settings") return;
  waitForEl("#mySalesTbody", () => OwnerEventsStats.loadMine());
}

window.addEventListener("hashchange", loadSalesWhenSettingsVisible);

$(document).ready(function () {
  loadSalesWhenSettingsVisible();
});
