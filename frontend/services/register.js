$(document).ready(function () {
  $("#registerForm").on("submit", function (e) {
    e.preventDefault();

    const $btn = $("#registerForm button[type='submit']");
    const firstName = $("#firstName").val().trim();
    const lastName = $("#lastName").val().trim();
    const email = $("#email").val().trim();
    const password = $("#password").val();
    const repeatPassword = $("#repeatPassword").val();

    // ---- Client-side validation (fast & consistent UX) ----
    if (firstName.length < 2) {
      toastr.error("First name must be at least 2 characters.");
      return;
    }

    if (lastName.length < 2) {
      toastr.error("Last name must be at least 2 characters.");
      return;
    }

    // Simple email check (backend is still the source of truth)
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      toastr.error("Please enter a valid email address.");
      return;
    }

    if (password.length < 6) {
      toastr.error("Password must be at least 6 characters.");
      return;
    }

    if (password !== repeatPassword) {
      toastr.error("Passwords do not match.");
      return;
    }

    // ---- Submit ----
    $btn.prop("disabled", true).text("Creating account...");

    const data = {
      first_name: firstName,
      last_name: lastName,
      email: email,
      password: password,
      repeat_password: repeatPassword
    };

    RestClient.post(
      "/auth/register",
      data,
      function () {
        toastr.success("Registration successful! You can now log in.");
        window.location.href = "login.html";
        $btn.prop("disabled", false).text("Register Account");
      },
      function (xhr) {
        // Backend fallback (safe handling)
        const backendMsg =
          xhr.responseJSON?.message ||
          xhr.responseJSON?.error ||
          xhr.responseText ||
          "";

        // Friendly fallback message
        let msg = "Registration failed. Please try again.";

        // If backend gave something useful, show a nicer version
        if (backendMsg.toLowerCase().includes("email already")) {
          msg = "That email is already registered. Try logging in.";
        } else if (backendMsg.toLowerCase().includes("invalid email")) {
          msg = "Please enter a valid email address.";
        } else if (backendMsg.toLowerCase().includes("password")) {
          msg = "Password must be at least 6 characters.";
        } else if (backendMsg.toLowerCase().includes("match")) {
          msg = "Passwords do not match.";
        } else if (backendMsg.trim().length > 0) {
          // If backend returned plain text, still show it (but not with ugly prefixes)
          msg = backendMsg.replace(/^message\s*:\s*/i, "").trim();
        }

        toastr.error(msg);
        $btn.prop("disabled", false).text("Register Account");
      }
    );
  });
});
