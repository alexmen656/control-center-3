<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle v-if="true" icon="folder-outline" title="File System" />
      <div class="page-container">
        <div class="page-header">
          <div class="header-content">
            <PageTitle icon="folder-outline" title="Files" />
          </div>
          <div class="header-actions">
            <div class="folder-creation-bar" style="margin-right: 12px;">
              <input type="text" v-model="newFolderName" placeholder="New folder..." class="folder-input"
                @keyup.enter="createFolder" />
              <button class="action-btn icon-only secondary" @click="createFolder" :disabled="!newFolderName.trim()"
                title="Create Folder">
                <ion-icon name="add-outline"></ion-icon>
              </button>
            </div>

            <button class="action-btn secondary" @click="refreshFiles">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
            <button class="action-btn primary" @click="showUploadArea">
              <ion-icon name="cloud-upload-outline"></ion-icon>
              Upload Files
            </button>
          </div>
        </div>
        <div class="stats-grid">
          <StatCard icon="folder-outline" color="primary" :value="folderCount" label="Folders" />
          <StatCard icon="document-outline" color="info" :value="fileCount" label="Files" />
          <StatCard icon="cloud-upload-outline" color="success"
            :value="uploadPercentage > 0 ? uploadPercentage + '%' : 'Idle'" label="Upload Status" />
        </div>


        <div v-if="showUpload" class="upload-card">
          <div class="upload-header">
            <h4>Upload Files to {{ currentFolderName }}</h4>
            <button class="close-upload-btn" @click="showUpload = false">
              <ion-icon name="close"></ion-icon>
            </button>
          </div>

          <div class="drop-zone" :class="{ 'drag-over': isDragOver }" @dragover.prevent="handleDragOver"
            @drop="handleDrop" @dragenter.prevent="handleDragEnter" @dragleave="handleDragLeave">
            <form ref="fileform" style="display: none;"></form>
            <ion-icon name="cloud-upload-outline" class="upload-icon"></ion-icon>
            <p class="upload-text">Drop files here or click to select</p>
            <input type="file" multiple @change="handleFileSelect" style="display: none;" ref="fileInput">
            <button class="select-files-btn" @click="$refs.fileInput.click()">
              Select Files
            </button>
          </div>

          <div v-if="uploadPercentage > 0" class="upload-progress">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: uploadPercentage + '%' }"></div>
            </div>
            <span class="progress-text">{{ uploadPercentage }}%</span>
          </div>
          <div v-if="files.length > 0" class="file-preview-list">
            <h5>Selected Files:</h5>
            <div v-for="(file, index) in files" :key="index" class="file-preview-item">
              <ion-icon name="document-outline"></ion-icon>
              <span class="file-name">{{ file.name }}</span>
              <button class="remove-file-btn" @click="removeFile(index)">
                <ion-icon name="close"></ion-icon>
              </button>
            </div>
            <button class="action-btn primary full-width" @click="submit">Upload Now</button>
          </div>
        </div>
        <div class="data-card" :class="{ 'drag-over-card': isDragOver }" @dragover.prevent="handleDragOver"
          @drop="handleDrop" @dragenter.prevent="handleDragEnter" @dragleave="handleDragLeave">
          <div class="card-header">
            <div class="header-left-group">
              <div class="header-title-row">
                <h3>{{ currentFolderName }}</h3>
                <span class="entry-count">{{ displayedItems.length }} item{{ displayedItems.length !== 1 ? 's' : ''
                  }}</span>
              </div>
              <div class="header-breadcrumbs">
                <div v-for="(crumb, index) in breadcrumbs" :key="crumb.id" class="breadcrumb-item small"
                  :class="{ 'active': index === breadcrumbs.length - 1 }" @click="navigateToFolder(crumb.id)"
                  @dragover.prevent="handleBreadcrumbDragOver" @dragleave.prevent="handleBreadcrumbDragLeave"
                  @drop="e => handleBreadcrumbDrop(e, crumb.id)">
                  <ion-icon :name="index === 0 ? 'home-outline' : 'folder-open-outline'"></ion-icon>
                  <span>{{ crumb.name }}</span>
                  <ion-icon v-if="index < breadcrumbs.length - 1" name="chevron-forward-outline"
                    class="separator"></ion-icon>
                </div>
              </div>
            </div>

            <div class="header-right-group">
              <div class="search-box">
                <ion-icon name="search-outline"></ion-icon>
                <input type="text" placeholder="Search files..." v-model="searchTerm">
              </div>
              <div class="view-toggle">
                <button class="action-btn icon-only" :class="{ 'active': viewMode === 'grid' }"
                  @click="viewMode = 'grid'" title="Grid View">
                  <ion-icon name="grid-outline"></ion-icon>
                </button>
                <button class="action-btn icon-only" :class="{ 'active': viewMode === 'list' }"
                  @click="viewMode = 'list'" title="List View">
                  <ion-icon name="list-outline"></ion-icon>
                </button>
              </div>
            </div>
          </div>

          <div v-if="displayedItems.length === 0" class="no-data-state" @dragover.prevent="handleDragOver"
            @drop="handleDrop">
            <div class="no-data-content">
              <ion-icon name="folder-open-outline" class="no-data-icon"></ion-icon>
              <h4>Folder is empty</h4>
              <p v-if="searchTerm">No files match your search criteria</p>
              <p v-else>Drag files here or upload to populate this folder</p>
            </div>
          </div>

          <div v-if="viewMode === 'grid' && displayedItems.length > 0" class="files-wrapper">
            <div class="files-grid" @dragover.prevent="handleDragOver" @drop="handleDrop"
              @dragenter.prevent="handleDragEnter" @dragleave="handleDragLeave">

              <div v-for="item in displayedItems" :key="item.id" class="file-card-container">
                <div class="file-card" :class="{
                  'is-folder': item.type === 'folder',
                  'is-image': item.type === 'file' && isImageFile(item.name),
                  'drag-over': item.isDragOver
                }" @click="handleItemClick(item)" @dragover.prevent="handleDragOver"
                  @dragenter.prevent="e => handleItemDragEnter(e, item)"
                  @dragleave.prevent="e => handleItemDragLeave(e, item)" @drop="e => handleItemDrop(e, item)"
                  :draggable="true" @dragstart="e => handleDragStart(e, item)">
                  <div class="file-card-content">
                    <img v-if="item.type === 'file' && isImageFile(item.name) && getSignedImageUrl(item.location)"
                      :src="getSignedImageUrl(item.location)" class="file-preview-image" @error="onImageError"
                      @load="onImageLoad" />
                    <ion-icon v-else :name="getFileIcon(item)" class="file-icon" />
                    <div class="file-info">
                      <span class="file-name">{{ shortenName(item.name) }}</span>
                      <span v-if="item.type === 'folder' && item.children" class="file-meta">
                        {{ item.children.length }} items
                      </span>
                      <span v-else-if="item.type === 'file'" class="file-meta">
                        {{ getFileExtension(item.name) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="viewMode === 'list' && displayedItems.length > 0" class="table-wrapper"
            @dragover.prevent="handleDragOver" @drop="handleDrop">
            <div class="modern-table">
              <div class="table-header">
                <div class="header-cell" style="flex: 0 0 60px;">Type</div>
                <div class="header-cell" style="flex: 2;">Name</div>
                <div class="header-cell" style="flex: 1;">Details</div>
                <div class="header-cell actions-header" style="flex: 0 0 100px;">Actions</div>
              </div>
              <div class="table-body">
                <div v-for="item in displayedItems" :key="item.id" class="table-row" @click="handleItemClick(item)"
                  :draggable="true" @dragstart="e => handleDragStart(e, item)" @dragover.prevent="handleDragOver"
                  @dragenter.prevent="e => handleItemDragEnter(e, item)"
                  @dragleave.prevent="e => handleItemDragLeave(e, item)" @drop="e => handleItemDrop(e, item)"
                  :class="{ 'drag-over': item.isDragOver }">
                  <div class="table-cell" style="flex: 0 0 60px;">
                    <ion-icon :name="getFileIcon(item)" class="list-icon"></ion-icon>
                  </div>
                  <div class="table-cell" style="flex: 2;">
                    <span class="cell-content name-cell">{{ item.name }}</span>
                  </div>
                  <div class="table-cell" style="flex: 1;">
                    <span v-if="item.type === 'folder'" class="cell-content text-secondary">{{ item.children ?
                      item.children.length : 0 }} items</span>
                    <span v-else class="cell-content text-secondary">{{ getFileExtension(item.name).toUpperCase()
                      }}</span>
                  </div>
                  <div class="table-cell actions-cell" style="flex: 0 0 100px;" @click.stop>
                    <div class="action-buttons">
                      <button class="icon-btn edit-btn" title="More">
                        <ion-icon name="ellipsis-vertical"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <ion-modal :is-open="imagePreviewOpen" @did-dismiss="closeImagePreview" class="image-preview-modal">
          <ion-header>
            <ion-toolbar>
              <ion-title>{{ previewImageName }}</ion-title>
              <ion-buttons slot="end">
                <ion-button @click="closeImagePreview">
                  <ion-icon name="close" />
                </ion-button>
              </ion-buttons>
            </ion-toolbar>
          </ion-header>
          <ion-content class="image-preview-content">
            <div class="image-container">
              <div v-if="!imageLoaded && !imageError" class="loading-spinner">
                <ion-spinner></ion-spinner>
                <p>Loading image...</p>
              </div>
              <div v-if="imageError" class="error-message">
                <ion-icon name="image-outline"></ion-icon>
                <p>Failed to load image</p>
              </div>
              <img v-if="previewImageUrl" :src="previewImageUrl" @load="onImageLoad" @error="onImageError"
                class="preview-image" :style="{ display: imageLoaded ? 'block' : 'none' }" />
            </div>
            <h3 class="preview-title">{{ previewImageName }}</h3>
          </ion-content>
        </ion-modal>

      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import {
  IonPage,
  IonContent,
  IonIcon,
  IonModal,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonButtons,
  IonButton,
  IonSpinner
} from "@ionic/vue";
import SiteTitle from "@/components/SiteTitle.vue";
import PageTitle from "@/components/PageTitle.vue";
import StatCard from "@/components/StatCard.vue";
import axios from "axios";

export default defineComponent({
  name: "ProjectFileSystem",
  components: {
    PageTitle,
    StatCard,
    IonPage,
    IonContent,
    IonIcon,
    IonModal,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonButtons,
    IonButton,
    IonSpinner,
    SiteTitle,
  },
  data() {
    return {
      name: "",
      code: "",
      dragAndDropCapable: false,
      files: [],
      uploadPercentage: 0,

      // New Data Structure
      fileSystem: [], // This will be the children of root
      rootId: null,
      currentFolderId: null,
      projectID: null,

      newFolderName: "",
      imageStatus: {},
      signedUrls: {},

      // UI state
      viewMode: 'grid', // 'grid' | 'list'
      showUpload: false,
      searchTerm: '',
      isDragOver: false, // For main area

      // Image preview data
      imagePreviewOpen: false,
      previewImageUrl: "",
      previewImageName: "",
      imageLoaded: false,
      imageError: false,
    };
  },
  computed: {
    fileCount() {
      // Flatten recursively to count files
      const countFiles = (items) => {
        let count = 0;
        items.forEach(item => {
          if (item.type === 'file') count++;
          if (item.children) count += countFiles(item.children);
        });
        return count;
      };
      return countFiles(this.fileSystem);
    },
    folderCount() {
      const countFolders = (items) => {
        let count = 0;
        items.forEach(item => {
          if (item.type === 'folder') count++;
          if (item.children) count += countFolders(item.children);
        });
        return count;
      };
      return countFolders(this.fileSystem);
    },
    breadcrumbs() {
      if (this.currentFolderId === null || this.rootId === null) return [];

      const rootCrumb = { id: this.rootId, name: 'Home' };

      if (this.currentFolderId === this.rootId) {
        return [rootCrumb];
      }

      const path = this.findPath(this.fileSystem, this.currentFolderId);
      if (path) {
        return [rootCrumb, ...path];
      }

      return [rootCrumb]; // Fallback
    },

    currentFolderName() {
      if (this.currentFolderId === this.rootId) return 'Home';
      const crumbs = this.breadcrumbs;
      return crumbs.length > 0 ? crumbs[crumbs.length - 1].name : 'Unknown';
    },

    displayedItems() {
      let items = [];
      if (this.currentFolderId === this.rootId) {
        items = this.fileSystem;
      } else {
        // Find current folder
        const folder = this.findItemById(this.fileSystem, this.currentFolderId);
        items = folder && folder.children ? folder.children : [];
      }

      // Filter by search
      if (this.searchTerm.trim()) {
        const lowerTerm = this.searchTerm.toLowerCase();
        return items.filter(item => item.name.toLowerCase().includes(lowerTerm));
      }
      return items;
    }
  },
  mounted() {
    this.dragAndDropCapable = this.determineDragAndDropCapable();
    if (this.dragAndDropCapable) {
      // Setup global drag listeners if needed, mostly handled by local events
    }
    this.fetchFileSystemData();
  },
  methods: {
    findPath(items, targetId) {
      for (const item of items) {
        if (item.id === targetId) {
          return [{ id: item.id, name: item.name }];
        }
        if (item.children) {
          const subPath = this.findPath(item.children, targetId);
          if (subPath) {
            return [{ id: item.id, name: item.name }, ...subPath];
          }
        }
      }
      return null;
    },

    findItemById(items, id) {
      for (const item of items) {
        if (item.id === id) return item;
        if (item.children) {
          const found = this.findItemById(item.children, id);
          if (found) return found;
        }
      }
      return null;
    },

    shortenName(name) {
      if (name.length > 18) {
        return name.slice(0, 8) + "..." + name.slice(-7);
      }
      return name;
    },

    getFileIcon(item) {
      if (item.type === 'folder') {
        return 'folder';
      }

      const ext = this.getFileExtension(item.name).toLowerCase();
      const iconMap = {
        'js': 'logo-javascript', 'ts': 'logo-javascript', 'vue': 'logo-vue',
        'php': 'code-outline', 'html': 'logo-html5', 'css': 'logo-css3',
        'json': 'code-outline', 'md': 'document-text-outline',
        'pdf': 'document-outline', 'zip': 'archive-outline'
      };
      return iconMap[ext] || 'document-outline';
    },

    getFileExtension(filename) {
      return filename.split('.').pop() || '';
    },

    isImageFile(filename) {
      return /\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i.test(filename);
    },

    async fetchFileSystemData() {
      try {
        const response = await axios.get(`v2/filesystem/?project=${this.$route.params.project}`);
        console.log('File system data:', response.data);

        if (response.data) {
          this.rootId = response.data.rootId;
          this.fileSystem = this.processFileSystemData(response.data.items);

          if (this.currentFolderId === null) {
            this.currentFolderId = this.rootId;
          }

          this.loadSignedUrlsForImages();
        }
      } catch (error) {
        console.error("Error fetching file system data:", error);
      }
    },

    processFileSystemData(items) {
      return items.map(item => {
        const processedItem = { ...item };
        processedItem.isDragOver = false;

        if (processedItem.projectID && !this.projectID) {
          this.projectID = processedItem.projectID;
        }

        if (item.type === 'folder' && item.children) {
          processedItem.children = this.processFileSystemData(item.children);
        }
        return processedItem;
      });
    },

    async loadSignedUrlsForImages() {
      const imageFiles = [];
      const collectImages = (items) => {
        items.forEach(item => {
          if (item.type === 'file' && this.isImageFile(item.name)) {
            imageFiles.push({
              path: item.location,
              location: item.location,
              projectID: this.projectID || item.projectID
            });
          }
          if (item.type === 'folder' && item.children) {
            collectImages(item.children);
          }
        });
      };

      collectImages(this.fileSystem);
      if (imageFiles.length === 0) return;

      try {
        const response = await axios.post('signed_url_generator.php', {
          files: imageFiles,
          validitySeconds: 3600
        });

        if (response.data.success) {
          response.data.urls.forEach(item => {
            this.signedUrls[item.originalPath] = item.signedUrl;
          });
        }
      } catch (error) {
        console.error('Error loading signed URLs:', error);
      }
    },

    getSignedImageUrl(location) {
      return this.signedUrls[location] || '';
    },

    navigateToFolder(folderId) {
      this.currentFolderId = folderId;
      this.searchTerm = '';
    },

    handleItemClick(item) {
      if (item.type === 'folder') {
        this.navigateToFolder(item.id);
      } else if (item.type === 'file' && this.isImageFile(item.name)) {
        this.previewImage(item);
      }
    },

    createFolder() {
      if (this.newFolderName.trim() !== "") {
        const formData = new FormData();
        formData.append("name", this.newFolderName);
        formData.append("parentId", this.currentFolderId);
        formData.append("project", this.$route.params.project);

        axios.post("v2/filesystem/folder", formData).then(() => {
          this.fetchFileSystemData();
          this.newFolderName = "";
        }).catch((err) => {
          console.log("Error creating folder:", err);
        });
      }
    },

    refreshFiles() {
      this.fetchFileSystemData();
    },

    showUploadArea() {
      this.showUpload = true;
    },

    handleFileSelect(event) {
      this.files.push(...event.target.files);
    },
    removeFile(index) {
      this.files.splice(index, 1);
    },
    submit() {
      if (this.files.length > 0) {
        const formData = new FormData();
        for (let i = 0; i < this.files.length; i++) {
          formData.append("files[" + i + "]", this.files[i]);
          formData.append("name", this.files[i].name);
        }
        formData.append("parentId", this.currentFolderId);
        formData.append("project", this.$route.params.project);

        this.$axios.post("v2/filesystem/upload", formData, {
          headers: { "Content-Type": "multipart/form-data" },
          onUploadProgress: (progressEvent) => {
            this.uploadPercentage = parseInt(Math.round((progressEvent.loaded * 100) / progressEvent.total));
          }
        })
          .then(() => {
            this.files = [];
            this.uploadPercentage = 0;
            this.showUpload = false;
            this.fetchFileSystemData();
          })
          .catch((err) => console.log("FAILURE!!", err));
      }
    },

    // ---- Drag and Drop ----
    determineDragAndDropCapable() {
      // Simple check
      return (('draggable' in document.createElement('span')) && ('FileReader' in window));
    },

    handleDragStart(event, item) {
      event.dataTransfer.setData('application/json', JSON.stringify({
        type: 'existing-file',
        id: item.id,
        isFolder: item.type === 'folder'
      }));
      event.dataTransfer.effectAllowed = 'move';
    },

    handleDragEnter(event) {
      event.preventDefault();
      this.isDragOver = true;
    },
    handleDragLeave(event) {
      event.preventDefault();
      this.isDragOver = false;
    },
    handleDragOver(event) {
      event.preventDefault();
    },
    handleDrop(event) {
      event.preventDefault();
      this.isDragOver = false;

      const dragData = event.dataTransfer.getData('application/json');

      if (dragData) {
        // Internal move logic...
      } else if (event.dataTransfer.files.length > 0) {
        // External upload - Immediate
        this.files.push(...event.dataTransfer.files);
        // this.showUpload = true; // Disable UI opening
        this.submit(); // Immediate upload
      }
    },

    handleItemDragEnter(event, item) {
      if (item.type === 'folder') item.isDragOver = true;
    },
    handleItemDragLeave(event, item) {
      if (item.type === 'folder') item.isDragOver = false;
    },
    handleItemDrop(event, targetFolder) {
      if (targetFolder.type !== 'folder') return;
      event.preventDefault();
      event.stopPropagation();
      targetFolder.isDragOver = false;

      const dragData = event.dataTransfer.getData('application/json');
      if (dragData) {
        const data = JSON.parse(dragData);
        if (data.id === targetFolder.id) return; // Can't move into self
        this.moveItem(data.id, targetFolder.id);
      } else if (event.dataTransfer.files.length > 0) {
        // Upload into this folder
        // We should switch state to upload files to THAT folder, or just add them to queue with that parent?
        // Current upload logic uses `currentFolderId`.
        // Let's simpler: enter the folder then upload.
        // Or: just upload to current folder.
        // For now, let's treat drop-on-folder for files as 'upload to current opened folder' to be safe, 
        // or we need to change how submit works to accept explicit parent.
        // I'll stick to 'Drop on background' -> Upload to Current.
        // Drop on Folder -> Should probably upload to THAT folder, but it's complex UI.
        // Let's just focus on internal move for now.
      }
    },

    // Breadcrumb Drop (Move to parent)
    handleBreadcrumbDragOver(event) {
      event.preventDefault();
      event.dataTransfer.dropEffect = 'move';
    },
    handleBreadcrumbDragLeave(event) {
      event.preventDefault();
    },
    handleBreadcrumbDrop(event, targetId) {
      event.preventDefault();
      const dragData = event.dataTransfer.getData('application/json');
      if (dragData) {
        const data = JSON.parse(dragData);
        if (this.currentFolderId === targetId && !data.id) return; // Already here
        if (data.id === targetId) return; // Self

        this.moveItem(data.id, targetId);
      }
    },

    // ---- Operations ----
    async moveItem(sourceId, targetFolderId) {
      const formData = new FormData();
      formData.append('action', 'move');
      formData.append('sourceId', sourceId);
      formData.append('targetFolderId', targetFolderId);
      formData.append('project', this.$route.params.project);

      try {
        const response = await axios.post('v2/filesystem/move', formData);
        if (response.data.success) {
          this.fetchFileSystemData();
        }
      } catch (e) { console.error(e); }
    },

    async generateSignedUrl(filePath) {
      try {
        const payload = {
          path: filePath,
          projectID: this.projectID,
          validitySeconds: 3600
        };
        const response = await axios.post('signed_url_generator.php', payload);
        return response.data.success ? response.data.url : null;
      } catch (error) { return null; }
    },

    async previewImage(file) {
      this.imagePreviewOpen = true;
      this.previewImageName = file.name;
      this.imageLoaded = false;
      this.imageError = false;

      const signedUrl = await this.generateSignedUrl(file.location);
      if (signedUrl) {
        this.previewImageUrl = signedUrl;
      } else {
        this.imageError = true;
      }
    },
    closeImagePreview() {
      this.imagePreviewOpen = false;
      this.previewImageUrl = '';
    },
    onImageLoad() { this.imageLoaded = true; },
    onImageError() { this.imageError = true; this.imageLoaded = false; },
  },
});
</script>


<style scoped>
/* Modern Design System */
.modern-content {
  --primary-color: #f97316;
  --primary-hover: #ea580c;
  --secondary-color: #64748b;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-content h1 {
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  color: var(--text-primary);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text-primary);
}

.action-btn.icon-only {
  padding: 8px;
  width: 36px;
  height: 36px;
}

.action-btn.active {
  background: var(--surface-color);
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.action-btn:hover {
  background: var(--surface-color);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
}

.action-btn.small {
  padding: 4px 10px;
  font-size: 13px;
  height: 32px;
}

/* Stats (Matching ManageUsers) */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

/* Navigation Bar */
.navigation-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 16px;
  flex-wrap: wrap;
}

.breadcrumbs {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow-x: auto;
  white-space: nowrap;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  color: var(--text-secondary);
  font-size: 14px;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background 0.2s;
}

.breadcrumb-item:hover {
  background: var(--surface-color);
  color: var(--primary-color);
}

.breadcrumb-item.active {
  font-weight: 600;
  color: var(--text-primary);
  cursor: default;
}

.separator {
  color: var(--text-muted);
  font-size: 12px;
  margin-left: 4px;
}

/* Folder Creation - Updated location styles */
.folder-creation-bar {
  display: flex;
  gap: 8px;
  align-items: center;
}

.folder-input {
  padding: 0 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  width: 180px;
  height: 38px;
  /* Match button height */
  background: var(--surface);
  color: var(--text-primary);
  transition: border-color 0.2s;
}

.folder-input:focus {
  outline: none;
  border-color: var(--primary-color);
}

.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-height: 400px;
  margin-bottom: 24px;
}

