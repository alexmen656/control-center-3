<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle v-if="true" icon="folder-outline" title="File System"/>

      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Project File System</h1>
            <p>Browse and manage your project files and folders</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="refreshFiles">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
            <button class="action-btn secondary" @click="toggleSearch">
              <ion-icon name="search-outline"></ion-icon>
              {{ showSearchBox ? 'Hide Search' : 'Search' }}
            </button>
            <button class="action-btn primary" @click="showUploadArea">
              <ion-icon name="cloud-upload-outline"></ion-icon>
              Upload Files
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="folder-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ folderCount }}</h3>
              <p>Folders</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="document-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ fileCount - folderCount }}</h3>
              <p>Files</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="grid-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ fileCount }}</h3>
              <p>Total Items</p>
            </div>
          </div>
        </div>

        <!-- Search Bar -->
        <div v-if="showSearchBox" class="search-container">
          <div class="search-box">
            <ion-icon name="search-outline"></ion-icon>
            <input 
              type="text" 
              placeholder="Search files and folders..." 
              v-model="searchTerm"
              class="search-input"
              autofocus
              @input="handleSearch"
            >
            <button v-if="searchTerm" @click="searchTerm = ''" class="clear-search">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
        </div>

        <!-- File Upload Area -->
        <div v-if="showUpload" class="upload-card">
          <div class="upload-header">
            <h4>Upload Files</h4>
            <button class="close-upload-btn" @click="showUpload = false">
              <ion-icon name="close"></ion-icon>
            </button>
          </div>
          
          <div 
            class="drop-zone" 
            :class="{ 'drag-over': isRootDragOver }"
            @dragover.prevent="handleRootDragOver"
            @drop="handleRootDrop"
            @dragenter.prevent="handleRootDragEnter"
            @dragleave="handleRootDragLeave"
          >
            <form ref="fileform" style="display: none;"></form>
            <ion-icon name="cloud-upload-outline" class="upload-icon"></ion-icon>
            <p class="upload-text">Drop files here or click to select</p>
            <input type="file" multiple @change="handleFileSelect" style="display: none;" ref="fileInput">
            <button class="select-files-btn" @click="$refs.fileInput.click()">
              Select Files
            </button>
          </div>
          
          <!-- Upload Progress -->
          <div v-if="uploadPercentage > 0" class="upload-progress">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: uploadPercentage + '%' }"></div>
            </div>
            <span class="progress-text">{{ uploadPercentage }}%</span>
          </div>
          
          <!-- File Preview List -->
          <div v-if="files.length > 0" class="file-preview-list">
            <h5>Selected Files:</h5>
            <div v-for="(file, index) in files" :key="index" class="file-preview-item">
              <ion-icon name="document-outline"></ion-icon>
              <span class="file-name">{{ file.name }}</span>
              <button class="remove-file-btn" @click="removeFile(index)">
                <ion-icon name="close"></ion-icon>
              </button>
            </div>
          </div>
        </div>

        <!-- Files Grid -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Project Files</h3>
              <span class="entry-count">{{ filteredFileSystem.length }} item{{ filteredFileSystem.length !== 1 ? 's' : '' }}</span>
            </div>
          </div>

          <div class="files-wrapper">
            <div class="files-grid" 
                 @dragover.prevent="handleRootDragOver"
                 @drop="handleRootDrop"
                 @dragenter.prevent="handleRootDragEnter"
                 @dragleave="handleRootDragLeave">
              
              <!-- No files state -->
              <div v-if="filteredFileSystem.length === 0" class="no-data-state">
                <div class="no-data-content">
                  <ion-icon name="folder-open-outline" class="no-data-icon"></ion-icon>
                  <h4>No files found</h4>
                  <p v-if="searchTerm">No files match your search criteria</p>
                  <p v-else>This project doesn't have any files yet</p>
                  <button v-if="searchTerm" @click="searchTerm = ''" class="action-btn primary">
                    Clear Search
                  </button>
                  <button v-else class="action-btn primary" @click="showUploadArea">
                    <ion-icon name="add"></ion-icon>
                    Upload First File
                  </button>
                </div>
              </div>

              <!-- File and folder cards -->
              <div v-for="item in filteredFileSystem" :key="item.name" class="file-card-container">
              <div 
                class="file-card" 
                :class="{ 
                  'is-folder': item.type === 'folder',
                  'is-image': item.type === 'file' && isImageFile(item.name),
                  'drag-over': item.isDragOver 
                }"
                @click="handleItemClick(item)"
                @dragover.prevent="handleDragOver"
                @dragenter.prevent="e => handleDragEnter(e, item)"
                @dragleave.prevent="handleDragLeave"
                @drop="e => handleFolderDrop(e, item)"
                :draggable="item.type === 'file'"
                @dragstart="e => handleDragStart(e, item)"
              >
                <div class="file-card-content">
                  <!-- Image preview for image files -->
                  <img
                    v-if="item.type === 'file' && isImageFile(item.name)"
                    :src="'https://alex.polan.sk/control-center/file_provider.php?path=' + item.location"
                    class="file-preview-image"
                    @error="onImageError"
                    @load="onImageLoad"
                  />
                  
                  <!-- Icons for folders and non-image files -->
                  <ion-icon
                    v-else
                    :name="getFileIcon(item)"
                    class="file-icon"
                  ></ion-icon>
                  
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

                <!-- Folder indicator -->
                <div v-if="item.type === 'folder'" class="folder-indicator">
                  <ion-icon name="chevron-forward"></ion-icon>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Folder Creation Card -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Create New Folder</h3>
              <span class="entry-count">Quick folder creation</span>
            </div>
          </div>
          
          <div class="folder-creation">
            <input 
              type="text"
              v-model="newFolderName"
              placeholder="Enter folder name..."
              class="folder-input"
              @keyup.enter="createFolder"
            />
            <button class="folder-button" @click="createFolder" :disabled="!newFolderName.trim()">
              <ion-icon name="folder-outline"></ion-icon>
              Create Folder
            </button>
          </div>
        </div>
      </div>
      
      <!-- Image Preview Modal -->
      <ion-modal :is-open="imagePreviewOpen" @did-dismiss="closeImagePreview" class="image-preview-modal">
        <ion-header>
          <ion-toolbar>
            <ion-title>{{ previewImageName }}</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="closeImagePreview">
                <ion-icon name="close"></ion-icon>
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
            
            <img
              v-if="previewImageUrl"
              :src="previewImageUrl"
              @load="onImageLoad"
              @error="onImageError"
              class="preview-image"
              :style="{ display: imageLoaded ? 'block' : 'none' }"
            />
          </div>
          <h3 class="preview-title">{{ previewImageName }}</h3>
        </ion-content>
      </ion-modal>

      <!-- Folder Content Modal -->
      <ion-modal :is-open="folderModalOpen" @did-dismiss="closeFolderModal" class="folder-modal">
        <ion-header>
          <ion-toolbar>
            <ion-title>{{ selectedFolder?.name }}</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="closeFolderModal">
                <ion-icon name="close"></ion-icon>
              </ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content 
          class="folder-modal-content"
          @dragover.prevent="handleModalDragOver"
          @dragenter.prevent="handleModalDragEnter"
          @dragleave="handleModalDragLeave"
          @drop="handleModalDrop"
        >
          <div v-if="selectedFolder && selectedFolder.children" class="modal-files-grid">
            <div v-if="selectedFolder.children.length === 0" class="no-files-state">
              <ion-icon name="folder-open-outline" class="no-files-icon"></ion-icon>
              <h4>Empty folder</h4>
              <p>Drag files here to add them to this folder</p>
            </div>
            
            <div v-for="item in selectedFolder.children" :key="item.name" class="modal-item-card">
              <div 
                class="file-card"
                :class="{ 'is-image': item.type === 'file' && isImageFile(item.name) }"
                @click="handleModalItemClick(item)"
                :draggable="item.type === 'file'"
                @dragstart="e => handleDragStart(e, item)"
              >
                <div class="file-card-content">
                  <!-- Image preview -->
                  <img
                    v-if="item.type === 'file' && isImageFile(item.name)"
                    :src="'https://alex.polan.sk/control-center/file_provider.php?path=' + item.location"
                    class="file-preview-image"
                  />
                  
                  <!-- Icons -->
                  <ion-icon
                    v-else
                    :name="getFileIcon(item)"
                    class="file-icon"
                  ></ion-icon>
                  
                  <div class="file-info">
                    <span class="file-name">{{ shortenName(item.name) }}</span>
                    <span v-if="item.type === 'file'" class="file-meta">
                      {{ getFileExtension(item.name) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
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
import axios from "axios";

export default defineComponent({
  name: "ProjectFileSystem",
  components: {
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
      fileSystem: [],
      newFolderName: "",
      imageStatus: {},
      // UI state
      dropdownOpen: false,
      showUpload: false,
      searchTerm: '',
      showSearchBox: false,
      // Image preview data
      imagePreviewOpen: false,
      previewImageUrl: "",
      previewImageName: "",
      imageLoaded: false,
      imageError: false,
      // Folder modal data
      folderModalOpen: false,
      selectedFolder: null,
      // Drag state
      isRootDragOver: false,
      isModalDragOver: false,
    };
  },
  computed: {
    fileCount() {
      return this.fileSystem.length;
    },
    folderCount() {
      return this.fileSystem.filter(item => item.type === 'folder').length;
    },
    filteredFileSystem() {
      if (!this.searchTerm.trim()) {
        return this.fileSystem;
      }
      
      const searchLower = this.searchTerm.toLowerCase();
      return this.fileSystem.filter(item => 
        item.name.toLowerCase().includes(searchLower)
      );
    }
  },
  mounted() {
    this.dragAndDropCapable = this.determineDragAndDropCapable();

    if (this.dragAndDropCapable) {
      [
        "drag",
        "dragstart",
        "dragend",
        "dragover",
        "dragenter",
        "dragleave",
        "drop",
      ].forEach(
        function (evt) {
          if (this.$refs.fileform) {
            this.$refs.fileform.addEventListener(
              evt,
              function (e) {
                e.preventDefault();
                e.stopPropagation();
              }.bind(this),
              false
            );
          }
        }.bind(this)
      );

      if (this.$refs.fileform) {
        this.$refs.fileform.addEventListener(
          "drop",
          function (e) {
            for (let i = 0; i < e.dataTransfer.files.length; i++) {
              this.files.push(e.dataTransfer.files[i]);
              this.getImagePreviews();
            }
          }.bind(this)
        );
      }

      // Add global drop listener for modal to root drag & drop
      document.addEventListener('dragover', this.globalDragOver);
      document.addEventListener('drop', this.globalDrop);
    }

    // Fetch file system data
    this.fetchFileSystemData();
  },

  beforeUnmount() {
    // Clean up global listeners
    document.removeEventListener('dragover', this.globalDragOver);
    document.removeEventListener('drop', this.globalDrop);
  },

  methods: {
    shortenName(name) {
      if (name.length > 18) {
        return name.slice(0, 8) + "..." + name.slice(-7);
      }
      return name;
    },

    getFileIcon(item) {
      if (item.type === 'folder') {
        return item.open ? 'folder-open' : 'folder';
      }
      
      const ext = this.getFileExtension(item.name).toLowerCase();
      const iconMap = {
        'js': 'logo-javascript',
        'ts': 'logo-javascript',
        'vue': 'logo-vue',
        'php': 'code-outline',
        'html': 'logo-html5',
        'css': 'logo-css3',
        'json': 'code-outline',
        'md': 'document-text-outline',
        'txt': 'document-text-outline',
        'pdf': 'document-outline',
        'zip': 'archive-outline',
        'rar': 'archive-outline',
        'mp4': 'videocam-outline',
        'mp3': 'musical-notes-outline',
        'wav': 'musical-notes-outline',
      };
      
      return iconMap[ext] || 'document-outline';
    },

    getFileExtension(filename) {
      return filename.split('.').pop() || '';
    },

    isImageFile(filename) {
      const imageExtensions = /\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i;
      return imageExtensions.test(filename);
    },

    async getImage(location) {
      try {
        const res = await axios.get(
          "https://alex.polan.sk/control-center/file_provider.php?path=" + location
        );
        this.imageStatus[location] = res.status === 200;
      } catch (error) {
        this.imageStatus[location] = false;
      }
    },

    determineDragAndDropCapable() {
      const div = document.createElement("div");
      return (
        ("draggable" in div || ("ondragstart" in div && "ondrop" in div)) &&
        "FormData" in window &&
        "FileReader" in window
      );
    },

    getImagePreviews() {
      for (let i = 0; i < this.files.length; i++) {
        if (/\.(jpe?g|png|gif)$/i.test(this.files[i].name)) {
          const reader = new FileReader();
          reader.addEventListener(
            "load",
            function () {
              // Handle image preview if needed
            }.bind(this),
            false
          );
          reader.readAsDataURL(this.files[i]);
        }
      }
    },

    // UI Methods
    toggleDropdown() {
      this.dropdownOpen = !this.dropdownOpen;
    },

    toggleSearch() {
      this.showSearchBox = !this.showSearchBox;
      if (!this.showSearchBox) {
        this.searchTerm = "";
      }
    },

    showUploadArea() {
      this.showUpload = true;
      this.dropdownOpen = false;
    },

    handleSearch() {
      // Search is handled in computed property
    },

    refreshFiles() {
      this.fetchFileSystemData();
      this.dropdownOpen = false;
    },

    // File handling methods
    handleFileSelect(event) {
      for (let i = 0; i < event.target.files.length; i++) {
        this.files.push(event.target.files[i]);
      }
      this.getImagePreviews();
    },

    removeFile(index) {
      this.files.splice(index, 1);
      this.getImagePreviews();
    },

    handleItemClick(item) {
      if (item.type === 'folder') {
        this.openFolderModal(item);
      } else if (item.type === 'file' && this.isImageFile(item.name)) {
        this.previewImage(item);
      }
    },

    handleModalItemClick(item) {
      if (item.type === 'file' && this.isImageFile(item.name)) {
        this.previewImage(item);
      }
    },

    // Drag and drop methods
    handleDrop(event, folder) {
      this.handleFolderDrop(event, folder);
    },

    handleRootDrop(event) {
      event.preventDefault();
      this.isRootDragOver = false;
      
      console.log('Root drop event triggered');
      
      // Check if it's a file being dragged from within a folder (modal)
      const dragData = event.dataTransfer.getData('application/json');
      console.log('Drag data:', dragData);
      
      if (dragData) {
        // Moving existing file from folder to root
        const data = JSON.parse(dragData);
        console.log('Parsed drag data:', data);
        if (data.type === 'existing-file') {
          this.moveFileToRoot(data.file);
          return;
        }
      }
      
      // Otherwise it's new files from computer
      console.log('Processing new files from computer, count:', event.dataTransfer.files.length);
      for (let i = 0; i < event.dataTransfer.files.length; i++) {
        this.files.push(event.dataTransfer.files[i]);
      }
      this.submit("");
    },

    handleRootDragOver(event) {
      event.preventDefault();
      event.dataTransfer.dropEffect = 'move';
      this.isRootDragOver = true;
    },

    handleRootDragEnter(event) {
      event.preventDefault();
      this.isRootDragOver = true;
    },

    handleRootDragLeave(event) {
      event.preventDefault();
      this.isRootDragOver = false;
    },

    handleDragStart(event, file) {
      console.log('Starting drag for file:', file.name, 'from location:', file.location);
      event.dataTransfer.setData('application/json', JSON.stringify({
        type: 'existing-file',
        file: file
      }));
      event.dataTransfer.effectAllowed = 'move';
    },

    handleDragOver(event) {
      event.preventDefault();
      event.dataTransfer.dropEffect = 'move';
    },

    handleDragEnter(event, folder) {
      event.preventDefault();
      if (folder.type === 'folder') {
        folder.isDragOver = true;
      }
    },

    handleDragLeave(event) {
      event.preventDefault();
      // Find the folder and remove drag state
      this.fileSystem.forEach(item => {
        if (item.type === 'folder') {
          item.isDragOver = false;
        }
      });
    },

    handleFolderDrop(event, folder) {
      event.preventDefault();
      if (folder.type === 'folder') {
        folder.isDragOver = false;
      }
      
      // Check if it's a file from computer or existing file
      const dragData = event.dataTransfer.getData('application/json');
      
      if (dragData) {
        // Moving existing file between folders
        const data = JSON.parse(dragData);
        if (data.type === 'existing-file') {
          this.moveFileToFolder(data.file, folder);
          return;
        }
      } else if (event.dataTransfer.files && event.dataTransfer.files.length > 0) {
        // Uploading new files from computer
        for (let i = 0; i < event.dataTransfer.files.length; i++) {
          this.files.push(event.dataTransfer.files[i]);
        }
        this.submit(folder.name);
      }
    },

    // Modal drag handlers
    handleModalDragOver(event) {
      event.preventDefault();
      console.log('Modal drag over');
    },

    handleModalDragEnter(event) {
      event.preventDefault();
      console.log('Modal drag enter');
      if (!this.isModalDragOver) {
        this.isModalDragOver = true;
      }
    },

    handleModalDragLeave(event) {
      event.preventDefault();
      console.log('Modal drag leave');
      if (!event.currentTarget.contains(event.relatedTarget)) {
        this.isModalDragOver = false;
      }
    },

    handleModalDrop(event) {
      event.preventDefault();
      console.log('Modal drop - preventing default behavior');
      this.isModalDragOver = false;
    },

    // Global drag handlers for modal to root functionality
    globalDragOver(event) {
      const dragData = event.dataTransfer?.types?.includes('application/json');
      if (dragData && this.folderModalOpen) {
        event.preventDefault();
        console.log('Global drag over detected');
      }
    },

    globalDrop(event) {
      if (!this.folderModalOpen) return;
      
      try {
        const dragData = event.dataTransfer.getData('application/json');
        console.log('Global drop event, drag data:', dragData);
        
        if (dragData) {
          const data = JSON.parse(dragData);
          if (data.type === 'existing-file') {
            this.moveFileToRoot(data.file);
          }
        }
      } catch (error) {
        console.log('Global drop error (normal if dragging from outside):', error);
      }
      
      this.isModalDragOver = false;
    },

    // File operations
    submit(dir) {
      if (this.files.length > 0) {
        const formData = new FormData();

        for (let i = 0; i < this.files.length; i++) {
          const file = this.files[i];
          formData.append("files[" + i + "]", file);
          formData.append("name", file.name);
        }

        formData.append("directory", dir);
        formData.append("project", this.$route.params.project);

        this.$axios
          .post("filesystem.php", formData, {
            headers: {
              "Content-Type": "multipart/form-data",
            },
            onUploadProgress: function (progressEvent) {
              this.uploadPercentage = parseInt(
                Math.round((progressEvent.loaded * 100) / progressEvent.total)
              );
            }.bind(this),
          })
          .then(() => {
            console.log("SUCCESS!!");
            this.files = [];
            this.uploadPercentage = 0;
            this.showUpload = false;
            this.fetchFileSystemData();
          })
          .catch((err) => {
            console.log("FAILURE!!", err);
          });
      } else {
        console.log("No files to upload");
      }
    },

    async fetchFileSystemData() {
      try {
        const response = await axios.get(`filesystem.php?project=${this.$route.params.project}`);
        console.log('Raw file system data:', response.data);
        this.fileSystem = this.processFileSystemData(response.data);
        console.log('Processed file system data:', this.fileSystem);
      } catch (error) {
        console.error("Error fetching file system data:", error);
      }
    },

    processFileSystemData(items) {
      return items.map(item => {
        const processedItem = { ...item };
        
        if (item.type === 'folder') {
          processedItem.open = false;
          processedItem.isDragOver = false;
          if (item.children) {
            processedItem.children = this.processFileSystemData(item.children);
          }
        }
        
        return processedItem;
      });
    },

    createFolder() {
      if (this.newFolderName.trim() !== "") {
        const formData = new FormData();
        formData.append("name", this.newFolderName);
        formData.append("directory", "");
        formData.append("project", this.$route.params.project);

        axios
          .post("filesystem.php", formData)
          .then(() => {
            console.log("Folder created successfully!");
            this.fetchFileSystemData();
            this.newFolderName = "";
          })
          .catch((err) => {
            console.log("Error creating folder:", err);
          });
      } else {
        console.log("Folder name cannot be empty");
      }
    },

    toggleFolder(folder) {
      folder.open = !folder.open;
    },

    // Modal methods
    openFolderModal(folder) {
      console.log('Opening folder modal for:', folder.name, 'children:', folder.children);
      this.selectedFolder = folder;
      this.folderModalOpen = true;
    },

    closeFolderModal() {
      this.folderModalOpen = false;
      this.selectedFolder = null;
    },

    updateSelectedFolder() {
      if (this.selectedFolder && this.folderModalOpen) {
        const updatedFolder = this.fileSystem.find(item => 
          item.name === this.selectedFolder.name && item.type === 'folder'
        );
        if (updatedFolder) {
          this.selectedFolder = updatedFolder;
        }
      }
    },

    // Image preview methods
    previewImage(file) {
      if (this.isImageFile(file.name)) {
        this.previewImageUrl = `https://alex.polan.sk/control-center/file_provider.php?path=${file.location}`;
        this.previewImageName = file.name;
        this.imagePreviewOpen = true;
        this.imageLoaded = false;
        this.imageError = false;
      }
    },

    closeImagePreview() {
      this.imagePreviewOpen = false;
      this.previewImageUrl = '';
      this.previewImageName = '';
      this.imageLoaded = false;
      this.imageError = false;
    },

    onImageLoad() {
      this.imageLoaded = true;
      this.imageError = false;
    },

    onImageError() {
      this.imageError = true;
      this.imageLoaded = false;
    },

    // File moving methods
    async moveFileToFolder(file, targetFolder) {
      try {
        const formData = new FormData();
        formData.append('action', 'move');
        formData.append('sourceFile', file.location);
        formData.append('targetFolder', targetFolder.name);
        formData.append('project', this.$route.params.project);
        
        const response = await axios.post('filesystem.php', formData);
        
        if (response.data.success) {
          this.fetchFileSystemData();
        } else {
          console.error('Error moving file:', response.data.error);
        }
      } catch (error) {
        console.error('Error moving file:', error);
      }
    },

    async moveFileToRoot(file) {
      try {
        console.log('moveFileToRoot called with:', file);
        const formData = new FormData();
        formData.append('action', 'move');
        formData.append('sourceFile', file.location);
        formData.append('targetFolder', '');
        formData.append('project', this.$route.params.project);
        
        console.log('Sending move request to backend...');
        const response = await axios.post('filesystem.php', formData);
        console.log('Backend response:', response.data);
        
        if (response.data.success) {
          this.fetchFileSystemData();
        } else {
          console.error('Error moving file to root:', response.data.error);
        }
      } catch (error) {
        console.error('Error moving file to root:', error);
      }
    },
  },
});
</script>

<style scoped>
/* Modern Design System */
.modern-content {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
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
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 20px;
}

.header-content h1 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  line-height: 1.2;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.5;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 20px;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
  flex-shrink: 0;
}

