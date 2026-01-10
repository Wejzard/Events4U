$(document).ready(function () {
  // Logout from modal or navbar
  $(document).on("click", ".logout-link", function (e) {
    e.preventDefault();
    localStorage.removeItem("user_token");
    window.location.href = "/HajrudinVejzovic/WebProject/index.html"; // or /index.html#login if using SPApp
  });
});