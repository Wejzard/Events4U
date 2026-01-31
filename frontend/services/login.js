// frontend/services/login.js
(function () {

  function clearLoginForm() {
    const form = document.getElementById("loginForm");
    const email = document.getElementById("email");
    const pass = document.getElementById("password");

    if (form) form.reset();     // clears browser-restored values
    if (email) email.value = ""; // hard clear
    if (pass) pass.value = "";   // hard clear
  }

  // ✅ Clear on normal load
  document.addEventListener("DOMContentLoaded", clearLoginForm);

  // ✅ Clear when coming back via Back/Forward cache (bfcache)
  window.addEventListener("pageshow", function () {
    clearLoginForm();
  });

  $(document).ready(function () {
    $("#loginForm").on("submit", function (e) {
      e.preventDefault();

      const $btn = $("#loginForm button[type='submit']");
      $btn.prop("disabled", true).text("Logging in...");

      const credentials = {
        email: $("#email").val().trim(),
        password: $("#password").val()
      };

      RestClient.post(
        "/auth/login",
        credentials,
        function (response) {
          const token = response?.data?.token;

          if (token) {
            localStorage.setItem("user_token", token);
            toastr.success("Login successful!");

            // ✅ replace() prevents Back button returning to the login form state
            window.location.replace("/");
          } else {
            toastr.error("Login failed: token not returned.");
          }

          $btn.prop("disabled", false).text("Login");
        },
        function (xhr) {
          toastr.error("Login failed. Please check your email and password.");
          $btn.prop("disabled", false).text("Login");
        }
      );
    });
  });

})();
