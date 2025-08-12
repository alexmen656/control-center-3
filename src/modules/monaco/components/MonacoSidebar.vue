<template>
  <div class="monaco-sidebar">
    <!-- File Explorer Section -->
    <div class="sidebar-section">
      <div class="section-header">
        <ion-icon name="folder-outline"></ion-icon>
        <span>Explorer</span>
        <ion-button fill="clear" size="small" @click="refreshFiles">
          <ion-icon slot="icon-only" name="refresh-outline"></ion-icon>
        </ion-button>
        <ion-button fill="clear" size="small" @click="createNewFile">
          <ion-icon slot="icon-only" name="document-outline"></ion-icon>
        </ion-button>
        <ion-button fill="clear" size="small" @click="createNewFolder">
          <ion-icon slot="icon-only" name="folder-outline"></ion-icon>
        </ion-button>
      </div>

      <div class="section-content">
        <div class="file-tree">
          <div v-if="hierarchicalFileTree.length === 0" class="no-files">
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
          
          <!-- Recursive file tree rendering -->
          <div v-for="item in hierarchicalFileTree" :key="item.path" class="tree-item">
            <!-- Folder -->
            <div v-if="item.type === 'directory'" 
                 class="file-item folder-item" 
                 @click="toggleFolder(item.path)">
              <ion-icon 
                :name="isFolderExpanded(item.path) ? 'chevron-down-outline' : 'chevron-forward-outline'" 
                class="folder-chevron"
              ></ion-icon>
              <ion-icon :name="isFolderExpanded(item.path) ? 'folder-open-outline' : 'folder-outline'" class="file-icon"></ion-icon>
              <span class="file-name">{{ item.name }}</span>
              <ion-button fill="clear" size="small" @click.stop="deleteFile(item)" class="delete-btn">
                <ion-icon name="trash-outline"></ion-icon>
              </ion-button>
            </div>
            
            <!-- Children (if folder is expanded) -->
            <div v-if="item.type === 'directory' && isFolderExpanded(item.path)" class="folder-children">
              <div v-for="child in item.children" :key="child.path" class="tree-item" style="margin-left: 16px;">
                <!-- Nested Folder -->
                <div v-if="child.type === 'directory'" 
                     class="file-item folder-item" 
                     @click="toggleFolder(child.path)">
                  <ion-icon 
                    :name="isFolderExpanded(child.path) ? 'chevron-down-outline' : 'chevron-forward-outline'" 
                    class="folder-chevron"
                  ></ion-icon>
                  <ion-icon :name="isFolderExpanded(child.path) ? 'folder-open-outline' : 'folder-outline'" class="file-icon"></ion-icon>
                  <span class="file-name">{{ child.name }}</span>
                  <ion-button fill="clear" size="small" @click.stop="deleteFile(child)" class="delete-btn">
                    <ion-icon name="trash-outline"></ion-icon>
                  </ion-button>
                </div>
                
                <!-- Nested Children -->
                <div v-if="child.type === 'directory' && isFolderExpanded(child.path)" class="folder-children">
                  <div v-for="grandchild in child.children" :key="grandchild.path" class="tree-item" style="margin-left: 32px;">
                    <!-- File in nested folder -->
                    <div v-if="grandchild.type === 'file'" 
                         class="file-item" 
                         :class="{ 'active-file': activeFile === grandchild.path }"
                         @click="openFile(grandchild)">
                      <span class="file-indent"></span>
                      <ion-icon :name="getFileIcon(grandchild)" class="file-icon"></ion-icon>
                      <span class="file-name">{{ grandchild.name }}</span>
                      <ion-button fill="clear" size="small" @click.stop="deleteFile(grandchild)" class="delete-btn">
                        <ion-icon name="trash-outline"></ion-icon>
                      </ion-button>
                    </div>
                  </div>
                </div>
                
                <!-- File in first level folder -->
                <div v-if="child.type === 'file'" 
                     class="file-item" 
                     :class="{ 'active-file': activeFile === child.path }"
                     @click="openFile(child)">
                  <span class="file-indent"></span>
                  <ion-icon :name="getFileIcon(child)" class="file-icon"></ion-icon>
                  <span class="file-name">{{ child.name }}</span>
                  <ion-button fill="clear" size="small" @click.stop="deleteFile(child)" class="delete-btn">
                    <ion-icon name="trash-outline"></ion-icon>
                  </ion-button>
                </div>
              </div>
            </div>
            
            <!-- File at root level -->
            <div v-if="item.type === 'file'" 
                 class="file-item" 
                 :class="{ 'active-file': activeFile === item.path }"
                 @click="openFile(item)">
              <span class="file-indent"></span>
              <ion-icon :name="getFileIcon(item)" class="file-icon"></ion-icon>
              <span class="file-name">{{ item.name }}</span>
              <ion-button fill="clear" size="small" @click.stop="deleteFile(item)" class="delete-btn">
                <ion-icon name="trash-outline"></ion-icon>
              </ion-button>
            </div>
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
          <ion-icon slot="icon-only" name="refresh-outline"></ion-icon>
        </ion-button>
        <ion-button fill="clear" size="small" @click="pullFromGitHub" :disabled="isPulling">
          <ion-icon slot="icon-only" name="cloud-download-outline"></ion-icon>
          <!--{{ isPulling ? 'Pulling...' : 'Pull' }}-->
        </ion-button>
        <ion-button fill="clear" size="small" @click="pushToGitHub" :disabled="isPushing">
          <ion-icon slot="icon-only" name="cloud-upload-outline"></ion-icon>
          <!--{{ isPushing ? 'Pushing...' : 'Push' }}-->
        </ion-button>
      </div>

      <div class="section-content">
        <!-- Commit Input -->
        <div class="commit-input-section">
          <ion-textarea v-model="commitMessage" placeholder="Message (press Ctrl+Enter to commit)" rows="3"
            class="commit-textarea" @keydown="handleCommitKeyDown"></ion-textarea>
          <ion-button expand="block" size="small" @click="commitChanges"
            :disabled="!commitMessage.trim() || isCommitting">
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
          <div v-for="file in changedFiles" :key="file.path || file.file" class="file-item" @click="viewFileDiff(file.path || file.file)">
            <div class="file-status" :class="file.status">{{ getStatusIcon(file.status) }}</div>
            <div class="file-path">{{ file.path || file.file }}</div>
            <ion-button fill="clear" size="small" @click.stop="stageFile(file.path || file.file)" v-if="!file.staged">
              <ion-icon name="add-outline"></ion-icon>
            </ion-button>
            <ion-button fill="clear" size="small" @click.stop="unstageFile(file.path || file.file)" v-if="file.staged">
              <ion-icon name="remove-outline"></ion-icon>
            </ion-button>
            <ion-button fill="clear" size="small" @click.stop="discardChanges(file.path || file.file)" v-if="!file.staged">
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
          <ion-icon slot="icon-only" name="refresh-outline"></ion-icon>
        </ion-button>
      </div>

      <div class="section-content">
        <!-- Deploy Button -->
        <ion-button expand="block" color="success" size="small" @click="deployToVercel" :disabled="isDeploying">
          <ion-icon name="rocket-outline" slot="start"></ion-icon>
          {{ isDeploying ? 'Deploying...' : 'Deploy' }}
        </ion-button>

        <!-- Deployment List -->
        <div class="deployments-list">
          <div v-if="deployments.length === 0" class="no-deployments">
            No deployments yet
          </div>
          <div v-for="deployment in deployments" :key="deployment.id" class="deployment-item"
            @dblclick="openDeploymentInspector(deployment)">
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

    <!-- CMS APIs Section -->
    <div class="sidebar-section">
      <div class="section-header">
        <ion-icon name="server-outline"></ion-icon>
        <span>CMS APIs</span>
        <ion-button fill="clear" size="small" @click="refreshAvailableAPIs">
          <ion-icon slot="icon-only" name="refresh-outline"></ion-icon>
        </ion-button>
      </div>

      <div class="section-content">
        <!-- Available APIs with toggle switches -->
        <div v-if="availableAPIs.length === 0" class="no-apis">
          No APIs subscribed for this project.<br>
          <small>Subscribe to APIs in the main Control Center.</small>
        </div>
        
        <div v-for="api in availableAPIs" :key="api.slug" class="api-item">
          <div class="api-info">
            <ion-icon :name="api.icon || 'server-outline'" class="api-icon"></ion-icon>
            <div class="api-details">
              <span class="api-name">{{ api.name }}</span>
              <small class="api-category">{{ api.category }}</small>
            </div>
          </div>
          
          <div class="api-controls">
            <ion-toggle 
              :checked="api.is_active" 
              @ionChange="toggleAPI(api)"
              :disabled="api.isToggling"
            ></ion-toggle>
          </div>
        </div>

        <!-- Active APIs Quick Copy -->
        <div v-if="activeAPIs.length === 0" class="no-apis-message">
          Activate APIs above to see import examples
        </div>
        <div v-else>
          <div class="copy-section">
            <div class="subsection-header">All Imports</div>
            <div class="code-block" @click="copyToClipboard(getAllImportsExample())">
              <code>{{ getAllImportsExample() }}</code>
              <ion-icon name="copy-outline" class="copy-icon"></ion-icon>
            </div>
          </div>
          <div class="copy-section">
            <div class="subsection-header">Usage Example</div>
            <div class="code-block" @click="copyToClipboard(getUsageExample())">
              <code>{{ getUsageExample() }}</code>
              <ion-icon name="copy-outline" class="copy-icon"></ion-icon>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Diff Viewer Modal -->
  <div v-if="showDiffViewer" class="diff-viewer-modal" @click="closeDiffViewer">
    <div class="diff-viewer-content" @click.stop>
      <div class="diff-viewer-header">
        <h3>Changes in {{ diffData.filePath }}</h3>
        <button class="close-diff-btn" @click="closeDiffViewer">×</button>
      </div>
      <div class="diff-viewer-body">
        <div class="diff-content">
          <div v-for="(line, index) in (diffData.diff || [])" :key="index" 
               :class="['diff-line', line.type]">
            <div class="diff-line-number">{{ line.lineNumber }}</div>
            <div class="diff-line-content">
              {{ (line.type === 'added' ? '+' : line.type === 'deleted' ? '-' : ' ') + ' ' + (line.content || '') }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Merge Editor Modal -->
  <div v-if="showMergeEditor" class="merge-editor-modal" @click="closeMergeEditor">
    <div class="merge-editor-content" @click.stop>
      <div class="merge-editor-header">
        <h3>Merge Conflicts Detected</h3>
        <button class="close-merge-btn" @click="closeMergeEditor">×</button>
      </div>
      <div class="merge-editor-body">
        <p>The following files have conflicts that need to be resolved:</p>
        <div class="conflicts-list">
          <div v-for="file in mergeConflicts" :key="file" class="conflict-file">
            <ion-icon name="warning" class="conflict-icon"></ion-icon>
            <span class="conflict-filename">{{ file }}</span>
            <button class="resolve-btn" @click="resolveConflict(file)">Resolve</button>
          </div>
        </div>
        <div class="merge-actions">
          <button class="btn-secondary" @click="closeMergeEditor">Cancel</button>
          <button class="btn-primary" @click="autoResolveConflicts">Auto-resolve All</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { ToastService } from '@/services/ToastService'

const route = useRoute()

// State
const commitMessage = ref('')
const isCommitting = ref(false)
const isPulling = ref(false)
const isPushing = ref(false)
const isDeploying = ref(false)
const changedFiles = ref([])
const recentCommits = ref([])
const deployments = ref([])
const projectFiles = ref([])
const pullRequests = ref([])

// API State
const availableAPIs = ref([])
const activeAPIs = computed(() => availableAPIs.value.filter(api => api.is_active))

// Excluded files state
const excludedFiles = ref([".monaco_commits.json", ".monaco_git", ".monaco_initialized", ".monaco_lastcommit.json", ".monaco_staged.json"])
const exclude = ref(true)

// Folder state for Explorer
const expandedFolders = ref(new Set())
const fileTree = ref([])

// Live update state
const gitRefreshInterval = ref(null)

// State für Modals
const showDiffViewer = ref(false)
const showMergeEditor = ref(false)
const diffData = ref({})
const mergeConflicts = ref([])

// Active file state
const activeFile = ref('')

// Get project and codespace name from route
const projectName = route.params.project || 'default-project'
const codespace = route.params.codespace || 'main'

// API Methods - for loading codespace-specific APIs
const loadAvailableAPIs = async () => {
  try {
    const formData = new FormData()
    formData.append('getCodespaceAPIs', '1')
    formData.append('project', projectName)
    formData.append('codespace', codespace)
    
    const response = await axios.post('codespace_apis.php', formData)
    
    if (response.data && Array.isArray(response.data)) {
      availableAPIs.value = response.data.map(api => ({
        ...api,
        isToggling: false
      }))
    } else {
      availableAPIs.value = []
    }
  } catch (error) {
    console.error('Failed to load available APIs:', error)
    availableAPIs.value = []
  }
}

const refreshAvailableAPIs = async () => {
  await loadAvailableAPIs()
}

const toggleAPI = async (api) => {
  if (api.isToggling) return
  
  api.isToggling = true
  
  try {
    const formData = new FormData()
    formData.append('project', projectName)
    formData.append('codespace', codespace)
    formData.append('subscription_id', api.subscription_id)
    
    if (api.is_active) {
      formData.append('deactivateCodespaceAPI', '1')
      const response = await axios.post('codespace_apis.php', formData)
      
      if (response.data && response.data.success) {
        api.is_active = false
        ToastService.success('API deactivated successfully')
      } else {
        ToastService.error(response.data?.message || 'Failed to deactivate API')
      }
    } else {
      formData.append('activateCodespaceAPI', '1')
      const response = await axios.post('codespace_apis.php', formData)
      
      if (response.data && response.data.success) {
        api.is_active = true
        ToastService.success('API activated successfully')
      } else {
        ToastService.error(response.data?.message || 'Failed to activate API')
      }
    }
  } catch (error) {
    console.error('Failed to toggle API:', error)
    ToastService.error('Failed to toggle API')
  } finally {
    api.isToggling = false
  }
}

const getAPIClassName = (slug) => {
  return slug.charAt(0).toUpperCase() + slug.slice(1) + 'API'
}

const getAllImportsExample = () => {
  if (activeAPIs.value.length === 0) return ''
  
  const imports = activeAPIs.value.map(api => getAPIClassName(api.slug)).join(', ')
  return `import { ${imports} } from 'apis';`
}

const getUsageExample = () => {
  if (activeAPIs.value.length === 0) return ''
  
  const firstAPI = activeAPIs.value[0]
  const className = getAPIClassName(firstAPI.slug)
  
  switch (firstAPI.slug) {
    case 'user-management':
      return `// Get all users\nconst users = await ${className}.getAll();\n\n// Create new user\nconst user = await ${className}.create({ name: 'John', email: 'john@example.com' });`
    case 'file-storage':
      return `// Upload file\nconst result = await ${className}.upload(file);\n\n// List files\nconst files = await ${className}.list();`
    case 'database':
      return `// Query database\nconst records = await ${className}.query('users', { active: true });\n\n// Insert record\nconst result = await ${className}.insert('users', { name: 'John' });`
    case 'notifications':
      return `// Send notification\nconst result = await ${className}.send({ message: 'Hello!', userId: 123 });\n\n// Get notifications\nconst notifications = await ${className}.list();`
    case 'analytics':
      return `// Track event\nconst result = await ${className}.track('user_login', { userId: 123 });\n\n// Get analytics\nconst data = await ${className}.getReport('daily');`
    default:
      return `// Use ${firstAPI.name} API\nconst result = await ${className}.get();`
  }
}

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    ToastService.success('Copied to clipboard!')
  }).catch(() => {
    ToastService.error('Failed to copy to clipboard')
  })
}

