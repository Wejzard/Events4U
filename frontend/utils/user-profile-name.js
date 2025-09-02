
  document.addEventListener("DOMContentLoaded", () => {
    const token = localStorage.getItem("user_token");
    const decoded = Utils.parseJwt(token);

    if (decoded && decoded.user && decoded.user.first_name && decoded.user.last_name) {
      const fullName = `${decoded.user.first_name} ${decoded.user.last_name}`;
      document.getElementById("userNameDisplay").innerText = fullName;
    }
  });