.stat-content h3 {
  margin: 0 0 4px 0;
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

/* Search Container */
.search-container {
  margin-bottom: 24px;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  max-width: 500px;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  font-size: 16px;
  z-index: 1;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-shadow: var(--shadow);
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.clear-search {
  position: absolute;
  right: 8px;
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 4px;
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.clear-search:hover {
  background: var(--background);
  color: var(--text-primary);
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
}

.action-btn ion-icon {
  font-size: 16px;
}




/* Upload Card */
.upload-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  margin-bottom: 24px;
  overflow: hidden;
}

.upload-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.upload-header h4 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.close-upload-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.close-upload-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

/* Drop Zone */
.drop-zone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 24px;
  border: 2px dashed var(--border);
  border-radius: var(--radius);
  margin: 24px;
  background: var(--background);
  transition: all 0.2s ease;
  cursor: pointer;
}

.drop-zone:hover,
.drop-zone.drag-over {
  border-color: var(--primary-color);
  background: #eff6ff;
}

.upload-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin-bottom: 16px;
}

.upload-text {
  margin: 0 0 16px 0;
  color: var(--text-secondary);
  font-size: 16px;
  text-align: center;
}

.select-files-btn {
  padding: 10px 20px;
  background: var(--primary-color);
  color: white;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.select-files-btn:hover {
  background: var(--primary-hover);
}

/* Upload Progress */
.upload-progress {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 24px 24px;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: var(--border);
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: var(--primary-color);
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 14px;
  font-weight: 500;
  color: var(--text-primary);
}

/* File Preview List */
.file-preview-list {
  padding: 0 24px 24px;
}

.file-preview-list h5 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.file-preview-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: var(--background);
  border-radius: var(--radius);
  margin-bottom: 8px;
}

