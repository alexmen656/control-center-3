<template>
  <ion-page>
    <ion-content>
      <div class="file-system-container">
        <!-- Toolbar: Breadcrumbs + View Toggle -->
        <div class="toolbar">
          <div class="breadcrumbs">
            <span class="breadcrumb-item" :class="{ active: currentFolderId === rootId }" @click="navigateToRoot">
              <ion-icon name="home"></ion-icon>
            </span>
            <template v-for="(crumb, index) in breadcrumbs" :key="crumb.id">
              <ion-icon name="chevron-forward" class="breadcrumb-separator"></ion-icon>
              <span class="breadcrumb-item" :class="{ active: index === breadcrumbs.length - 1 }"
                @click="navigateToFolder(crumb)">
                {{ crumb.name }}
              </span>
            </template>
          </div>
          <div class="view-toggle">
            <ion-icon name="grid" :class="{ active: viewMode === 'grid' }" @click="viewMode = 'grid'"></ion-icon>
            <ion-icon name="list" :class="{ active: viewMode === 'table' }" @click="viewMode = 'table'"></ion-icon>
          </div>
        </div>

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="grid-view" @dragover.prevent="handleDragOver"
          @drop="handleDrop($event, currentFolderId)">
          <div v-for="item in currentItems" :key="item.id" class="grid-item" :class="{
            'is-folder': item.type === 'folder',
            'drag-over': item.type === 'folder' && dragOverId === item.id
          }" :draggable="true" @dragstart="handleDragStart($event, item)"
            @dragover.prevent="item.type === 'folder' && handleItemDragOver($event, item)"
            @dragleave="handleItemDragLeave" @drop.stop="item.type === 'folder' && handleDrop($event, item.id)"
            @click="handleItemClick(item)"
            @dblclick="item.type === 'file' && isImageFile(item.name) && previewImage(item)">
            <div class="item-preview">
              <img v-if="item.type === 'file' && isImageFile(item.name) && getSignedImageUrl(item.location)"
                :src="getSignedImageUrl(item.location)" @error="imageStatus[item.location] = false" />
              <ion-icon v-else :name="item.type === 'folder' ? 'folder' : 'document'"></ion-icon>
            </div>
            <div class="item-name">{{ shortenName(item.name) }}</div>
          </div>
        </div>

        <!-- Table View -->
        <div v-else class="table-view" @dragover.prevent="handleDragOver" @drop="handleDrop($event, currentFolderId)">
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Typ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in currentItems" :key="item.id" :class="{
                'is-folder': item.type === 'folder',
                'drag-over': item.type === 'folder' && dragOverId === item.id
              }" :draggable="true" @dragstart="handleDragStart($event, item)"
                @dragover.prevent="item.type === 'folder' && handleItemDragOver($event, item)"
                @dragleave="handleItemDragLeave" @drop.stop="item.type === 'folder' && handleDrop($event, item.id)"
                @click="handleItemClick(item)"
                @dblclick="item.type === 'file' && isImageFile(item.name) && previewImage(item)">
                <td class="name-cell">
                  <ion-icon :name="item.type === 'folder' ? 'folder' : 'document'"></ion-icon>
                  {{ item.name }}
                </td>
                <td>{{ item.type === 'folder' ? 'Ordner' : 'Datei' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="currentItems.length === 0" class="empty-state">
          <ion-icon name="folder-open"></ion-icon>
          <p>Ordner ist leer</p>
        </div>

        <!-- Upload Area -->
        <div class="upload-area">
          <div class="drop-zone" @dragover.prevent @drop.prevent="handleFileDrop">
            <ion-icon name="cloud-upload"></ion-icon>
            <span>Dateien hier ablegen</span>
          </div>
          <progress v-if="uploadPercentage > 0" max="100" :value="uploadPercentage"></progress>
        </div>

        <!-- Folder Creation -->
        <div class="folder-creation">
          <input v-model="newFolderName" placeholder="Neuer Ordnername" @keyup.enter="createFolder" />
          <button @click="createFolder">Ordner erstellen</button>
        </div>
      </div>

      <!-- Image Preview Modal -->
      <ion-modal :is-open="imagePreviewOpen" @did-dismiss="closeImagePreview">
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
            <img :src="previewImageUrl" :alt="previewImageName" class="preview-image" />
          </div>
        </ion-content>
      </ion-modal>
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
  IonButton
} from "@ionic/vue";
import axios from "axios";

export default defineComponent({
  name: "FileSystem",
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
  },
  data() {
    return {
      fileSystem: [],
      rootId: 0,
      currentFolderId: 0,
      breadcrumbs: [],
      viewMode: 'grid',
      newFolderName: "",
      uploadPercentage: 0,
      imageStatus: {},
      signedUrls: {},
      dragOverId: null,
      draggedItem: null,
      // Image preview
      imagePreviewOpen: false,
      previewImageUrl: "",
      previewImageName: "",
    };
  },
  computed: {
    currentItems() {
      if (this.currentFolderId === this.rootId) {
        return this.fileSystem;
      }
      return this.findFolderItems(this.fileSystem, this.currentFolderId);
    }
  },
  mounted() {
    this.fetchFileSystemData();
  },
  methods: {
    async fetchFileSystemData() {
      try {
        const projectLink = this.getProjectLink();
        const url = projectLink ? `filesystem.php?project=${projectLink}` : 'filesystem.php';
        const response = await axios.get(url);

        console.log('Backend response:', response.data);

        // Ensure IDs are integers for proper comparison
        this.rootId = parseInt(response.data.rootId) || 0;
        this.fileSystem = response.data.items || [];

        // Preserve current folder if valid, otherwise go to root
        if (this.currentFolderId === 0 || !this.findFolderById(this.fileSystem, this.currentFolderId)) {
          this.currentFolderId = this.rootId;
        }

        await this.loadSignedUrlsForImages();
      } catch (error) {
        console.error("Fehler beim Laden der Dateisystemdaten:", error);
      }
    },

    findFolderItems(items, folderId) {
      const targetId = parseInt(folderId);
      for (const item of items) {
        if (parseInt(item.id) === targetId && item.type === 'folder') {
          return item.children || [];
        }
        if (item.type === 'folder' && item.children) {
          const found = this.findFolderItems(item.children, targetId);
          if (found && found.length > 0) return found;
        }
      }
      return [];
    },

    findFolderById(items, folderId) {
      const targetId = parseInt(folderId);
      for (const item of items) {
        if (parseInt(item.id) === targetId) {
          return item;
        }
        if (item.type === 'folder' && item.children) {
          const found = this.findFolderById(item.children, targetId);
          if (found) return found;
        }
      }
      return null;
    },

    handleItemClick(item) {
      if (item.type === 'folder') {
        this.navigateToFolder(item);
      }
    },

    navigateToFolder(folder) {
      this.currentFolderId = parseInt(folder.id);
      this.updateBreadcrumbs(folder);
    },

    navigateToRoot() {
      this.currentFolderId = this.rootId;
      this.breadcrumbs = [];
    },

    updateBreadcrumbs(folder) {
      const existingIndex = this.breadcrumbs.findIndex(b => b.id === folder.id);
      if (existingIndex >= 0) {
        this.breadcrumbs = this.breadcrumbs.slice(0, existingIndex + 1);
      } else {
        this.breadcrumbs.push({ id: folder.id, name: folder.name });
      }
    },

    // Drag and Drop
    handleDragStart(event, item) {
      this.draggedItem = item;
      event.dataTransfer.setData('application/json', JSON.stringify(item));
      event.dataTransfer.effectAllowed = 'move';
    },

    handleDragOver(event) {
      event.preventDefault();
      event.dataTransfer.dropEffect = 'move';
    },

    handleItemDragOver(event, item) {
      event.preventDefault();
      if (this.draggedItem && this.draggedItem.id !== item.id) {
        this.dragOverId = item.id;
      }
    },

    handleItemDragLeave() {
      this.dragOverId = null;
    },

    async handleDrop(event, targetFolderId) {
      event.preventDefault();
      this.dragOverId = null;

      // Check for new files from computer
      if (event.dataTransfer.files && event.dataTransfer.files.length > 0) {
        await this.uploadFiles(event.dataTransfer.files, targetFolderId);
        return;
      }

      // Move existing item
      if (this.draggedItem && this.draggedItem.id !== targetFolderId) {
        await this.moveItem(this.draggedItem.id, targetFolderId);
      }
      this.draggedItem = null;
    },

    async handleFileDrop(event) {
      if (event.dataTransfer.files && event.dataTransfer.files.length > 0) {
        await this.uploadFiles(event.dataTransfer.files, this.currentFolderId);
      }
    },

    async moveItem(sourceId, targetFolderId) {
      try {
        const formData = new FormData();
        formData.append('action', 'move');
        formData.append('sourceId', sourceId);
        formData.append('targetFolderId', targetFolderId);
        formData.append('project', this.getProjectLink());

        const response = await axios.post('filesystem.php', formData);
        if (response.data.success) {
          await this.fetchFileSystemData();
        } else {
          console.error('Move failed:', response.data.message);
        }
      } catch (error) {
        console.error('Error moving item:', error);
      }
    },

    async uploadFiles(files, parentId) {
      const formData = new FormData();

      for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
      }
      formData.append('parentId', parentId);
      formData.append('name', files[0].name);
      formData.append('project', this.getProjectLink());

      try {
        await axios.post('filesystem.php', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
          onUploadProgress: (progressEvent) => {
            this.uploadPercentage = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          }
        });
        this.uploadPercentage = 0;
        await this.fetchFileSystemData();
      } catch (error) {
        console.error('Upload failed:', error);
        this.uploadPercentage = 0;
      }
    },

    async createFolder() {
      if (!this.newFolderName.trim()) return;

      const formData = new FormData();
      formData.append('name', this.newFolderName);
      formData.append('parentId', this.currentFolderId);
      formData.append('project', this.getProjectLink());

      try {
        await axios.post('filesystem.php', formData);
        this.newFolderName = "";
        await this.fetchFileSystemData();
      } catch (error) {
        console.error('Folder creation failed:', error);
      }
    },

    getProjectLink() {
      return this.$route?.params?.project || '';
    },

    // Image handling
    async loadSignedUrlsForImages() {
      const imageFiles = [];

      const collectImages = (items) => {
        items.forEach(item => {
          if (item.type === 'file' && this.isImageFile(item.name)) {
            imageFiles.push({ path: item.location, location: item.location });
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

    isImageFile(filename) {
      return /\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i.test(filename);
    },

    async previewImage(file) {
      this.imagePreviewOpen = true;
      this.previewImageName = file.name;

      try {
        const response = await axios.post('signed_url_generator.php', {
          path: file.location,
          validitySeconds: 3600
        });

        if (response.data.success) {
          this.previewImageUrl = response.data.url;
        }
      } catch (error) {
        console.error('Error generating preview URL:', error);
      }
    },

    closeImagePreview() {
      this.imagePreviewOpen = false;
      this.previewImageUrl = '';
      this.previewImageName = '';
    },

    shortenName(name) {
      if (name.length > 20) {
        return name.slice(0, 10) + "..." + name.slice(-7);
      }
      return name;
    }
  }
});
</script>

<style scoped>
.file-system-container {
  padding: 16px;
  max-width: 1400px;
  margin: 0 auto;
}

/* Toolbar */
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: var(--ion-color-light);
  border-radius: 8px;
  margin-bottom: 16px;
}

.breadcrumbs {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.breadcrumb-item {
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  color: var(--ion-color-medium);
  transition: all 0.2s;
}

.breadcrumb-item:hover {
  background: var(--ion-color-light-shade);
  color: var(--ion-color-dark);
}

.breadcrumb-item.active {
  color: var(--ion-color-primary);
  font-weight: 500;
}

.breadcrumb-separator {
  color: var(--ion-color-medium);
  font-size: 12px;
}

.view-toggle {
  display: flex;
  gap: 8px;
}

.view-toggle ion-icon {
  font-size: 24px;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  color: var(--ion-color-medium);
  transition: all 0.2s;
}

.view-toggle ion-icon:hover {
  background: var(--ion-color-light-shade);
}

.view-toggle ion-icon.active {
  color: var(--ion-color-primary);
  background: var(--ion-color-primary-tint);
}

/* Grid View */
.grid-view {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 16px;
  min-height: 200px;
}

.grid-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 8px;
  background: var(--ion-color-light);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  border: 2px solid transparent;
}

.grid-item:hover {
  background: var(--ion-color-light-shade);
  transform: translateY(-2px);
}

.grid-item.drag-over {
  border-color: var(--ion-color-primary);
  background: var(--ion-color-primary-tint);
}

.item-preview {
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
}

.item-preview img {
  max-width: 100%;
  max-height: 100%;
  object-fit: cover;
  border-radius: 4px;
}

.item-preview ion-icon {
  font-size: 48px;
  color: var(--ion-color-medium);
}

.grid-item.is-folder .item-preview ion-icon {
  color: var(--ion-color-warning);
}

.item-name {
  font-size: 12px;
  text-align: center;
  word-break: break-word;
  color: var(--ion-color-dark);
}

/* Table View */
.table-view {
  min-height: 200px;
}

.table-view table {
  width: 100%;
  border-collapse: collapse;
}

.table-view th,
.table-view td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid var(--ion-color-light-shade);
}

.table-view th {
  background: var(--ion-color-light);
  font-weight: 600;
  color: var(--ion-color-medium);
}

.table-view tr {
  cursor: pointer;
  transition: background 0.2s;
}

.table-view tbody tr:hover {
  background: var(--ion-color-light);
}

.table-view tr.drag-over {
  background: var(--ion-color-primary-tint);
}

.name-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.name-cell ion-icon {
  font-size: 24px;
  color: var(--ion-color-medium);
}

.table-view tr.is-folder .name-cell ion-icon {
  color: var(--ion-color-warning);
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--ion-color-medium);
}

