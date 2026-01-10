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

    // IMPORTANT: your backend might not include payload.user, so don't require it yet
    return true;
  }

  // ✅ Only enforce auth on index.html (and /WebProject/)
  const p = window.location.pathname.toLowerCase();
  const onIndex = p.endsWith("/webproject/") || p.endsWith("/webproject/index.html");

  if (onIndex && !isTokenValid()) {
    localStorage.removeItem("user_token");
    // ✅ root-relative so it never becomes frontend/views/frontend/views/...
    window.location.replace("/HajrudinVejzovic/WebProject/frontend/views/login.html");
  }

  window.AuthGuard = { isTokenValid, parseJwt };
})();