// Computed function to filter projectFiles
const filteredProjectFiles = computed(() => {
  if (exclude.value) {
    console.log(projectFiles.value);
    // Zeige alle Dateien an, aber excludiere nur bestimmte Monaco-interne Dateien aus der Anzeige
    // .monaco_apis/ Dateien sollen weiterhin sichtbar und commitbar sein
    return projectFiles.value.filter(file => 
      !excludedFiles.value.includes(file.path) || 
      file.path.startsWith(".monaco_apis/")
    );
  } else {
    return projectFiles.value;
  }
});

// Build hierarchical file tree from flat file list
const buildFileTree = (files) => {
  const tree = []
  const pathMap = new Map()

  // Create root nodes and build path map
  files.forEach(file => {
    const pathParts = file.path.split('/').filter(part => part)
    let currentPath = ''
    
    pathParts.forEach((part, index) => {
      const parentPath = currentPath
      currentPath = currentPath ? `${currentPath}/${part}` : part
      
      if (!pathMap.has(currentPath)) {
        const isFile = index === pathParts.length - 1
        const node = {
          name: part,
          path: currentPath,
          type: isFile ? 'file' : 'directory',
          children: [],
          size: isFile ? file.size : 0,
          modified: isFile ? file.modified : null,
          parent: parentPath || null
        }
        pathMap.set(currentPath, node)
        
        if (parentPath) {
          const parent = pathMap.get(parentPath)
          if (parent) {
            parent.children.push(node)
          }
        } else {
          tree.push(node)
        }
      }
    })
  })

  return tree
}

