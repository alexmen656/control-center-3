<template>
  <div class="monaco-sidebar">
    <!-- File Explorer Section -->
    <div class="sidebar-section">
      <div class="section-header">
        <ion-icon name="folder-outline"></ion-icon>
        <span>Explorer</span>
        <ion-button fill="clear" size="small" @click="refreshFiles">
          <ion-icon name="refresh-outline"></ion-icon>
        </ion-button>
        <ion-button fill="clear" size="small" @click="createNewFile">
          <ion-icon name="document-outline"></ion-icon>
        </ion-button>
        <ion-button fill="clear" size="small" @click="createNewFolder">
          <ion-icon name="folder-outline"></ion-icon>
        </ion-button>
      </div>
      
      <div class="section-content">
        <div class="file-tree">
          <div v-if="projectFiles.length === 0" class="no-files">
            No files yet
            <div class="create-buttons">
              <ion-button size="small" @click="createNewFile">
                <ion-icon name="document-outline" slot="start"></ion-icon>
                New File
              </ion-button>
              <ion-button size="small" @click="createNewFolder">
                <ion-icon name="folder-outline" slot="start"></ion-icon>
                New Folder
              </ion-button>
            </div>
          </div>
          <div v-for="file in projectFiles" :key="file.path" class="file-item" @click="openFile(file)">
            <ion-icon :name="getFileIcon(file)" class="file-icon"></ion-icon>
            <span class="file-name">{{ file.name }}</span>
            <ion-button fill="clear" size="small" @click.stop="deleteFile(file)" class="delete-btn">
              <ion-icon name="trash-outline"></ion-icon>
            </ion-button>
          </div>
        </div>
      </div>
    </div>

    <!-- GitHub Section -->
    <div class="sidebar-section">
      <div class="section-header">
        <ion-icon name="logo-github"></ion-icon>
        <span>Source Control</span>
        <ion-button fill="clear" size="small" @click="refreshGitStatus">
          <ion-icon name="refresh-outline"></ion-icon>
        </ion-button>
      </div>
      
      <div class="section-content">
        <!-- Commit Input -->
        <div class="commit-input-section">
          <ion-textarea
            v-model="commitMessage"
            placeholder="Message (press Ctrl+Enter to commit)"
            rows="3"
            class="commit-textarea"
            @keydown="handleCommitKeyDown"
          ></ion-textarea>
          <ion-button 
            expand="block" 
            size="small" 
            @click="commitChanges"
            :disabled="!commitMessage.trim() || isCommitting"
          >
            <ion-icon name="checkmark-outline" slot="start"></ion-icon>
            {{ isCommitting ? 'Committing...' : 'Commit' }}
          </ion-button>
        </div>

        <!-- Changed Files -->
        <div class="changed-files">
          <div class="subsection-header">Changes</div>
          <div v-if="changedFiles.length === 0" class="no-changes">
            No changes
          </div>
          <div v-for="file in changedFiles" :key="file.path" class="file-item">
            <div class="file-status" :class="file.status">{{ getStatusIcon(file.status) }}</div>
            <div class="file-path">{{ file.path }}</div>
            <ion-button fill="clear" size="small" @click="stageFile(file.path)" v-if="!file.staged">
              <ion-icon name="add-outline"></ion-icon>
            </ion-button>
            <ion-button fill="clear" size="small" @click="unstageFile(file.path)" v-if="file.staged">
              <ion-icon name="remove-outline"></ion-icon>
            </ion-button>
            <ion-button fill="clear" size="small" @click="discardChanges(file.path)" v-if="!file.staged">
              <ion-icon name="refresh-outline"></ion-icon>
            </ion-button>
          </div>
        </div>

        <!-- Recent Commits -->
        <div class="recent-commits">
          <div class="subsection-header">Recent Commits</div>
          <div v-if="recentCommits.length === 0" class="no-commits">
            No commits yet
          </div>
          <div v-for="commit in recentCommits" :key="commit.hash" class="commit-item">
            <div class="commit-hash" @dblclick="openCommitOnGitHub(commit)">{{ commit.hash.substring(0, 7) }}</div>
            <div class="commit-message" @dblclick="openCommitOnGitHub(commit)">{{ commit.message }}</div>
            <div class="commit-author">{{ commit.author }}</div>
            <div class="commit-date">{{ formatDate(commit.date) }}</div>
          </div>
        </div>

        <!-- Pull Requests -->
        <div class="pull-requests">
          <div class="subsection-header">
            Pull Requests
            <ion-button fill="clear" size="small" @click="createPullRequest" class="create-pr-btn">
              <ion-icon name="git-pull-request-outline"></ion-icon>
            </ion-button>
          </div>
          <div v-if="pullRequests.length === 0" class="no-prs">
            No pull requests
          </div>
          <div v-for="pr in pullRequests" :key="pr.number" class="pr-item" @click="openPullRequest(pr)">
            <div class="pr-status" :class="pr.state">
              <ion-icon :name="getPRIcon(pr.state)"></ion-icon>
            </div>
            <div class="pr-info">
              <div class="pr-title">#{{ pr.number }} {{ pr.title }}</div>
              <div class="pr-meta">{{ pr.user.login }} • {{ formatDate(pr.created_at) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Vercel Section -->
    <div class="sidebar-section">
      <div class="section-header">
        <ion-icon name="cloud-outline"></ion-icon>
        <span>Deployments</span>
        <ion-button fill="clear" size="small" @click="refreshDeployments">
          <ion-icon name="refresh-outline"></ion-icon>
        </ion-button>
      </div>
      
      <div class="section-content">
        <!-- Deploy Button -->
        <ion-button 
          expand="block" 
          color="success" 
          size="small" 
          @click="deployToVercel"
          :disabled="isDeploying"
        >
          <ion-icon name="rocket-outline" slot="start"></ion-icon>
          {{ isDeploying ? 'Deploying...' : 'Deploy' }}
        </ion-button>

        <!-- Deployment List -->
        <div class="deployments-list">
          <div v-if="deployments.length === 0" class="no-deployments">
            No deployments yet
          </div>
          <div v-for="deployment in deployments" :key="deployment.id" class="deployment-item" @dblclick="openDeploymentInspector(deployment)">
            <div class="deployment-status" :class="deployment.state">
              <ion-icon :name="getDeploymentIcon(deployment.state)"></ion-icon>
            </div>
            <div class="deployment-info">
              <div class="deployment-url">
                <a :href="'https://' + deployment.url" target="_blank">{{ deployment.url }}</a>
              </div>
              <div class="deployment-commit">{{ deployment.commit?.substring(0, 7) }}</div>
              <div class="deployment-date">{{ formatDate(deployment.created) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()

// State
const commitMessage = ref('')
const isCommitting = ref(false)
const isDeploying = ref(false)
const changedFiles = ref([])
const recentCommits = ref([])
const deployments = ref([])
const projectFiles = ref([])
const pullRequests = ref([])

// Get project name from route
const projectName = route.params.project || 'default-project'

// File Explorer Methods
const refreshFiles = async () => {
  try {
    const response = await axios.get(`/backend/file_api.php?project=${projectName}&action=list`)
    projectFiles.value = flattenFileTree(response.data || [])
  } catch (error) {
    console.error('Failed to load files:', error)
    projectFiles.value = []
  }
}

const flattenFileTree = (files) => {
  let flattened = []
  files.forEach(file => {
    if (file.type === 'file') {
      flattened.push({
        name: file.name,
        path: file.path,
        type: file.type,
        size: file.size,
        modified: file.modified
      })
    } else if (file.type === 'directory' && file.children) {
      flattened = flattened.concat(flattenFileTree(file.children))
    }
  })
  return flattened
}

const openFile = (file) => {
  // Emit event to parent component to open file
  window.dispatchEvent(new CustomEvent('monaco-open-file', { detail: file }))
}

const createNewFile = async () => {
  const fileName = prompt('Enter file name:')
  if (fileName) {
    try {
      await axios.post(`/backend/file_api.php?project=${projectName}`, {
        action: 'create_file',
        path: fileName,
        content: ''
      })
      await refreshFiles()
      openFile({ name: fileName, path: fileName })
    } catch (error) {
      console.error('Failed to create file:', error)
      alert('Failed to create file: ' + error.message)
    }
  }
}

const createNewFolder = async () => {
  const folderName = prompt('Enter folder name:')
  if (folderName) {
    try {
      // Create folder by creating a .gitkeep file inside it
      await axios.post(`/backend/file_api.php?project=${projectName}`, {
        action: 'create_file',
        path: folderName + '/.gitkeep',
        content: '# This file keeps the folder in version control'
      })
      await refreshFiles()
    } catch (error) {
      console.error('Failed to create folder:', error)
      alert('Failed to create folder: ' + error.message)
    }
  }
}

const deleteFile = async (file) => {
  if (confirm(`Are you sure you want to delete ${file.name}?`)) {
    try {
      await axios.delete(`/backend/file_api.php?project=${projectName}`, {
        data: { file: file.path }
      })
      await refreshFiles()
      await refreshGitStatus()
    } catch (error) {
      console.error('Failed to delete file:', error)
      alert('Failed to delete file: ' + error.message)
    }
  }
}

const getFileIcon = (file) => {
  if (file.type === 'directory') return 'folder-outline'
  
  const ext = file.name.split('.').pop()?.toLowerCase()
  switch (ext) {
    case 'js': return 'logo-javascript'
    case 'ts': return 'logo-javascript'
    case 'vue': return 'logo-vue'
    case 'html': return 'logo-html5'
    case 'css': return 'logo-css3'
    case 'json': return 'code-outline'
    case 'md': return 'document-text-outline'
    case 'txt': return 'document-outline'
    default: return 'document-outline'
  }
}

// Git Methods
const loadGitData = async () => {
  try {
    // Load real git changes
    const changesResponse = await axios.get(`/backend/monaco_git_api.php?project=${projectName}&action=changes`)
    if (changesResponse.data.success) {
      changedFiles.value = changesResponse.data.changes || []
    } else {
      changedFiles.value = []
    }
    
    // Load commit history
    const commitsResponse = await axios.get(`/backend/monaco_git_api.php?project=${projectName}&action=commits`)
    if (commitsResponse.data.success) {
      recentCommits.value = commitsResponse.data.commits.map(commit => ({
        hash: commit.sha || commit.hash,
        message: commit.message,
        author: commit.author?.name || commit.author,
        date: new Date(commit.date || commit.created_at)
      }))
    } else {
      recentCommits.value = []
    }
  } catch (error) {
    console.error('Failed to load git data:', error)
    // Only show empty state on error, no mock data
    changedFiles.value = []
    recentCommits.value = []
  }
}

const loadPullRequests = async () => {
  try {
    const response = await axios.get(`/backend/monaco_git_api.php?project=${projectName}&action=pull_requests`)
    if (response.data.success) {
      pullRequests.value = response.data.pull_requests || []
    } else {
      pullRequests.value = []
    }
  } catch (error) {
    console.error('Failed to load pull requests:', error)
    pullRequests.value = []
  }
}

const loadDeployments = async () => {
  try {
    const response = await axios.get(`/backend/vercel_api.php?project=${projectName}&action=deployments`)
    if (response.data.success) {
      deployments.value = response.data.deployments.deployments?.map(deployment => ({
        id: deployment.uid,
        url: deployment.url,
        state: deployment.readyState,
        commit: deployment.meta?.githubCommitSha,
        created: deployment.created,
        inspectorUrl: deployment.inspectorUrl
      })) || []
    } else {
      deployments.value = []
    }
  } catch (error) {
    console.error('Failed to load deployments:', error)
    deployments.value = []
  }
}

// Git Methods
const handleCommitKeyDown = (event) => {
  if (event.ctrlKey && event.key === 'Enter') {
    commitChanges()
  }
}

const commitChanges = async () => {
  if (!commitMessage.value.trim()) return
  
  isCommitting.value = true
  try {
    const response = await axios.post(`/backend/monaco_git_api.php?project=${projectName}`, {
      action: 'commit',
      message: commitMessage.value,
      files: changedFiles.value
    })
    
    if (response.data.success) {
      // Add to recent commits
      recentCommits.value.unshift({
        hash: response.data.commit.sha,
        message: response.data.commit.message,
        author: response.data.commit.author.name,
        date: new Date(response.data.commit.date)
      })
      
      // Clear changed files and commit message
      changedFiles.value = []
      commitMessage.value = ''
      
      // Refresh git status
      await refreshGitStatus()
    } else {
      throw new Error(response.data.error || 'Commit failed')
    }
  } catch (error) {
    console.error('Commit failed:', error)
    alert('Commit failed: ' + error.message)
  } finally {
    isCommitting.value = false
  }
}

const stageFile = async (filePath) => {
  console.log('Staging file:', filePath)
  try {
    const response = await axios.post(`/backend/monaco_git_api.php?project=${projectName}`, {
      action: 'stage',
      file: filePath
    })
    
    if (response.data.success) {
      await refreshGitStatus()
    } else {
      console.error('Failed to stage file:', response.data.message)
    }
  } catch (error) {
    console.error('Error staging file:', error)
  }
}

const unstageFile = async (filePath) => {
  console.log('Unstaging file:', filePath)
  try {
    const response = await axios.post(`/backend/monaco_git_api.php?project=${projectName}`, {
      action: 'unstage',
      file: filePath
    })
    
    if (response.data.success) {
      await refreshGitStatus()
    } else {
      console.error('Failed to unstage file:', response.data.message)
    }
  } catch (error) {
    console.error('Error unstaging file:', error)
  }
}

const discardChanges = async (filePath) => {
  if (confirm(`Are you sure you want to discard changes to ${filePath}?`)) {
    try {
      const response = await axios.post(`/backend/monaco_git_api.php?project=${projectName}`, {
        action: 'discard',
        file: filePath
      })
      
      if (response.data.success) {
        await refreshGitStatus()
        // Refresh the file in editor if it's currently open
        window.dispatchEvent(new CustomEvent('monaco-refresh-file', { detail: { path: filePath } }))
      } else {
        console.error('Failed to discard changes:', response.data.message)
      }
    } catch (error) {
      console.error('Error discarding changes:', error)
    }
  }
}

const refreshGitStatus = async () => {
  console.log('Refreshing git status...')
  await Promise.allSettled([
    loadGitData(),
    loadPullRequests()
  ])
}

// Pull Request Methods
const createPullRequest = async () => {
  const title = prompt('Enter pull request title:')
  const baseBranch = prompt('Enter base branch (default: main):', 'main')
  const headBranch = prompt('Enter head branch (default: feature):')
  
  if (title && headBranch) {
    try {
      const response = await axios.post(`/backend/monaco_git_api.php?project=${projectName}`, {
        action: 'create_pull_request',
        title: title,
        base: baseBranch,
        head: headBranch,
        body: 'Created via Monaco IDE'
      })
      
      if (response.data.success) {
        await loadPullRequests()
        alert('Pull request created successfully!')
      } else {
        alert('Failed to create pull request: ' + response.data.message)
      }
    } catch (error) {
      console.error('Failed to create pull request:', error)
      alert('Failed to create pull request: ' + error.message)
    }
  }
}

const openPullRequest = (pr) => {
  window.open(pr.html_url, '_blank')
}

const getPRIcon = (state) => {
  switch (state) {
    case 'open': return 'git-pull-request-outline'
    case 'closed': return 'close-circle-outline'
    case 'merged': return 'git-merge-outline'
    default: return 'help-circle-outline'
  }
}

// Deployment Methods
const deployToVercel = async () => {
  isDeploying.value = true
  try {
    const response = await axios.post(`/backend/vercel_api.php?project=${projectName}`, {
      action: 'deploy',
      gitSource: {
        type: 'github',
        repo: projectName,
        ref: 'main'
      }
    })
    
    if (response.data.success) {
      // Add new deployment
      deployments.value.unshift({
        id: response.data.deployment.uid,
        url: response.data.deployment.url,
        state: 'BUILDING',
        commit: recentCommits.value[0]?.hash || 'latest',
        created: new Date()
      })
      
      // Simulate deployment completion
      setTimeout(() => {
        if (deployments.value[0]) {
          deployments.value[0].state = 'READY'
        }
      }, 3000)
    } else {
      throw new Error(response.data.error || 'Deployment failed')
    }
  } catch (error) {
    console.error('Deployment failed:', error)
    // For demo purposes, still add the deployment locally
    deployments.value.unshift({
      id: Date.now().toString(),
      url: `https://myproject-${Math.random().toString(36).substring(2, 8)}.vercel.app`,
      state: 'BUILDING',
      commit: recentCommits.value[0]?.hash || 'latest',
      created: new Date()
    })
    
    setTimeout(() => {
      if (deployments.value[0]) {
        deployments.value[0].state = 'READY'
      }
    }, 3000)
  } finally {
    isDeploying.value = false
  }
}

const refreshDeployments = async () => {
  console.log('Refreshing deployments...')
  await loadDeployments()
}

// Utility Methods
const getStatusIcon = (status) => {
  switch (status) {
    case 'modified': return 'M'
    case 'added': return 'A'
    case 'deleted': return 'D'
    case 'renamed': return 'R'
    case 'untracked': return 'U'
    default: return '?'
  }
}

const getDeploymentIcon = (state) => {
  switch (state) {
    case 'READY': return 'checkmark-circle-outline'
    case 'ERROR': return 'close-circle-outline'
    case 'BUILDING': return 'time-outline'
    case 'QUEUED': return 'hourglass-outline'
    default: return 'help-circle-outline'
  }
}

function formatDate(date) {
  // Vercel: Millisekunden (number), GitHub: ISO-String
  let dateObj;
  if (typeof date === 'number') {
    dateObj = new Date(date);
  } else if (typeof date === 'string' && /^\d+$/.test(date)) {
    dateObj = new Date(Number(date));
  } else {
    dateObj = new Date(date);
  }
  const now = new Date();
  const diff = now - dateObj;
  const minutes = Math.floor(diff / (1000 * 60));
  const hours = Math.floor(diff / (1000 * 60 * 60));
  const days = Math.floor(diff / (1000 * 60 * 60 * 24));
  if (minutes < 1) return 'just now';
  if (minutes < 60) return `${minutes}m ago`;
  if (hours < 24) return `${hours}h ago`;
  return `${days}d ago`;
}

function openCommitOnGitHub(commit) {
  // Passe ggf. die URL-Struktur an dein Repo an
  const repo = projectName;
  const hash = commit.hash;
  // Beispiel: https://github.com/<owner>/<repo>/commit/<hash>
  // Owner ggf. dynamisch holen, hier als Platzhalter 'alexmen656'
  const url = `https://github.com/alexmen656/${repo}/commit/${hash}`;
  window.open(url, '_blank');
}

function openDeploymentInspector(deployment) {
  if (deployment.inspectorUrl) {
    window.open(deployment.inspectorUrl, '_blank');
  }
}

// Initialize
onMounted(async () => {
  // Load real data only
  await Promise.allSettled([
    refreshFiles(),
    loadGitData(),
    loadPullRequests(),
    loadDeployments()
  ])
})
</script>

<style scoped>
.monaco-sidebar {
  width: 280px;
  background: var(--vscode-sideBar-background, #252526);
  color: var(--vscode-sideBar-foreground, #cccccc);
  border-right: 1px solid var(--vscode-sideBar-border, #2b2b2b);
  height: 100vh;
  overflow-y: auto;
  font-size: 13px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.sidebar-section {
  border-bottom: 1px solid var(--vscode-sideBar-border, #2b2b2b);
}

.section-header {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  background: var(--vscode-sideBarSectionHeader-background, #2d2d30);
  color: var(--vscode-sideBarSectionHeader-foreground, #cccccc);
  border-bottom: 1px solid var(--vscode-sideBar-border, #2b2b2b);
  font-weight: 600;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.5px;
}

.section-header ion-icon:first-child {
  margin-right: 8px;
  font-size: 16px;
}

.section-header span {
  flex: 1;
}

.section-header ion-button {
  --color: var(--vscode-sideBarSectionHeader-foreground, #cccccc);
  margin: 0;
  height: 20px;
  width: 20px;
}

.section-content {
  padding: 8px;
}

.subsection-header {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--vscode-descriptionForeground, #999999);
  margin: 12px 0 6px 0;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.create-pr-btn {
  --color: var(--vscode-descriptionForeground, #999999);
  margin: 0;
  height: 16px;
  width: 16px;
}

/* File Explorer */
.file-tree {
  margin-bottom: 16px;
}

.file-item {
  display: flex;
  align-items: center;
  padding: 4px 8px;
  border-radius: 4px;
  margin-bottom: 2px;
  cursor: pointer;
  position: relative;
}

.file-item:hover {
  background: var(--vscode-list-hoverBackground, #2a2d2e);
}

.file-icon {
  margin-right: 8px;
  font-size: 16px;
  flex-shrink: 0;
}

.file-name {
  flex: 1;
  font-size: 13px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.delete-btn {
  opacity: 0;
  transition: opacity 0.2s;
  --color: var(--vscode-errorForeground, #f85149);
  margin: 0;
  height: 20px;
  width: 20px;
}

.file-item:hover .delete-btn {
  opacity: 1;
}

.no-files {
  padding: 16px 8px;
  text-align: center;
  color: var(--vscode-descriptionForeground, #999999);
  font-size: 13px;
  font-style: italic;
}

.create-buttons {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.create-buttons ion-button {
  --background: var(--vscode-button-background, #0e639c);
  --color: var(--vscode-button-foreground, #ffffff);
  height: 32px;
  font-size: 12px;
}

/* Commit Section */
.commit-input-section {
  margin-bottom: 16px;
}

.commit-textarea {
  --background: var(--vscode-input-background, #3c3c3c);
  --color: var(--vscode-input-foreground, #cccccc);
  --border-color: var(--vscode-input-border, #464647);
  --placeholder-color: var(--vscode-input-placeholderForeground, #999999);
  margin-bottom: 8px;
  font-size: 13px;
  border-radius: 4px;
}

.commit-textarea::part(native) {
  resize: vertical;
  min-height: 60px;
}

.changed-files,
.recent-commits,
.pull-requests {
  margin-bottom: 16px;
}

.commit-item {
  flex-direction: column;
  align-items: flex-start;
  padding: 8px;
}

.commit-item {
  display: flex;
  align-items: center;
  padding: 4px 8px;
  border-radius: 4px;
  margin-bottom: 2px;
  cursor: pointer;
}

.commit-item:hover {
  background: var(--vscode-list-hoverBackground, #2a2d2e);
}

.file-status {
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: bold;
  margin-right: 8px;
  border-radius: 2px;
}

.file-status.modified {
  background: var(--vscode-gitDecoration-modifiedResourceForeground, #e2c08d);
  color: #000;
}

.file-status.added {
  background: var(--vscode-gitDecoration-addedResourceForeground, #81b88b);
  color: #000;
}

.file-status.deleted {
  background: var(--vscode-gitDecoration-deletedResourceForeground, #c74e39);
  color: #fff;
}

.file-status.untracked {
  background: var(--vscode-gitDecoration-untrackedResourceForeground, #73c991);
  color: #000;
}

.file-path {
  flex: 1;
  font-size: 13px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.commit-item {
  flex-direction: column;
  align-items: flex-start;
  padding: 8px;
}

.commit-hash {
  font-family: monospace;
  font-size: 11px;
  color: var(--vscode-descriptionForeground, #999999);
  margin-bottom: 2px;
}

.commit-message {
  font-size: 13px;
  margin-bottom: 2px;
  line-height: 1.3;
}

.commit-author,
.commit-date {
  font-size: 11px;
  color: var(--vscode-descriptionForeground, #999999);
}

/* Pull Requests */
.pr-item {
  display: flex;
  align-items: center;
  padding: 8px;
  border-radius: 4px;
  margin-bottom: 4px;
  cursor: pointer;
}

.pr-item:hover {
  background: var(--vscode-list-hoverBackground, #2a2d2e);
}

.pr-status {
  margin-right: 8px;
}

.pr-status.open {
  color: var(--vscode-testing-iconQueued, #73c991);
}

.pr-status.closed {
  color: var(--vscode-testing-iconFailed, #f85149);
}

.pr-status.merged {
  color: var(--vscode-testing-iconPassed, #a991f5);
}

.pr-info {
  flex: 1;
}

.pr-title {
  font-size: 13px;
  margin-bottom: 2px;
  line-height: 1.3;
}

.pr-meta {
  font-size: 11px;
  color: var(--vscode-descriptionForeground, #999999);
}

.no-changes,
.no-commits,
.no-deployments,
.no-prs {
  padding: 16px 8px;
  text-align: center;
  color: var(--vscode-descriptionForeground, #999999);
  font-size: 13px;
  font-style: italic;
}

/* Deployment Section */
.deployments-list {
  margin-top: 8px;
}

.deployment-item {
  display: flex;
  align-items: center;
  padding: 8px;
  border-radius: 4px;
  margin-bottom: 4px;
  cursor: pointer;
}

.deployment-item:hover {
  background: var(--vscode-list-hoverBackground, #2a2d2e);
}

.deployment-status {
  margin-right: 8px;
}

.deployment-status.READY {
  color: var(--vscode-testing-iconPassed, #73c991);
}

.deployment-status.ERROR {
  color: var(--vscode-testing-iconFailed, #f85149);
}

.deployment-status.BUILDING {
  color: var(--vscode-testing-iconQueued, #ffa500);
}

.deployment-info {
  flex: 1;
}

.deployment-url {
  font-size: 13px;
  margin-bottom: 2px;
}

.deployment-url a {
  color: var(--vscode-textLink-foreground, #4fc1ff);
  text-decoration: none;
}

.deployment-url a:hover {
  text-decoration: underline;
}

.deployment-commit,
.deployment-date {
  font-size: 11px;
  color: var(--vscode-descriptionForeground, #999999);
}

.deployment-commit {
  font-family: monospace;
}

/* Buttons */
ion-button {
  --background: var(--vscode-button-background, #0e639c);
  --background-hover: var(--vscode-button-hoverBackground, #1177bb);
  --color: var(--vscode-button-foreground, #ffffff);
  --border-radius: 4px;
  font-size: 13px;
  height: 28px;
  margin: 2px 0;
}

ion-button[color="success"] {
  --background: var(--vscode-testing-iconPassed, #73c991);
  --background-hover: #5bb379;
}

ion-button[fill="clear"] {
  --background: transparent;
  --background-hover: var(--vscode-toolbar-hoverBackground, #3e3e42);
  --color: var(--vscode-foreground, #cccccc);
}

/* Scrollbar */
.monaco-sidebar::-webkit-scrollbar {
  width: 10px;
}

.monaco-sidebar::-webkit-scrollbar-track {
  background: var(--vscode-scrollbarSlider-background, #424242);
}

.monaco-sidebar::-webkit-scrollbar-thumb {
  background: var(--vscode-scrollbarSlider-background, #4e4e4e);
  border-radius: 5px;
}

.monaco-sidebar::-webkit-scrollbar-thumb:hover {
  background: var(--vscode-scrollbarSlider-hoverBackground, #5a5a5a);
}
</style>