.file-preview-item ion-icon {
  color: var(--text-secondary);
}

.file-name {
  flex: 1;
  font-size: 14px;
  color: var(--text-primary);
}

.remove-file-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border: none;
  border-radius: 50%;
  background: var(--danger-color);
  color: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

.remove-file-btn:hover {
  transform: scale(1.1);
}

/* Data Card */
.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 16px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.entry-count {
  color: var(--text-secondary);
  font-size: 14px;
}

/* Files Wrapper */
.files-wrapper {
  overflow-x: auto;
}

/* Files Grid */
.files-grid {
  padding: 24px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
  min-height: 200px;
}

/* File Cards */
.file-card-container {
  position: relative;
}

.file-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  height: 160px;
  position: relative;
  overflow: hidden;
}

.file-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

.file-card.is-image:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.file-card.drag-over {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
}

.file-card.drag-over .file-info {
  color: white;
}

.file-card-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  flex: 1;
}

.file-preview-image {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: var(--radius);
  margin-bottom: 12px;
  box-shadow: var(--shadow);
}

.file-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin-bottom: 12px;
}

.file-card.is-folder .file-icon {
  color: var(--warning-color);
}

.file-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.file-name {
  font-weight: 500;
  color: var(--text-primary);
  font-size: 14px;
  margin-bottom: 4px;
  word-break: break-word;
}

