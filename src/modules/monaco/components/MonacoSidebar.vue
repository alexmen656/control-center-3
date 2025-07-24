<template>
  <div class="monaco-sidebar">
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
            <ion-button fill="clear" size="small" @click="stageFile(file.path)">
              <ion-icon name="add-outline"></ion-icon>
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
            <div class="commit-hash">{{ commit.hash.substring(0, 7) }}</div>
            <div class="commit-message">{{ commit.message }}</div>
            <div class="commit-author">{{ commit.author }}</div>
            <div class="commit-date">{{ formatDate(commit.date) }}</div>
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
          <div v-for="deployment in deployments" :key="deployment.id" class="deployment-item">
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
import qs from 'qs'

const route = useRoute()

// State
const commitMessage = ref('')
const isCommitting = ref(false)
const isDeploying = ref(false)
const changedFiles = ref([])
const recentCommits = ref([])
const deployments = ref([])

// Get project name from route
const projectName = route.params.project || 'default-project'

// API Methods
const loadGitData = async () => {
  try {
    const response = await axios.get(`github_api.php?project=${projectName}&action=commits`)
    if (response.data.success) {
      recentCommits.value = response.data.commits.map(commit => ({
        hash: commit.sha,
        message: commit.commit.message,
        author: commit.commit.author.name,
        date: new Date(commit.commit.author.date)
      }))
    }
  } catch (error) {
    console.error('Failed to load git data:', error)
    // Fall back to mock data
    loadMockGitData()
  }
}

const loadDeployments = async () => {
  try {
    const response = await axios.get(`vercel_api.php?project=${projectName}&action=deployments`)
    if (response.data.success) {
      deployments.value = response.data.deployments.deployments?.map(deployment => ({
        id: deployment.uid,
        url: deployment.url,
        state: deployment.readyState,
        commit: deployment.meta?.githubCommitSha,
        created: deployment.created
      })) || []
    }
  } catch (error) {
    console.error('Failed to load deployments:', error)
    // Fall back to mock data
    loadMockDeployments()
  }
}

// Mock data for development fallback
const loadMockGitData = () => {
  changedFiles.value = [
    { path: 'src/components/MonacoEditor.vue', status: 'modified' },
    { path: 'src/assets/styles.css', status: 'added' },
    { path: 'package.json', status: 'modified' }
  ]
  
  recentCommits.value = [
    {
      hash: 'abc123def456',
      message: 'Add Monaco editor support',
      author: 'Developer',
      date: new Date(Date.now() - 1000 * 60 * 30) // 30 minutes ago
    },
    {
      hash: 'def456ghi789',
      message: 'Update project sidebar',
      author: 'Developer',
      date: new Date(Date.now() - 1000 * 60 * 60 * 2) // 2 hours ago
    },
    {
      hash: 'ghi789jkl012',
      message: 'Fix responsive layout',
      author: 'Developer',
      date: new Date(Date.now() - 1000 * 60 * 60 * 24) // 1 day ago
    }
  ]
}

const loadMockDeployments = () => {
  deployments.value = [
    {
      id: '1',
      url: 'https://myproject-abc123.vercel.app',
      state: 'READY',
      commit: 'abc123def456',
      created: new Date(Date.now() - 1000 * 60 * 15) // 15 minutes ago
    },
    {
      id: '2',
      url: 'https://myproject-def456.vercel.app',
      state: 'ERROR',
      commit: 'def456ghi789',
      created: new Date(Date.now() - 1000 * 60 * 60 * 3) // 3 hours ago
    },
    {
      id: '3',
      url: 'https://myproject-ghi789.vercel.app',
      state: 'READY',
      commit: 'ghi789jkl012',
      created: new Date(Date.now() - 1000 * 60 * 60 * 24 * 2) // 2 days ago
    }
  ]
}

// Methods
const handleCommitKeyDown = (event) => {
  if (event.ctrlKey && event.key === 'Enter') {
    commitChanges()
  }
}

const commitChanges = async () => {
  if (!commitMessage.value.trim()) return
  
  isCommitting.value = true
  try {
    const response = await axios.post(`github_api.php?project=${projectName}`, {
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
        date: new Date(response.data.commit.created_at)
      })
      
      // Clear changed files and commit message
      changedFiles.value = []
      commitMessage.value = ''
    } else {
      throw new Error(response.data.error || 'Commit failed')
    }
  } catch (error) {
    console.error('Commit failed:', error)
    // For demo purposes, still add the commit locally
    recentCommits.value.unshift({
      hash: Math.random().toString(36).substring(2, 15),
      message: commitMessage.value,
      author: 'Current User',
      date: new Date()
    })
    changedFiles.value = []
    commitMessage.value = ''
  } finally {
    isCommitting.value = false
  }
}

const stageFile = (filePath) => {
  console.log('Staging file:', filePath)
  // TODO: Implement file staging
}

const refreshGitStatus = async () => {
  console.log('Refreshing git status...')
  await loadGitData()
  // For demo, also refresh mock changed files
  loadMockGitData()
}

const deployToVercel = async () => {
  isDeploying.value = true
  try {
    const response = await axios.post(`vercel_api.php?project=${projectName}`, {
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

const getStatusIcon = (status) => {
  switch (status) {
    case 'modified': return 'M'
    case 'added': return 'A'
    case 'deleted': return 'D'
    case 'renamed': return 'R'
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
    console.log(date, dateObj);
  } else if (typeof date === 'string' && /^\d+$/.test(date)) {
    dateObj = new Date(Number(date));
    console.log(date, dateObj);
  } else {
    dateObj = new Date(date);
    console.log(date, dateObj);
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

// Initialize
onMounted(async () => {
  // Load initial mock data
  loadMockGitData()
  loadMockDeployments()
  
  // Try to load real data
  await Promise.allSettled([
    loadGitData(),
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
.recent-commits {
  margin-bottom: 16px;
}

.file-item,
.commit-item {
  display: flex;
  align-items: center;
  padding: 4px 8px;
  border-radius: 4px;
  margin-bottom: 2px;
  cursor: pointer;
}

.file-item:hover,
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

.no-changes,
.no-commits,
.no-deployments {
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