.card-header {
  padding: 16px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: var(--background);
  flex-wrap: wrap;
  gap: 16px;
}

.header-left-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
  min-width: 250px;
}

.header-title-row {
  display: flex;
  align-items: baseline;
  gap: 12px;
}

.header-title-row h3 {
  margin: 0;
  font-size: 22px;
  font-weight: 600;
  color: var(--text-primary);
}

.entry-count {
  font-size: 13px;
  color: var(--text-secondary);
}

.header-breadcrumbs {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
}

.breadcrumb-item.small {
  padding: 2px 6px;
  font-size: 12px;
  background: transparent;
  border: none;
  box-shadow: none;
  color: var(--text-secondary);
  border-radius: 4px;
}

.breadcrumb-item.small:hover {
  background: rgba(0, 0, 0, 0.05);
  color: var(--primary-color);
}

.breadcrumb-item.small.active {
  font-weight: 600;
  color: var(--text-primary);
  background: transparent;
}

.header-right-group {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* Inline Folder Creation */
.folder-creation-inline {
  display: flex;
  align-items: center;
  gap: 6px;
}

.folder-input.small {
  padding: 6px 10px;
  height: 34px;
  font-size: 13px;
  min-width: 140px;
}

.action-btn.small-btn {
  width: 34px;
  height: 34px;
  padding: 6px;
}

/* Card Drop Zone */
.data-card {
  position: relative;
  transition: box-shadow 0.2s, border-color 0.2s;
}

.data-card.drag-over-card {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.2);
  /* Maybe a background tint? */
}

