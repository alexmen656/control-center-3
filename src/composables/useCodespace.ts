import { ref, computed } from 'vue'
import axios from 'axios'
import type { RouteParams } from 'vue-router'

export interface CodespaceFile {
  name: string
  path: string
  type: 'file' | 'directory'
  size?: number
  modified?: number
  children?: CodespaceFile[]
}

export interface CodespaceInfo {
  id: number
  name: string
  slug: string
  description: string
  icon: string
  language: string
  template: string
  status: 'active' | 'inactive' | 'archived'
  created_at: string
  updated_at: string
  order_index: number
}

export function useCodespace(routeParams: RouteParams) {
  const projectName = computed(() => routeParams.project as string || 'default-project')
  const codespaceName = computed(() => routeParams.codespace as string || 'main')
  
  const files = ref<CodespaceFile[]>([])
  const codespaceInfo = ref<CodespaceInfo | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const apiCall = async (action: string, data: any = {}) => {
    try {
      const response = await axios.post('/backend/monaco_codespace_api.php', {
        action,
        project: projectName.value,
        codespace: codespaceName.value,
        ...data
      })
      
      if (response.data.success) {
        return response.data
      } else {
        throw new Error(response.data.error || 'Unknown error')
      }
    } catch (err: any) {
      error.value = err.message || 'Request failed'
      throw err
    }
  }

  const loadFiles = async () => {
    loading.value = true
    try {
      const response = await apiCall('list_files')
      files.value = response.files || []
    } catch (err) {
      console.error('Failed to load files:', err)
    } finally {
      loading.value = false
    }
  }

  const loadFile = async (filename: string): Promise<string> => {
    const response = await apiCall('load_file', { filename })
    return response.content || ''
  }

  const saveFile = async (filename: string, content: string): Promise<void> => {
    await apiCall('save_file', { filename, content })
  }

  const createFile = async (filename: string, content: string = ''): Promise<void> => {
    await apiCall('create_file', { filename, content })
    await loadFiles() // Refresh file list
  }

  const deleteFile = async (filename: string): Promise<void> => {
    await apiCall('delete_file', { filename })
    await loadFiles() // Refresh file list
  }

  const loadCodespaceInfo = async () => {
    try {
      const response = await apiCall('get_codespace_info')
      codespaceInfo.value = response.codespace
    } catch (err) {
      console.error('Failed to load codespace info:', err)
    }
  }

  const init = async () => {
    await Promise.all([
      loadFiles(),
      loadCodespaceInfo()
    ])
  }

  return {
    // State
    projectName,
    codespaceName,
    files,
    codespaceInfo,
    loading,
    error,
    
    // Methods
    loadFiles,
    loadFile,
    saveFile,
    createFile,
    deleteFile,
    loadCodespaceInfo,
    init,
    apiCall
  }
}
