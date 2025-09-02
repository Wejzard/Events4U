$(document).ready(function () {
  $("#loginForm").on("submit", function (e) {
    e.preventDefault();

    const credentials = {
      email: $("#email").val(),
      password: $("#password").val()
    };

    RestClient.post("/auth/login", credentials, function (response) {
      const token = response.data.token;
      if (token) {
        localStorage.setItem("user_token", token);
        toastr.success("Login successful!");
        window.location.href = "/HajrudinVejzovic/WebProject/index.html";
      } else {
        toastr.error("No token returned");
      }
    }, function (xhr) {
      toastr.error(xhr.responseJSON?.message || "Login failed.");
    });
  });
});