.file-meta {
  font-size: 12px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.folder-indicator {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 24px;
  height: 24px;
  background: var(--primary-color);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 12px;
}

/* No Data State */
.no-data-state {
  grid-column: 1 / -1;
  padding: 60px 20px;
  text-align: center;
  background: var(--surface);
}

.no-data-content {
  max-width: 400px;
  margin: 0 auto;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
}

.no-data-content h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-content p {
  margin: 0 0 24px 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

/* Folder Creation */
.folder-creation {
  display: flex;
  gap: 12px;
  padding: 24px;
  align-items: center;
}

.folder-input {
  flex: 1;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.folder-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.folder-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: var(--primary-color);
  color: white;
  border: none;
  border-radius: var(--radius);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.folder-button:hover:not(:disabled) {
  background: var(--primary-hover);
}

.folder-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal Styles */
.image-preview-modal,
.folder-modal {
  --width: 90%;
  --max-width: 800px;
  --height: auto;
  --max-height: 90%;
}

.image-preview-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
}

.image-container {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-height: 200px;
}

.preview-image {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
  border-radius: var(--radius);
  box-shadow: var(--shadow-md);
}

.loading-spinner, .error-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 20px;
}

.loading-spinner ion-spinner {
  margin-bottom: 10px;
}

.error-message {
  color: var(--danger-color);
}

.error-message ion-icon {
  font-size: 48px;
  margin-bottom: 10px;
}

.preview-title {
  margin-top: 16px;
  text-align: center;
  font-weight: 500;
  color: var(--text-primary);
  word-break: break-all;
}

.folder-modal-content {
  padding: 24px;
}

.modal-files-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 16px;
}

