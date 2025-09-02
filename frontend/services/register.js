$(document).ready(function () {
  $("#registerForm").on("submit", function (e) {
    e.preventDefault();

    const data = {
      first_name: $("#firstName").val(),
      last_name: $("#lastName").val(),
      email: $("#email").val(),
      password: $("#password").val(),
      repeat_password: $("#repeatPassword").val()
    };
    
    RestClient.post("/auth/register", data, function () {
      toastr.success("Registration successful!");
      window.location.href = "../views/login.html";
    }, function (error) {
      toastr.error(error.responseJSON?.message || "Registration failed.");
    });
  });
});