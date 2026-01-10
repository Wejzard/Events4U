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
        // Your backend returns: { data: { ... , token: "..." } }
        const token = response?.data?.token;

        if (token) {
          localStorage.setItem("user_token", token);
          toastr.success("Login successful!");
          window.location.href = "/HajrudinVejzovic/WebProject/index.html";
        } else {
          toastr.error("Login failed: token not returned.");
        }

        $btn.prop("disabled", false).text("Login");
      },
      function (xhr) {
        let msg = "Login failed. Please check your email and password.";
        toastr.error(msg);
        $btn.prop("disabled", false).text("Login");
      }
    );
  });
});

