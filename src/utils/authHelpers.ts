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

export async function checkProjectAccess() {
  // Check if user has project assignment and enforce access restrictions
  try {
    const token = localStorage.getItem("token");
    if (!token) return;
    
    // Decode JWT to get user info (basic parsing, assuming JWT structure)
    const base64Url = token.split('.')[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
    
    const userInfo = JSON.parse(jsonPayload);
    const userID = userInfo.sub;
    
    // Make API call to check project assignment
    const response = await fetch('https://alex.polan.sk/control-center/users.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Authorization': token
      },
      body: new URLSearchParams({
        getUserAssignments: 'true'
      })
    });
    
    if (response.ok) {
      const data = await response.json();
      if (data.success) {
        const userAssignment = data.assignments.find(a => a.user_id == userID);
        
        if (userAssignment && userAssignment.project_link) {
          // User has project assignment - restrict access to everything except their project and profile
          const currentPath = location.pathname;
          const assignedProjectPath = `/project/${userAssignment.project_link}`;
          
          // Allow access to profile-related pages
          const allowedPaths = [
            '/my-account', '/my-account/',
            '/profile', '/profile/',
            '/my-profile', '/my-profile/',
            '/account', '/account/',
            '/logout', '/logout/',
            assignedProjectPath, `${assignedProjectPath}/`
          ];
          
          // Check if current path starts with assigned project path or is an allowed path
          const isProjectPath = currentPath.startsWith(assignedProjectPath);
          const isAllowedPath = allowedPaths.some(path => 
            currentPath.startsWith(path) || currentPath === path
          );
          
          if (!isProjectPath && !isAllowedPath) {
            // Redirect to assigned project for any other path
            location.href = assignedProjectPath;
            return;
          }
          
          // If user is on home page, redirect to their assigned project
          if (currentPath === '/' || currentPath === '/home' || currentPath === '/home/') {
            location.href = assignedProjectPath;
            return;
          }
        }
      }
    }
  } catch (error) {
    console.error('Error checking project access:', error);
  }
}