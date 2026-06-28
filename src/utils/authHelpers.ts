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
    location.href = "/projects";
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

export async function checkProjectAccess() {
  try {
    const token = localStorage.getItem("token");
    if (!token) return;

    const base64Url = token.split(".")[1];
    const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split("")
        .map(function (c) {
          return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
        })
        .join(""),
    );

    const userInfo = JSON.parse(jsonPayload);
    const userID = userInfo.sub;

    const response = await fetch("https://api.fringelo.com/users.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Authorization: token,
      },
      body: new URLSearchParams({
        getUserAssignments: "true",
      }),
    });

    if (response.ok) {
      const data = await response.json();
      if (data.success) {
        const userAssignment = data.assignments.find(
          (a) => a.user_id == userID,
        );

        if (userAssignment && userAssignment.project_link) {
          const currentPath = location.pathname;
          const assignedProjectPath = `/project/${userAssignment.project_link}`;
          const allowedPaths = [
            "/my-account",
            "/my-account/",
            "/profile",
            "/profile/",
            "/my-profile",
            "/my-profile/",
            "/account",
            "/account/",
            "/logout",
            "/logout/",
            assignedProjectPath,
            `${assignedProjectPath}/`,
          ];

          const isProjectPath = currentPath.startsWith(assignedProjectPath);
          const isAllowedPath = allowedPaths.some(
            (path) => currentPath.startsWith(path) || currentPath === path,
          );

          if (!isProjectPath && !isAllowedPath) {
            location.href = assignedProjectPath;
            return;
          }

          if (
            currentPath === "/" ||
            currentPath === "/projects" ||
            currentPath === "/projects/"
          ) {
            location.href = assignedProjectPath;
            return;
          }
        }
      }
    }
  } catch (error) {
    console.error("Error checking project access:", error);
  }
}