/* Ensure inner wrappers don't block drop if they are effectively the same zone */
/* But we added drop handlers to card, so bubbling should work or capture */

/* Search Box matching ManageUsers */
.search-box {
  position: relative;
  width: 300px;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  font-size: 18px;
  pointer-events: none;
}

.search-box input {
  width: 100%;
  padding: 10px 16px 10px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text-primary);
  background: var(--surface);
  transition: all 0.2s;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
}

.files-wrapper {
  padding: 24px;
  flex: 1;
}

.files-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 16px;
}

.file-card-container {
  position: relative;
}

.file-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  height: 140px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.file-card:hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.file-card.drag-over {
  background: #fff7ed;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px var(--primary-color);
}

.file-card-content {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
}

.file-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin: 12px 0;
}

.file-card.is-folder .file-icon {
  color: #fbbf24;
}

.file-preview-image {
  width: 100%;
  height: 80px;
  object-fit: cover;
  border-radius: 4px;
  margin-bottom: 8px;
}

.file-info {
  font-size: 13px;
  width: 100%;
  overflow: hidden;
}

.file-name {
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 500;
  color: var(--text-primary);
}

.file-meta {
  font-size: 11px;
  color: var(--text-secondary);
}

.table-wrapper {
  overflow-x: auto;
}

