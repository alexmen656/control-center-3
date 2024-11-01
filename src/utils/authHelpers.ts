export function checkPendingVerification(userData) {
  const allowedPaths2 = ["/pending_verification", "/pending_verification/"];
  if (
    userData.accountStatus == "pending_verification" &&
    !allowedPaths2.includes(location.pathname)
  ) {
    location.href = allowedPaths2[0];
  } else if (
    userData.accountStatus != "pending_verification" &&
    (location.pathname == allowedPaths2[0] ||
      location.pathname == allowedPaths2[1])
  ) {
    location.href = "/home";
  }
 }

  export function checkLoginStatus() {
  const allowedPaths = [
    "/login",
    "/login/verification/",
    "/login/",
    "/login/verification",
    "/signup",
    "/signup/",
  ];
  if (
    !localStorage.getItem("token") &&
    !allowedPaths.includes(location.pathname)
  ) {
    location.href = "/login";
  }
}