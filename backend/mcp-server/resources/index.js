/**
 * MCP Resources
 * 
 * Resources provide read-only access to CMS data
 */

/**
 * Get available resources for a user
 */
export async function getResources(user, backendUrl) {
  const resources = [];
  
  try {
    // Fetch user's projects
    const response = await fetch(`${backendUrl}/projects.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({ getUserProjects: 'true' })
    });
    
    const data = await response.json();
    const projects = data.projects || [];
    
    // Add project resources
    for (const project of projects) {
      resources.push({
        uri: `cms://projects/${project.link || project.projectID}`,
        name: project.name,
        description: `Project: ${project.name}`,
        mimeType: 'application/json'
      });
      
      // Add project pages resource
      resources.push({
        uri: `cms://projects/${project.link || project.projectID}/pages`,
        name: `${project.name} - Pages`,
        description: `Pages in project ${project.name}`,
        mimeType: 'application/json'
      });
      
      // Add project files resource
      resources.push({
        uri: `cms://projects/${project.link || project.projectID}/files`,
        name: `${project.name} - Files`,
        description: `Files in project ${project.name}`,
        mimeType: 'application/json'
      });
    }
    
    // Add user profile resource
    resources.push({
      uri: 'cms://user/profile',
      name: 'User Profile',
      description: 'Current user profile information',
      mimeType: 'application/json'
    });
    
    // Add bookmarks resource
    resources.push({
      uri: 'cms://user/bookmarks',
      name: 'Bookmarks',
      description: 'User bookmarks',
      mimeType: 'application/json'
    });
    
    // Add templates resource
    resources.push({
      uri: 'cms://templates',
      name: 'Project Templates',
      description: 'Available project templates',
      mimeType: 'application/json'
    });
    
    // Add available APIs resource
    resources.push({
      uri: 'cms://apis/available',
      name: 'Available APIs',
      description: 'APIs available for subscription',
      mimeType: 'application/json'
    });
    
  } catch (error) {
    console.error('Error fetching resources:', error);
  }
  
  return resources;
}

/**
 * Read a specific resource
 */
export async function readResource(uri, user, backendUrl) {
  const parsed = parseResourceUri(uri);
  
  if (!parsed) {
    throw new Error(`Invalid resource URI: ${uri}`);
  }
  
  const { type, id, subResource } = parsed;
  
  try {
    let data;
    
    switch (type) {
      case 'projects':
        if (subResource === 'pages') {
          data = await fetchProjectPages(id, backendUrl);
        } else if (subResource === 'files') {
          data = await fetchProjectFiles(id, backendUrl);
        } else {
          data = await fetchProject(id, backendUrl);
        }
        break;
        
      case 'user':
        if (id === 'profile') {
          data = { user };
        } else if (id === 'bookmarks') {
          data = await fetchBookmarks(backendUrl);
        }
        break;
        
      case 'templates':
        data = await fetchTemplates(backendUrl);
        break;
        
      case 'apis':
        if (id === 'available') {
          data = await fetchAvailableApis(backendUrl);
        }
        break;
        
      default:
        throw new Error(`Unknown resource type: ${type}`);
    }
    
    return {
      contents: [{
        uri,
        mimeType: 'application/json',
        text: JSON.stringify(data, null, 2)
      }]
    };
    
  } catch (error) {
    throw new Error(`Error reading resource: ${error.message}`);
  }
}

/**
 * Parse resource URI
 */
function parseResourceUri(uri) {
  const match = uri.match(/^cms:\/\/([^/]+)(?:\/([^/]+))?(?:\/(.+))?$/);
  
  if (!match) return null;
  
  return {
    type: match[1],
    id: match[2],
    subResource: match[3]
  };
}

// ============================================
// Fetch Functions
// ============================================

async function fetchProject(projectLink, backendUrl) {
  const response = await fetch(`${backendUrl}/projects.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      getProjectByLink: 'true',
      project: projectLink
    })
  });
  return response.json();
}

async function fetchProjectPages(projectLink, backendUrl) {
  const response = await fetch(`${backendUrl}/web_pages.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      getPagesByProject: 'true',
      project: projectLink
    })
  });
  return response.json();
}

async function fetchProjectFiles(projectLink, backendUrl) {
  const response = await fetch(
    `${backendUrl}/file_api.php?project=${encodeURIComponent(projectLink)}&action=list&path=/`
  );
  return response.json();
}

async function fetchBookmarks(backendUrl) {
  const response = await fetch(`${backendUrl}/bookmarks.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ getBookmarks: 'true' })
  });
  return response.json();
}

async function fetchTemplates(backendUrl) {
  const response = await fetch(`${backendUrl}/project_templates.php?action=list`);
  return response.json();
}

async function fetchAvailableApis(backendUrl) {
  const response = await fetch(`${backendUrl}/apis.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ getAvailableApis: 'true' })
  });
  return response.json();
}