.modern-table {
  width: 100%;
  min-width: 600px;
}

.table-header {
  display: flex;
  padding: 12px 24px;
  background: #f8fafc;
  border-bottom: 1px solid var(--border);
  font-weight: 600;
  color: var(--text-secondary);
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.header-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.table-body {
  display: flex;
  flex-direction: column;
}

.table-row {
  display: flex;
  padding: 16px 24px;
  border-bottom: 1px solid var(--border);
  align-items: center;
  transition: background-color 0.2s;
  cursor: pointer;
}

.table-row:hover {
  background: var(--surface-color);
}

.table-row.drag-over {
  background: var(--surface-color);
  box-shadow: inset 0 0 0 2px var(--primary-color);
}

.table-cell {
  display: flex;
  align-items: center;
  font-size: 14px;
  color: var(--text-primary);
  overflow: hidden;
}

.cell-content {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.text-secondary {
  color: var(--text-secondary);
}

.name-cell {
  font-weight: 500;
}

.actions-cell {
  justify-content: flex-end;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.icon-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid transparent;
  background: transparent;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.icon-btn:hover {
  background: var(--surface-color);
  color: var(--text-primary);
}

.list-icon {
  font-size: 24px;
  color: var(--text-secondary);
}

.upload-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  margin-bottom: 24px;
  overflow: hidden;
  box-shadow: var(--shadow);
}

.upload-header {
  background: #f8fafc;
  padding: 12px 20px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.upload-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.close-upload-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 20px;
  color: var(--text-secondary);
}

.drop-zone {
  padding: 40px;
  border: 2px dashed var(--border);
  text-align: center;
  margin: 20px;
  border-radius: var(--radius);
  transition: all 0.2s;
  background: var(--background);
}

.drop-zone:hover,
.drop-zone.drag-over {
  border-color: var(--primary-color);
  background: var(--surface-color);
}

.upload-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin-bottom: 12px;
}

.upload-text {
  margin: 0 0 16px 0;
  color: var(--text-secondary);
}

.select-files-btn {
  padding: 8px 16px;
  background: var(--primary-color);
  color: white;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  font-weight: 500;
}

.upload-progress {
  padding: 0 20px 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.progress-bar {
  flex: 1;
  height: 6px;
  background: var(--border);
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: var(--primary-color);
  transition: width 0.3s ease;
}

.file-preview-list {
  padding: 20px;
  border-top: 1px solid var(--border);
}

.file-preview-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  background: var(--background);
  margin-bottom: 8px;
  border-radius: var(--radius);
}

.remove-file-btn {
  margin-left: auto;
  background: none;
  border: none;
  color: var(--danger-color);
  cursor: pointer;
}

.full-width {
  width: 100%;
  margin-top: 12px;
}

.no-data-state {
  padding: 60px;
  text-align: center;
}

.no-data-content {
  max-width: 400px;
  margin: 0 auto;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  opacity: 0.5;
  margin-bottom: 16px;
}

.no-data-content h4 {
  margin: 0 0 8px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.no-data-content p {
  color: var(--text-secondary);
  margin: 0;
}

.image-preview-modal {
  --width: 90%;
  --max-width: 800px;
  --height: auto;
  --max-height: 90%;
}

.image-preview-content {
  --padding-top: 0;
  --padding-bottom: 0;
}

.image-container {
  text-align: center;
  padding: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
}

.preview-image {
  max-height: 70vh;
  max-width: 100%;
  object-fit: contain;
  border-radius: var(--radius);
  box-shadow: var(--shadow-md);
}

.loading-spinner {
  color: var(--primary-color);
}

.error-message {
  color: var(--danger-color);
  text-align: center;
}

.preview-title {
  text-align: center;
  margin: 10px 0 20px;
  color: var(--text-primary);
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1e1e1e;
    --border: #2e2e2e;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
  }

  .table-header,
  .table-row:hover {
    background: #1a1a1a;
  }

  .table-row.drag-over {
    background: rgba(249, 115, 22, 0.2);
  }

  .upload-header {
    background: #1a1a1a;
  }

  .drop-zone {
    background: #1a1a1a;
  }

  .drop-zone:hover,
  .drop-zone.drag-over {
    background: rgba(249, 115, 22, 0.1);
  }
}
</style>