.empty-state ion-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

/* Upload Area */
.upload-area {
  margin-top: 24px;
}

.drop-zone {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 24px;
  border: 2px dashed var(--ion-color-medium);
  border-radius: 8px;
  color: var(--ion-color-medium);
  transition: all 0.2s;
}

.drop-zone:hover {
  border-color: var(--ion-color-primary);
  color: var(--ion-color-primary);
}

.drop-zone ion-icon {
  font-size: 24px;
}

progress {
  width: 100%;
  height: 8px;
  margin-top: 12px;
  border-radius: 4px;
}

/* Folder Creation */
.folder-creation {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.folder-creation input {
  flex: 1;
  padding: 12px 16px;
  border: 1px solid var(--ion-color-light-shade);
  border-radius: 8px;
  font-size: 14px;
}

.folder-creation input:focus {
  outline: none;
  border-color: var(--ion-color-primary);
}

.folder-creation button {
  padding: 12px 24px;
  background: var(--ion-color-primary);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  white-space: nowrap;
}

.folder-creation button:hover {
  background: var(--ion-color-primary-shade);
}

/* Image Preview */
.image-preview-content {
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-container {
  padding: 20px;
}

.preview-image {
  max-width: 100%;
  max-height: 80vh;
  object-fit: contain;
  border-radius: 8px;
}

/* Mobile */
@media (max-width: 768px) {
  .toolbar {
    flex-direction: column;
    gap: 12px;
  }

  .grid-view {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 12px;
  }

  .folder-creation {
    flex-direction: column;
  }

  .folder-creation button {
    width: 100%;
  }
}
</style>