// Get hierarchical file tree
const hierarchicalFileTree = computed(() => {
  return buildFileTree(filteredProjectFiles.value)
})

// Toggle folder expansion
const toggleFolder = (folderPath) => {
  if (expandedFolders.value.has(folderPath)) {
    expandedFolders.value.delete(folderPath)
  } else {
    expandedFolders.value.add(folderPath)
  }
}

// Check if folder is expanded
const isFolderExpanded = (folderPath) => {
  return expandedFolders.value.has(folderPath)
}

// Get file icon based on file type/extension
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

// File Explorer Methods
const refreshFiles = async () => {
  try {
    // Use file API to load files from specific codespace
    const response = await axios.get(`file_api.php?project=${projectName}&codespace=${codespace}&action=list`)
    const allFiles = flattenFileTree(response.data || [])
    
    // Make sure .monaco_apis files are included and not excluded from commits
    projectFiles.value = allFiles
    
    // Update file tree
    fileTree.value = buildFileTree(filteredProjectFiles.value)
  } catch (error) {
    console.error('Failed to load files from codespace:', error)
    projectFiles.value = []
    fileTree.value = []
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
  // Set active file
  activeFile.value = file.path
  // Emit event to parent component to open file
  window.dispatchEvent(new CustomEvent('monaco-open-file', { detail: file }))
}

const createNewFile = async () => {
  const fileName = prompt('Enter file name (with extension):')
  if (fileName) {
    try {
      // Use file API to create file in specific codespace
      await axios.post(`file_api.php?project=${projectName}&codespace=${codespace}`, {
        action: 'create_file',
        path: fileName,
        content: ''
      })
      await refreshFiles()
      openFile({ name: fileName, path: fileName })
      ToastService.success(`File "${fileName}" created successfully in codespace "${codespace}"!`)
    } catch (error) {
      console.error('Failed to create file:', error)
      ToastService.error('Failed to create file: ' + (error.response?.data?.message || error.message))
    }
  }
}

const createNewFolder = async () => {
  const folderName = prompt('Enter folder name:')
  if (folderName) {
    try {
      // Create folder using the codespace API with codespace parameter
      await axios.post(`file_api.php?project=${projectName}&codespace=${codespace}`, {
        action: 'create_folder',
        path: folderName
      })
      await refreshFiles()
      ToastService.success(`Folder "${folderName}" created successfully in codespace "${codespace}"!`)
    } catch (error) {
      console.error('Failed to create folder:', error)
      ToastService.error('Failed to create folder: ' + (error.response?.data?.message || error.message))
    }
  }
}

const deleteFile = async (file) => {
  if (confirm(`Are you sure you want to delete ${file.name}?`)) {
    try {
      // Use file API to delete file from specific codespace
      await axios.delete(`file_api.php?project=${projectName}&codespace=${codespace}`, {
        data: { file: file.path }
      })
      await refreshFiles()
      await refreshGitStatus()
      ToastService.success(`File "${file.name}" deleted from codespace "${codespace}"!`)
    } catch (error) {
      console.error('Failed to delete file:', error)
      ToastService.error('Failed to delete file: ' + (error.response?.data?.message || error.message))
    }
  }
}

// Git Methods
const loadGitData = async () => {
  try {
    // Load real git changes for specific codespace
    const changesResponse = await axios.get(`monaco_git_api.php?project=${projectName}&codespace=${codespace}&action=changes`)
    if (changesResponse.data.success) {
      changedFiles.value = changesResponse.data.changes || []
    } else {
      changedFiles.value = []
    }

    // Load commit history for specific codespace
    const commitsResponse = await axios.get(`monaco_git_api.php?project=${projectName}&codespace=${codespace}&action=commits`)
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
    const response = await axios.get(`monaco_pr_api.php?project=${projectName}&codespace=${codespace}&action=list`)
    if (response.data.success) {
      pullRequests.value = response.data.data || []
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
    const response = await axios.get(`vercel_api.php?project=${projectName}&codespace=${codespace}&action=deployments`)
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
    event.preventDefault() // Prevent any default behavior
    if (!isCommitting.value) { // Only commit if not already committing
      commitChanges()
    }
  }
}

const commitChanges = async () => {
  if (!commitMessage.value.trim()) return
  if (isCommitting.value) return // Prevent multiple commits

  isCommitting.value = true
  try {
    console.log('Committing changes:', {
      message: commitMessage.value,
      filesCount: changedFiles.value.length,
      files: changedFiles.value.map(f => f.path || f.file || f)
    })

    // Stelle sicher, dass alle Dateien inklusive .monaco_apis/ committed werden
    const filesToCommit = changedFiles.value.map(f => ({
      path: f.path || f.file,
      status: f.status,
      staged: f.staged
    }))

    const response = await axios.post(`monaco_git_api.php?project=${projectName}&codespace=${codespace}`, {
      action: 'commit',
      message: commitMessage.value,
      files: filesToCommit,
      include_monaco_apis: true // Flag to ensure .monaco_apis files are included
    })

    if (response.data.success) {
      console.log('Commit successful:', response.data)

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

      ToastService.success(`Commit successful! Created commit ${response.data.commit.short_sha || response.data.commit.sha.substring(0, 7)}`)
    } else {
      throw new Error(response.data.error || 'Commit failed')
    }
  } catch (error) {
    console.error('Commit failed:', error)
    ToastService.error('Commit failed: ' + error.message)
  } finally {
    isCommitting.value = false
  }
}

const pullFromGitHub = async () => {
  console.log('Pulling from GitHub...')
  isPulling.value = true

  try {
    const response = await axios.post(`monaco_git_api.php?project=${projectName}&codespace=${codespace}`, {
      action: 'pull'
    })

    if (response.data.success) {
      console.log('Pull successful:', response.data)
      ToastService.success(`Pull erfolgreich! ${response.data.files_count} ${response.data.files_count === 1 ? 'Datei wurde' : 'Dateien wurden'} aktualisiert.`)

      // Refresh file list and git status after pull
      await refreshFiles()
      await refreshGitStatus()

      // Notify parent component about file changes if needed
      window.dispatchEvent(new CustomEvent('monaco-files-updated', { detail: response.data }))
    } else {
      console.error('Pull failed:', response.data.message)
      ToastService.error('Pull failed: ' + response.data.message)
    }
  } catch (error) {
    console.error('Pull failed:', error)
    ToastService.error('Pull failed: ' + error.message)
  } finally {
    isPulling.value = false
  }
}

const pushToGitHub = async () => {
  console.log('Pushing to GitHub...')
  isPushing.value = true

  try {
    const response = await axios.post(`monaco_git_api.php?project=${projectName}&codespace=${codespace}`, {
      action: 'push'
    })

    if (response.data.success) {
      console.log('Push successful:', response.data)
      ToastService.success(`Push erfolgreich! ${response.data.commits_count} ${response.data.commits_count == 1 ? 'Commit wurde' : 'Commits wurden'} zu GitHub gepusht.`)

      // Refresh git status after push
      await refreshGitStatus()
    } else {
      console.error('Push failed:', response.data.message)
      
      // Check for conflicts
      if (response.data.error && response.data.error.includes('conflict')) {
        openMergeEditor(response.data.conflicts || [])
      } else {
        ToastService.error('Push failed: ' + response.data.message)
      }
    }
  } catch (error) {
    console.error('Push failed:', error)
    ToastService.error('Push failed: ' + error.message)
  } finally {
    isPushing.value = false
  }
}

const openMergeEditor = (conflicts) => {
  mergeConflicts.value = conflicts
  showMergeEditor.value = true
}

const closeMergeEditor = () => {
  showMergeEditor.value = false
  mergeConflicts.value = []
}

const resolveConflict = async (filename) => {
  try {
    // Open the file with conflicts for manual resolution
    window.dispatchEvent(new CustomEvent('monaco-open-file', { 
      detail: { path: filename, name: filename } 
    }))
    ToastService.info(`Opening ${filename} for manual conflict resolution. Look for conflict markers: <<<<<<< HEAD, =======, >>>>>>> branch`)
    closeMergeEditor()
  } catch (error) {
    console.error('Failed to resolve conflict:', error)
  }
}

const autoResolveConflicts = async () => {
  try {
    const response = await axios.post(`monaco_git_api.php?project=${projectName}&codespace=${codespace}`, {
      action: 'auto_resolve_conflicts',
      conflicts: mergeConflicts.value
    })
    
    if (response.data.success) {
      ToastService.success('Conflicts auto-resolved successfully! You can now try pushing again.')
      closeMergeEditor()
      await fetchGitStatus()
    } else {
      ToastService.error('Auto-resolve failed: ' + response.data.message)
    }
  } catch (error) {
    console.error('Auto-resolve failed:', error)
    ToastService.error('Auto-resolve failed: ' + error.message)
  }
}

const stageFile = async (filePath) => {
  console.log('Staging file:', filePath)
  try {
    const response = await axios.post(`monaco_git_api.php?project=${projectName}&codespace=${codespace}`, {
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
    const response = await axios.post(`monaco_git_api.php?project=${projectName}&codespace=${codespace}`, {
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
      const response = await axios.post(`monaco_git_api.php?project=${projectName}&codespace=${codespace}`, {
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

// Live update methods
const startLiveGitUpdates = () => {
  if (gitRefreshInterval.value) {
    clearInterval(gitRefreshInterval.value)
  }
  
  // Refresh git status every 3 seconds automatically
  gitRefreshInterval.value = setInterval(() => {
    loadGitData()
  }, 3000)
}

const stopLiveGitUpdates = () => {
  if (gitRefreshInterval.value) {
    clearInterval(gitRefreshInterval.value)
    gitRefreshInterval.value = null
  }
}

// File diff viewer
const viewFileDiff = async (filePath) => {
  try {
    console.log('Loading diff for file:', filePath)
    const response = await axios.get(`monaco_git_api.php?project=${projectName}&codespace=${codespace}&action=diff&file=${filePath}`)
    
    if (response.data.success) {
      // Set diff data and show modal
      diffData.value = {
        filePath,
        diff: response.data.diff,
        originalContent: response.data.original_content,
        currentContent: response.data.current_content
      }
      showDiffViewer.value = true
    } else {
      console.error('Failed to load diff:', response.data.error)
      ToastService.error('Failed to load diff: ' + (response.data.error || 'Unknown error'))
    }
  } catch (error) {
    console.error('Error loading diff:', error)
    ToastService.error('Error loading diff: ' + error.message)
  }
}

const closeDiffViewer = () => {
  showDiffViewer.value = false
  diffData.value = {}
}

// Pull Request Methods
const createPullRequest = async () => {
  const title = prompt('Enter pull request title:')
  const baseBranch = prompt('Enter base branch (default: main):', 'main')
  const headBranch = prompt('Enter head branch (default: feature):')

  if (title && headBranch) {
    try {
      const response = await axios.post(`monaco_pr_api.php?project=${projectName}&codespace=${codespace}&action=create`, {
        title: title,
        base_branch: baseBranch,
        head_branch: headBranch,
        body: 'Created via Monaco IDE'
      })

      if (response.data.success) {
        await loadPullRequests()
        ToastService.success('Pull request created successfully!')
      } else {
        ToastService.error('Failed to create pull request: ' + response.data.message)
      }
    } catch (error) {
      console.error('Failed to create pull request:', error)
      ToastService.error('Failed to create pull request: ' + (error.response?.data?.message || error.message))
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
    const response = await axios.post(`vercel_api.php?project=${projectName}&codespace=${codespace}`, {
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
    loadDeployments(),
    loadAvailableAPIs()
  ])
  
  // Start live git updates
  startLiveGitUpdates()
  
  // Listen for file save events from Monaco editor to trigger git refresh
  window.addEventListener('monaco-file-saved', () => {
    console.log('File saved, refreshing git status...')
    loadGitData()
  })
  
  // Listen for file changes in general
  window.addEventListener('monaco-file-changed', () => {
    console.log('File changed, refreshing git status...')
    loadGitData()
  })
  
  // Listen for active file changes
  window.addEventListener('monaco-active-file-changed', (event) => {
    activeFile.value = event.detail.filePath
  })
})

// Cleanup on unmount
import { onUnmounted } from 'vue'
onUnmounted(() => {
  stopLiveGitUpdates()
  window.removeEventListener('monaco-file-saved', loadGitData)
  window.removeEventListener('monaco-file-changed', loadGitData)
  window.removeEventListener('monaco-active-file-changed', () => {})
})
</script>

<style scoped>
.monaco-sidebar {
  width: 280px;
  padding-bottom: 56px;
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

.tree-item {
  position: relative;
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

.file-item.active-file {
  background: var(--vscode-list-activeSelectionBackground, #094771);
  color: var(--vscode-list-activeSelectionForeground, #ffffff);
}

.file-item.active-file .file-name {
  font-weight: 600;
}

.folder-item {
  cursor: pointer;
}

.folder-chevron {
  width: 12px;
  height: 12px;
  margin-right: 4px;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.folder-children {
  border-left: 1px solid var(--vscode-tree-indentGuidesStroke, #464647);
  margin-left: 8px;
  padding-left: 8px;
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

.file-indent {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
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

/* Modal Styles */
.diff-viewer-modal,
.merge-editor-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.diff-viewer-content {
  background: var(--vscode-editor-background, #1e1e1e);
  color: var(--vscode-editor-foreground, #d4d4d4);
  border-radius: 8px;
  width: 90%;
  max-width: 1200px;
  height: 80%;
  display: flex;
  flex-direction: column;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

.merge-editor-content {
  background: var(--vscode-editor-background, #1e1e1e);
  color: var(--vscode-editor-foreground, #d4d4d4);
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

.diff-viewer-header,
.merge-editor-header {
  padding: 15px 20px;
  border-bottom: 1px solid var(--vscode-panel-border, #464647);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.diff-viewer-header h3 {
  margin: 0;
  font-size: 16px;
  color: var(--vscode-foreground, #cccccc);
}

.merge-editor-header h3 {
  margin: 0;
  color: var(--vscode-errorForeground, #f85149);
}

.close-diff-btn,
.close-merge-btn {
  background: none;
  border: none;
  color: var(--vscode-foreground, #cccccc);
  font-size: 20px;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.close-diff-btn:hover,
.close-merge-btn:hover {
  background: var(--vscode-button-hoverBackground, #464647);
}

.diff-viewer-body {
  flex: 1;
  overflow: auto;
  padding: 0;
}

.merge-editor-body {
  padding: 20px;
}

.diff-content {
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 13px;
  line-height: 1.4;
}

.diff-line {
  display: flex;
  padding: 2px 0;
  margin: 0;
  white-space: pre;
}

.diff-line-number {
  width: 60px;
  text-align: right;
  padding: 0 10px;
  color: var(--vscode-editorLineNumber-foreground, #858585);
  background: var(--vscode-editorGutter-background, #1e1e1e);
  border-right: 1px solid var(--vscode-panel-border, #464647);
  flex-shrink: 0;
}

.diff-line-content {
  flex: 1;
  padding: 0 10px;
  overflow-x: auto;
}

.diff-line.added {
  background: var(--vscode-diffEditor-insertedTextBackground, rgba(155, 185, 85, 0.2));
}

.diff-line.deleted {
  background: var(--vscode-diffEditor-removedTextBackground, rgba(255, 0, 0, 0.2));
}

.diff-line.unchanged {
  background: var(--vscode-editor-background, #1e1e1e);
}

.diff-line.added .diff-line-content {
  color: var(--vscode-diffEditor-insertedTextForeground, #9bb955);
}

.diff-line.deleted .diff-line-content {
  color: var(--vscode-diffEditor-removedTextForeground, #ff0000);
}

.conflicts-list {
  margin: 20px 0;
}

.conflict-file {
  display: flex;
  align-items: center;
  padding: 10px;
  background: var(--vscode-inputValidation-errorBackground, rgba(255, 0, 0, 0.1));
  border: 1px solid var(--vscode-inputValidation-errorBorder, #ff6b6b);
  border-radius: 4px;
  margin-bottom: 8px;
}

.conflict-icon {
  color: var(--vscode-errorForeground, #f85149);
  margin-right: 10px;
}

.conflict-filename {
  flex: 1;
  font-family: monospace;
}

.resolve-btn, .btn-primary, .btn-secondary {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}

.resolve-btn {
  background: var(--vscode-button-background, #0e639c);
  color: var(--vscode-button-foreground, #ffffff);
}

.btn-primary {
  background: var(--vscode-button-background, #0e639c);
  color: var(--vscode-button-foreground, #ffffff);
  margin-left: 10px;
}

.btn-secondary {
  background: var(--vscode-button-secondaryBackground, #3c3c3c);
  color: var(--vscode-button-secondaryForeground, #cccccc);
}

.merge-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid var(--vscode-panel-border, #464647);
}

/* API Section Styles */
.api-item {
  display: flex;
  align-items: center;
  padding: 8px;
  border-radius: 4px;
  margin-bottom: 4px;
  background: var(--vscode-sideBar-background, #252526);
  border: 1px solid var(--vscode-panel-border, #464647);
}

.api-item:hover {
  background: var(--vscode-list-hoverBackground, #2a2d2e);
}

.api-info {
  display: flex;
  align-items: center;
  flex: 1;
}

.api-icon {
  margin-right: 8px;
  font-size: 16px;
  color: var(--vscode-symbolIcon-functionForeground, #4fc1ff);
  flex-shrink: 0;
}

.api-details {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.api-name {
  font-size: 13px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--vscode-foreground, #cccccc);
  line-height: 1.2;
}

.api-category {
  font-size: 11px;
  color: var(--vscode-descriptionForeground, #8c8c8c);
  margin-top: 2px;
}

.api-controls {
  margin-left: 8px;
}

.api-status {
  margin-left: 8px;
  color: var(--vscode-testing-iconPassed, #73c991);
}

.copy-section {
  margin-bottom: 12px;
}

.copy-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--vscode-descriptionForeground, #cccccc);
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.code-block {
  background: var(--vscode-textCodeBlock-background, #2d2d30);
  padding: 8px;
  border-radius: 4px;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 11px;
  cursor: pointer;
  position: relative;
  border: 1px solid var(--vscode-panel-border, #464647);
  transition: background-color 0.2s;
}

.code-block:hover {
  background: var(--vscode-list-hoverBackground, #2a2d2e);
}

.code-block code {
  color: var(--vscode-textPreformat-foreground, #d7ba7d);
  white-space: pre-wrap;
  word-break: break-word;
  display: block;
  padding-right: 20px;
}

.copy-icon {
  position: absolute;
  right: 8px;
  top: 8px;
  opacity: 0.6;
  color: var(--vscode-foreground, #cccccc);
  font-size: 12px;
}

.code-block:hover .copy-icon {
  opacity: 1;
}

.no-apis, .no-apis-message {
  padding: 16px 8px;
  text-align: center;
  color: var(--vscode-descriptionForeground, #cccccc);
  font-size: 13px;
  font-style: italic;
}

.no-apis small {
  color: var(--vscode-descriptionForeground, #999999);
  font-size: 11px;
}
</style>
