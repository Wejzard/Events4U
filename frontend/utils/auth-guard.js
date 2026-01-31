// frontend/utils/auth-guard.js
(function () {

  function parseJwt(token) {
    if (!token) return null;
    try {
      return JSON.parse(atob(token.split(".")[1]));
    } catch (e) {
      return null;
    }
  }

  function isTokenValid() {
    const token = localStorage.getItem("user_token");
    const payload = parseJwt(token);
    if (!payload) return false;
    if (payload.exp && Date.now() / 1000 > payload.exp) return false;
    return true;
  }

  function redirectToLogin() {
    localStorage.removeItem("user_token");

    // ✅ Use replace so Back button can’t revive a protected page
    // If you keep login as a standalone html page, keep this line:
    window.location.replace("/frontend/views/login.html");

    // If later you move login into SPApp (#login), switch to:
    // window.location.replace("/#login");
  }

  // ✅ PRODUCTION-SAFE: guard root entry
  function guardRootEntry() {
    const path = window.location.pathname.toLowerCase();
    const isRoot = path === "/" || path.endsWith("/index.html");

    if (isRoot && !isTokenValid()) {
      redirectToLogin();
    }
  }

  // ✅ Run on initial load
  guardRootEntry();

  // ✅ Run when page is restored from cache (Back/Forward button)
  window.addEventListener("pageshow", function () {
    guardRootEntry();
  });

  // ✅ Also run on hash navigation (SPApp changes views via #main, #profile, etc.)
  window.addEventListener("hashchange", function () {
    guardRootEntry();
  });

  window.AuthGuard = { isTokenValid, parseJwt };

})();

