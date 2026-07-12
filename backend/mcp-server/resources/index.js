export async function getResources(user, backendUrl, token) {
  const resources = [];

  try {
    const response = await fetch(`${backendUrl}/v2/projects`, {
      method: 'GET',
      headers: {
        'Authorization': token
      }
    });

    const data = await response.json();
    const projects = Array.isArray(data) ? data : (data.projects || []);

    for (const project of projects) {
      resources.push({
        uri: `cms://projects/${project.link || project.projectID}`,
        name: project.name,
        description: `Project: ${project.name}`,
        mimeType: 'application/json'
      });

      resources.push({
        uri: `cms://projects/${project.link || project.projectID}/files`,
        name: `${project.name} - Files`,
        description: `Files in project ${project.name}`,
        mimeType: 'application/json'
      });
    }

    resources.push({
      uri: 'cms://user/profile',
      name: 'User Profile',
      description: 'Current user profile information',
      mimeType: 'application/json'
    });

    resources.push({
      uri: 'cms://user/bookmarks',
      name: 'Bookmarks',
      description: 'User bookmarks',
      mimeType: 'application/json'
    });

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

export async function readResource(uri, user, backendUrl, token) {
  const parsed = parseResourceUri(uri);

  if (!parsed) {
    throw new Error(`Invalid resource URI: ${uri}`);
  }

  const { type, id, subResource } = parsed;

  try {
    let data;

    switch (type) {
      case 'projects':
        if (subResource === 'files') {
          data = await fetchProjectFiles(id, backendUrl, token);
        } else {
          data = await fetchProject(id, backendUrl, token);
        }
        break;

      case 'user':
        if (id === 'profile') {
          data = { user };
        } else if (id === 'bookmarks') {
          data = await fetchBookmarks(backendUrl);
        }
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

function parseResourceUri(uri) {
  const match = uri.match(/^cms:\/\/([^/]+)(?:\/([^/]+))?(?:\/(.+))?$/);

  if (!match) return null;

  return {
    type: match[1],
    id: match[2],
    subResource: match[3]
  };
}

async function fetchProject(projectLink, backendUrl, token) {
  const response = await fetch(`${backendUrl}/v2/projects/${encodeURIComponent(projectLink)}`, {
    method: 'GET',
    headers: { 'Authorization': token }
  });
  return response.json();
}

async function fetchProjectFiles(projectLink, backendUrl, token) {
  const response = await fetch(
    `${backendUrl}/v2/codespaces/files?project=${encodeURIComponent(projectLink)}&codespace=main&action=list`,
    { method: 'GET', headers: { 'Authorization': token } }
  );
  return response.json();
}

async function fetchBookmarks(backendUrl) {
  const response = await fetch(`${backendUrl}/v2/bookmarks`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    //body: new URLSearchParams({ getBookmarks: 'true' })
  });
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

