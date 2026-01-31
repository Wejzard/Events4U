// frontend/services/logout.js
$(document).ready(function () {
  $(document).on("click", ".logout-link", function (e) {
    e.preventDefault();

    // 1) Clear token
    localStorage.removeItem("user_token");

    // (optional) clear anything else you might store
    // localStorage.removeItem("selected_event");
    // localStorage.removeItem("user_profile");

    // 2) Kill current history entry so Back doesn't return to a "protected" view
    // Replace with your real entry URL (root is fine for production)
    window.location.replace("/#login");
  });
});