.modal-item-card .file-card {
  height: 120px;
  background: var(--background);
  border: 1px solid var(--border);
}

.modal-item-card .file-card:hover {
  background: var(--surface);
  box-shadow: var(--shadow-md);
}

.modal-item-card .file-preview-image {
  width: 60px;
  height: 60px;
}

.modal-item-card .file-icon {
  font-size: 36px;
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }
  
  .search-box input,
  .folder-input {
    background: var(--surface);
    color: var(--text-primary);
  }
  
  .drop-zone:hover,
  .drop-zone.drag-over {
    background: rgba(37, 99, 235, 0.1);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }
  
  .action-bar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .action-group-left,
  .action-group-right {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .action-group-left {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  
  .search-box input {
    min-width: 100%;
  }
  
  .files-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
    padding: 16px;
  }
  
  .file-card {
    height: 140px;
    padding: 12px;
  }
  
  .file-preview-image {
    width: 60px;
    height: 60px;
  }
  
  .file-icon {
    font-size: 36px;
  }
  
  .folder-creation {
    flex-direction: column;
  }
  
  .folder-input {
    width: 100%;
    margin-bottom: 8px;
  }
  
  .folder-button {
    width: 100%;
    justify-content: center;
  }
  
  .modal-files-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  }
}

@media (max-width: 480px) {
  .files-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  }
  
  .file-card {
    height: 120px;
    padding: 8px;
  }
  
  .header-content h1 {
    font-size: 24px;
  }
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.file-card {
  animation: fadeIn 0.3s ease;
}

/* Drag and Drop Visual States */
.file-card[draggable="true"] {
  cursor: grab;
}

.file-card[draggable="true"]:active {
  cursor: grabbing;
  opacity: 0.7;
}

/* Enhanced hover states */
.file-card.is-folder:hover {
  border-color: var(--warning-color);
}

.file-card.is-folder:hover .file-icon {
  color: var(--warning-color);
  transform: scale(1.1);
}

.file-card.is-image:hover .file-preview-image {
  transform: scale(1.05);
}
</style>
