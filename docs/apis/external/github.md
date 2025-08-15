---
sidebar_position: 3
---

# GitHub API Integration

The GitHub SDK provides comprehensive access to GitHub's REST API for repository management, code operations, and collaboration features.

## 🔑 API Key Setup

### Getting Your Access Token
1. Go to [GitHub Settings > Developer settings > Personal access tokens](https://github.com/settings/tokens)
2. Click **"Generate new token"** → **"Generate new token (classic)"**
3. Give your token a descriptive name
4. Select scopes based on your needs:
   - `repo` - Full repository access
   - `public_repo` - Public repository access only
   - `user` - User profile access
   - `gist` - Gist access
5. Click **"Generate token"**
6. Copy the token (starts with `ghp_`)

### Environment Setup
Add your token to your environment variables:

```bash
GITHUB_TOKEN=ghp_your-actual-token-here
```

## 📖 SDK Usage

```javascript
import githubSDK from './backend/apis_sdk/githubSDK.js';
```

## 🚀 Available Methods

### User Information

```javascript
// Get authenticated user
const user = await githubSDK.getUser();
console.log('Username:', user.login);

// Get specific user
const otherUser = await githubSDK.getUser('octocat');
console.log('User info:', otherUser);
```

### Repository Management

```javascript
// List user repositories
const repos = await githubSDK.getUserRepos(null, {
  type: 'owner',
  sort: 'updated',
  per_page: 10
});

// Get specific repository
const repo = await githubSDK.getRepo('owner', 'repository-name');
console.log('Repository:', repo);

// Create new repository
const newRepo = await githubSDK.createRepo('my-new-project', {
  description: 'A fantastic new project',
  private: false,
  auto_init: true,
  gitignore_template: 'Node'
});
```

### File Operations

```javascript
// Get repository contents
const contents = await githubSDK.getRepoContents('owner', 'repo', 'path/to/file.js');

// Create or update file
const result = await githubSDK.createOrUpdateFile(
  'owner',
  'repo',
  'path/to/new-file.js',
  'console.log("Hello World!");',
  'Add new JavaScript file',
  {
    branch: 'main'
  }
);

// Delete file
await githubSDK.deleteFile(
  'owner',
  'repo',
  'path/to/file.js',
  'Remove old file',
  'file-sha-hash'
);
```

### Branch Management

```javascript
// Get all branches
const branches = await githubSDK.getBranches('owner', 'repo');

// Create new branch
const newBranch = await githubSDK.createBranch('owner', 'repo', 'feature-branch', 'main');
```

### Commit History

```javascript
// Get commits
const commits = await githubSDK.getCommits('owner', 'repo', {
  sha: 'main',
  per_page: 20,
  author: 'username'
});

commits.forEach(commit => {
  console.log(`${commit.sha}: ${commit.commit.message}`);
});
```

### Issues Management

```javascript
// Get issues
const issues = await githubSDK.getIssues('owner', 'repo', {
  state: 'open',
  labels: 'bug,enhancement'
});

// Create new issue
const newIssue = await githubSDK.createIssue(
  'owner',
  'repo',
  'Bug: Something is broken',
  'Detailed description of the bug...',
  {
    labels: ['bug', 'priority-high'],
    assignees: ['username']
  }
);
```

### Repository Search

```javascript
// Search repositories
const searchResults = await githubSDK.searchRepositories('react', {
  sort: 'stars',
  order: 'desc',
  per_page: 10
});

searchResults.items.forEach(repo => {
  console.log(`${repo.full_name} - ${repo.stargazers_count} stars`);
});
```

## 🎛️ Configuration Options

### Repository Options
- `type`: `all`, `owner`, `public`, `private`, `member`
- `sort`: `created`, `updated`, `pushed`, `full_name`
- `direction`: `asc`, `desc`

### Search Options
- `sort`: `stars`, `forks`, `help-wanted-issues`, `updated`
- `order`: `desc`, `asc`

### File Content
All file content must be Base64 encoded when creating/updating files:

```javascript
const content = btoa('Your file content here'); // Base64 encode
```

## 📊 Rate Limits

GitHub API rate limits:
- **Authenticated requests**: 5,000 per hour
- **Unauthenticated requests**: 60 per hour
- **Search API**: 30 requests per minute

## ⚠️ Error Handling

```javascript
try {
  const repo = await githubSDK.getRepo('owner', 'repo');
  console.log(repo);
} catch (error) {
  if (error.message.includes('404')) {
    console.log('Repository not found');
  } else if (error.message.includes('403')) {
    console.log('Access denied or rate limited');
  } else {
    console.log('Error:', error.message);
  }
}
```

## 🔐 Token Scopes

Different operations require different scopes:

- **`public_repo`**: Access public repositories
- **`repo`**: Full repository access (including private)
- **`repo:status`**: Access commit status
- **`repo_deployment`**: Access deployment status
- **`user`**: Read user profile data
- **`user:email`**: Access user email addresses
- **`user:follow`**: Follow/unfollow users
- **`admin:org`**: Organization administration
- **`gist`**: Create/edit/delete gists

## 🔍 Advanced Features

### Working with Large Files
For files larger than 1MB, use the Git Data API:

```javascript
// This would require additional implementation
// for blob creation and tree manipulation
```

### Webhooks
GitHub webhooks require a server endpoint. Consider using GitHub Actions or external webhook services.

### GraphQL API
For complex queries, consider using GitHub's GraphQL API v4 alongside this REST API.

## 🔗 Useful Links

- [GitHub Personal Access Tokens](https://github.com/settings/tokens)
- [GitHub REST API Documentation](https://docs.github.com/en/rest)
- [GitHub API Rate Limiting](https://docs.github.com/en/rest/overview/resources-in-the-rest-api#rate-limiting)
- [GitHub Scopes](https://docs.github.com/en/developers/apps/building-oauth-apps/scopes-for-oauth-apps)
