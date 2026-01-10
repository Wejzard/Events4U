let RestClient = {
  _tryParseJsonResponse: function (jqXHR) {
    if (!jqXHR || jqXHR.status !== 200 || !jqXHR.responseText) return null;

    // ✅ Trim + remove UTF-8 BOM if present
    let txt = String(jqXHR.responseText);
    txt = txt.replace(/^\uFEFF/, "").trim();

    try {
      return JSON.parse(txt);
    } catch (e) {
      return null;
    }
  },

  get: function (url, callback, error_callback) {
    $.ajax({
      url: Constants.PROJECT_BASE_URL + url,
      type: "GET",
      beforeSend: function (xhr) {
        const token = localStorage.getItem("user_token");
        if (token) {
          xhr.setRequestHeader("Authorization", token);
          xhr.setRequestHeader("Authentication", token);
        }
      },
      success: function (response) {
        if (callback) callback(response);
      },
      error: function (jqXHR, textStatus) {
        // ✅ Robust rescue: if status is 200 but jQuery routed to error, treat as success
        const parsed = RestClient._tryParseJsonResponse(jqXHR);
        if (parsed !== null) {
          if (callback) return callback(parsed);
        }

        if (error_callback) {
          error_callback(jqXHR);
        } else {
          const msg =
            jqXHR.responseJSON?.message ||
            jqXHR.responseJSON?.error ||
            jqXHR.responseText ||
            "Request failed.";
          if (window.toastr) toastr.error(msg);
          else console.error(msg);
        }
      },
    });
  },

  request: function (url, method, data, callback, error_callback) {
    $.ajax({
      url: Constants.PROJECT_BASE_URL + url,
      type: method,
      beforeSend: function (xhr) {
        const token = localStorage.getItem("user_token");
        if (token) {
          xhr.setRequestHeader("Authorization", token);
          xhr.setRequestHeader("Authentication", token);
        }
      },
      data: data,
    })
      .done(function (response) {
        if (callback) callback(response);
      })
      .fail(function (jqXHR, textStatus) {
        // ✅ Same rescue for POST/PUT/PATCH/DELETE if it ever happens
        const parsed = RestClient._tryParseJsonResponse(jqXHR);
        if (parsed !== null) {
          if (callback) return callback(parsed);
        }

        if (error_callback) {
          error_callback(jqXHR);
        } else {
          const msg =
            jqXHR.responseJSON?.message ||
            jqXHR.responseJSON?.error ||
            jqXHR.responseText ||
            "Request failed.";
          if (window.toastr) toastr.error(msg);
          else console.error(msg);
        }
      });
  },

  post: function (url, data, callback, error_callback) {
    RestClient.request(url, "POST", data, callback, error_callback);
  },
  delete: function (url, data, callback, error_callback) {
    RestClient.request(url, "DELETE", data, callback, error_callback);
  },
  patch: function (url, data, callback, error_callback) {
    RestClient.request(url, "PATCH", data, callback, error_callback);
  },
  put: function (url, data, callback, error_callback) {
    RestClient.request(url, "PUT", data, callback, error_callback);
  },
};
