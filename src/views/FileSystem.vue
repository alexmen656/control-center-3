<template>
  <ion-page>
    <ion-content>
      <div class="file-system-grid" @dragover.prevent @drop="handleRootDrop">
        <ion-grid>
          <ion-row>
            <ion-col 
              size="6" 
              size-sm="4" 
              size-md="3" 
              size-lg="2" 
              size-xl="2" 
              v-for="item in fileSystem" 
              :key="item.name"
            >
              <ion-card
                @click="item.type === 'folder' ? openFolderModal(item) : null"
                @dblclick="item.type === 'file' && isImageFile(item.name) && previewImage(item)"
                @dragover.prevent="item.type === 'folder' && handleDragOver($event)"
                @dragenter.prevent="item.type === 'folder' && handleDragEnter($event)"
                @dragleave="item.type === 'folder' && handleDragLeave($event)"
                @drop="item.type === 'folder' && handleFolderDrop($event, item)"
                :draggable="item.type === 'file'"
                @dragstart="item.type === 'file' && handleDragStart($event, item)"
                :class="{ 
                  'image-file': item.type === 'file' && isImageFile(item.name),
                  'drag-over': item.type === 'folder' && item.isDragOver
                }"
              >
                <ion-card-header>
                  <img
                    v-if="item.type === 'file' && isImageFile(item.name)"
                    :src="
                      'https://alex.polan.sk/control-center/file_provider.php?path=' +
                      item.location
                    "
                    @error="imageStatus[item.location] = false"
                    @load="imageStatus[item.location] = true"
                  />

                  <ion-icon
                    v-else
                    :name="
                      item.type === 'folder'
                        ? item.open
                          ? 'folder-open'
                          : 'folder'
                        : 'document'
                    "
                  ></ion-icon>
                  {{ shortenName(item.name) }}
                </ion-card-header>
                <ion-card-content v-if="false" style="display: none;">
                  <!-- Commented out for now - inline folder display -->
                  <!-- Debug info -->
                  <div style="font-size: 10px; color: red; padding: 5px;">
                    DEBUG: open={{ item.open }}, type={{ item.type }}, children={{ item.children ? item.children.length : 'none' }}
                  </div>
                  
                  <div v-if="!item.children || item.children.length === 0" style="padding: 20px; text-align: center; color: #666;">
                    Ordner ist leer
                  </div>
                  <ion-grid v-else>
                    <ion-row>
                      <ion-col
                        size="6"
                        size-sm="4" 
                        size-md="6"
                        size-lg="4"
                        v-for="subItem in item.children"
                        :key="subItem.name"
                      >
                        <ion-card
                          class="sub-item-card"
                          @dblclick="subItem.type === 'file' && isImageFile(subItem.name) && previewImage(subItem)"
                          :draggable="subItem.type === 'file'"
                          @dragstart="subItem.type === 'file' && handleDragStart($event, subItem)"
                          :class="{ 
                            'image-file': subItem.type === 'file' && isImageFile(subItem.name)
                          }"
                        >
                          <ion-card-header>
                            <img
                              v-if="subItem.type === 'file' && isImageFile(subItem.name)"
                              :src="'https://alex.polan.sk/control-center/file_provider.php?path=' + subItem.location"
                              @error="imageStatus[subItem.location] = false"
                              @load="imageStatus[subItem.location] = true"
                            />
                            <ion-icon
                              v-else
                              :name="subItem.type === 'folder' ? 'folder' : 'document'"
                            ></ion-icon>
                            {{ shortenName(subItem.name) }}
                          </ion-card-header>
                        </ion-card>
                      </ion-col>
                    </ion-row>
                  </ion-grid>
                </ion-card-content>
              </ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>

        <div id="file-drag-drop">
          <form ref="fileform">
            <span class="drop-files">Drop the files here!</span>
          </form>
          <progress max="100" :value.prop="uploadPercentage"></progress>
          <div v-for="(file, key) in files" :key="key" class="file-listing">
            <img class="preview" v-bind:ref="'preview' + parseInt(key)" />
            {{ file.name }}
            <div class="remove-container">
              <a class="remove" v-on:click="removeFile(key)">Remove</a>
            </div>
          </div>
        </div>

        <div>
          <div class="folder-creation">
            <input v-model="newFolderName" placeholder="Neuer Ordnername" class="folder-input" />
            <button @click="createFolder" class="folder-button">Ordner erstellen</button>
          </div>
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
            <img 
              :src="previewImageUrl" 
              :alt="previewImageName"
              class="preview-image"
              @load="imageLoaded = true"
              @error="imageError = true"
            />
            <div v-if="!imageLoaded && !imageError" class="loading-spinner">
              <ion-spinner></ion-spinner>
              <p>Lade Bild...</p>
            </div>
            <div v-if="imageError" class="error-message">
              <ion-icon name="alert-circle"></ion-icon>
              <p>Fehler beim Laden des Bildes</p>
            </div>
          </div>
        </ion-content>
      </ion-modal>

      <!-- Folder Content Modal -->
      <ion-modal :is-open="folderModalOpen" @did-dismiss="closeFolderModal">
        <ion-header>
          <ion-toolbar>
            <ion-title>{{ selectedFolder ? selectedFolder.name : 'Ordner' }}</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="closeFolderModal">
                <ion-icon name="close"></ion-icon>
              </ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="folder-modal-content">
          <div v-if="selectedFolder && (!selectedFolder.children || selectedFolder.children.length === 0)" 
               style="padding: 40px; text-align: center; color: #666;">
            <ion-icon name="folder-open" style="font-size: 48px; margin-bottom: 16px;"></ion-icon>
            <p>Ordner ist leer</p>
          </div>
          <ion-grid v-else-if="selectedFolder && selectedFolder.children">
            <ion-row>
              <ion-col
                size="6"
                size-sm="4" 
                size-md="3"
                size-lg="3"
                v-for="subItem in selectedFolder.children"
                :key="subItem.name"
              >
                <ion-card
                  class="modal-item-card"
                  @dblclick="subItem.type === 'file' && isImageFile(subItem.name) && previewImage(subItem)"
                  :draggable="subItem.type === 'file'"
                  @dragstart="subItem.type === 'file' && handleDragStart($event, subItem)"
                  :class="{ 
                    'image-file': subItem.type === 'file' && isImageFile(subItem.name)
                  }"
                >
                  <ion-card-header>
                    <img
                      v-if="subItem.type === 'file' && isImageFile(subItem.name)"
                      :src="'https://alex.polan.sk/control-center/file_provider.php?path=' + subItem.location"
                      @error="imageStatus[subItem.location] = false"
                      @load="imageStatus[subItem.location] = true"
                    />
                    <ion-icon
                      v-else
                      :name="subItem.type === 'folder' ? 'folder' : 'document'"
                    ></ion-icon>
                    {{ shortenName(subItem.name) }}
                  </ion-card-header>
                </ion-card>
              </ion-col>
            </ion-row>
          </ion-grid>
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
  IonButton, 
  IonSpinner 
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
    IonSpinner,
  },
  data() {
    return {
      name: "",
      code: "",
      dragAndDropCapable: false,
      files: [],
      uploadPercentage: 0,
      fileSystem: [],
      newFolderName: "", // Neuer Ordnername
      imageStatus: {}, // Status der Bilder
      // Image preview data
      imagePreviewOpen: false,
      previewImageUrl: "",
      previewImageName: "",
      imageLoaded: false,
      imageError: false,
      // Folder modal data
      folderModalOpen: false,
      selectedFolder: null,
    };
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
          this.$refs.fileform.addEventListener(
            evt,
            function (e) {
              e.preventDefault();
              e.stopPropagation();
            }.bind(this),
            false
          );
        }.bind(this)
      );

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

    // Daten vom Server abrufen
    this.fetchFileSystemData();
  },
  methods: {
    shortenName(name) {
      if (name.length > 18) {
        return name.slice(0, 8) + "..." + name.slice(-7);
      }
      return name;
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
              this.$refs["preview" + parseInt(i)][0].src = reader.result;
            }.bind(this),
            false
          );

          reader.readAsDataURL(this.files[i]);
        } else {
          this.$nextTick(function () {
            this.$refs["preview" + parseInt(i)][0].src = "/images/file.png";
          });
        }
      }
    },

    handleDrop(event, folder) {
      // Keep old method for backward compatibility
      this.handleFolderDrop(event, folder);
    },

    handleRootDrop(event) {
      event.preventDefault();
      for (let i = 0; i < event.dataTransfer.files.length; i++) {
        this.files.push(event.dataTransfer.files[i]);
      }
      this.submit("");
    },

    submit(dir) {
      if (this.files.length > 0) {
        const formData = new FormData();

        for (let i = 0; i < this.files.length; i++) {
          const file = this.files[i];
          formData.append("files[" + i + "]", file);
          formData.append("name", file.name); // Dateiname als name-Parameter
        }

        formData.append("directory", dir);

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
            this.fetchFileSystemData(); // Dateisystemdaten nach dem Hochladen erneut abrufen
          })
          .catch((err) => {
            console.log("FAILURE!!", err);
          });
      } else {
        console.log("No files to upload");
      }
    },

    fetchFileSystemData() {
      axios
        .get("filesystem.php")
        .then((response) => {
          console.log('Raw file system data:', response.data); // Debug log
          this.fileSystem = this.processFileSystemData(response.data);
          console.log('Processed file system data:', this.fileSystem); // Debug log
        })
        .catch((error) => {
          console.error(
            "Es gab ein Problem beim Abrufen der Dateisystemdaten:",
            error
          );
        });
    },

    processFileSystemData(items) {
      return items.map(item => {
        const processedItem = { ...item };
        
        if (item.type === 'folder') {
          // Initialize folder-specific properties
          processedItem.open = false;
          processedItem.isDragOver = false;
          
          // Recursively process children if they exist
          if (item.children && Array.isArray(item.children) && item.children.length > 0) {
            processedItem.children = this.processFileSystemData(item.children);
          }
        }
        
        return processedItem;
      });
    },

    initializeFolderStates(items) {
      items.forEach(item => {
        if (item.type === 'folder') {
          // Initialize 'open' property if it doesn't exist
          if (!Object.prototype.hasOwnProperty.call(item, 'open')) {
            item.open = false;
          }
          // Initialize 'isDragOver' property
          if (!Object.prototype.hasOwnProperty.call(item, 'isDragOver')) {
            item.isDragOver = false;
          }
          
          // Recursively initialize children if they exist
          if (item.children && item.children.length > 0) {
            this.initializeFolderStates(item.children);
          }
        }
      });
    },

    createFolder() {
      if (this.newFolderName.trim() !== "") {
        const formData = new FormData();
        formData.append("name", this.newFolderName);
        formData.append("directory", ""); // "/" Wurzelverzeichnis oder aktuelles Verzeichnis

        axios
          .post("filesystem.php", formData)
          .then(() => {
            console.log("Ordner erfolgreich erstellt!");
            this.fetchFileSystemData(); // Dateisystemdaten nach dem Erstellen des Ordners erneut abrufen
            this.newFolderName = ""; // Eingabefeld zurücksetzen
          })
          .catch((err) => {
            console.log("Fehler beim Erstellen des Ordners:", err);
          });
      } else {
        console.log("Ordnername darf nicht leer sein");
      }
    },

    toggleFolder(folder) {
      // Commented out for now - using modal instead
      console.log('Toggling folder:', folder.name, 'current state:', folder.open);
      folder.open = !folder.open;
      console.log('New state:', folder.open);
      
      // Force Vue to update the DOM
      this.$forceUpdate();
    },

    // New folder modal methods
    openFolderModal(folder) {
      console.log('Opening folder modal for:', folder.name, 'children:', folder.children);
      this.selectedFolder = folder;
      this.folderModalOpen = true;
    },

    closeFolderModal() {
      this.folderModalOpen = false;
      this.selectedFolder = null;
    },

    // Image preview methods
    isImageFile(filename) {
      const imageExtensions = /\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i;
      return imageExtensions.test(filename);
    },

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

    removeFile(index) {
      this.files.splice(index, 1);
      this.getImagePreviews();
    },

    // Enhanced drag and drop for moving files between folders
    handleDragStart(event, file) {
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
      folder.isDragOver = true;
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
      folder.isDragOver = false;
      
      // Check if it's a file from computer or existing file
      const dragData = event.dataTransfer.getData('application/json');
      
      if (dragData) {
        // Moving existing file between folders
        const data = JSON.parse(dragData);
        if (data.type === 'existing-file') {
          this.moveFileToFolder(data.file, folder);
        }
      } else if (event.dataTransfer.files && event.dataTransfer.files.length > 0) {
        // Uploading new files from computer
        for (let i = 0; i < event.dataTransfer.files.length; i++) {
          this.files.push(event.dataTransfer.files[i]);
        }
        this.submit(folder.name);
      }
    },

    async moveFileToFolder(file, targetFolder) {
      try {
        const formData = new FormData();
        formData.append('action', 'move');
        formData.append('sourceFile', file.location);
        formData.append('targetFolder', targetFolder.name);
        
        const response = await axios.post('filesystem.php', formData);
        
        if (response.data.success) {
          console.log('File moved successfully!');
          this.fetchFileSystemData(); // Refresh the file system
        } else {
          console.error('Failed to move file:', response.data.message);
        }
      } catch (error) {
        console.error('Error moving file:', error);
      }
    },
  },
});
</script>

<style scoped>
ion-icon {
  margin-right: 8px;
}

form {
  display: none; /* Verstecke das alte Drop-Formular */
}

div.file-listing {
  width: 400px;
  margin: auto;
  padding: 10px;
  border-bottom: 1px solid #ddd;
}

div.file-listing img {
  height: 100px;
}

div.remove-container {
  text-align: center;
}

div.remove-container a {
  color: red;
  cursor: pointer;
}

a.submit-button {
  display: block;
  margin: auto;
  text-align: center;
  width: 200px;
  padding: 10px;
  text-transform: uppercase;
  background-color: #ccc;
  color: white;
  font-weight: bold;
  margin-top: 20px;
}

progress {
  width: 400px;
  margin: auto;
  display: block;
  margin-top: 20px;
  margin-bottom: 20px;
}

ion-card {
  cursor: pointer;
  transition: transform 0.2s;
  aspect-ratio: 1 / 1;
  padding: 0;
  text-align: center;
  min-height: 120px; /* Ensure minimum height for mobile */
}

/* Mobile optimizations */
@media (max-width: 768px) {
  ion-card {
    min-height: 100px;
    margin-bottom: 8px;
  }
  
  ion-card-header {
    padding: 4px !important;
    font-size: 0.85rem;
  }
  
  ion-card-header > img {
    max-height: 60%;
  }
  
  ion-card-header > ion-icon {
    height: 60%;
    width: 60%;
  }
  
  .file-system-grid {
    padding: 8px;
  }
}

/* Tablet optimizations */
@media (min-width: 769px) and (max-width: 1024px) {
  ion-card {
    min-height: 110px;
  }
  
  ion-card-header {
    padding: 6px;
    font-size: 0.9rem;
  }
}

ion-row {
  display: flex;
  display: -webkit-flex;
  flex-wrap: wrap;
}

ion-card:hover {
  transform: scale(1.05);
}

.drop-files {
  font-size: 18px;
  color: #333;
}

.file-listing {
  display: flex;
  align-items: center;
}

.file-listing img.preview {
  margin-right: 10px;
}

.file-listing .remove-container {
  margin-left: auto;
}

ion-card-header {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  height: 100%;
  padding: 8px;
}

ion-card-header > img {
  height: 75%;
  width: 75%;
  object-fit: cover;
  border-radius: 4px;
}

ion-card-header > ion-icon {
  height: 75%;
  width: 75%;
}

.image-preview-modal {
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
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
  color: var(--ion-color-danger);
}

.error-message ion-icon {
  font-size: 48px;
  margin-bottom: 10px;
}

.preview-title {
  margin-top: 16px;
  text-align: center;
  font-weight: 500;
  color: var(--ion-color-dark);
  word-break: break-all;
}

/* Enhanced hover effect for image files */
ion-card.image-file:hover {
  transform: scale(1.08);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

ion-card.image-file {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

/* Cursor pointer for clickable image files */
ion-card.image-file {
  cursor: pointer;
}

/* Drag and drop styling */
ion-card[draggable="true"] {
  cursor: grab;
}

ion-card[draggable="true"]:active {
  cursor: grabbing;
}

ion-card.drag-over {
  background-color: var(--ion-color-primary-tint);
  border: 2px dashed var(--ion-color-primary);
  transform: scale(1.02);
}

ion-card.drag-over ion-card-header {
  opacity: 0.8;
}

/* Folder creation styling */
.folder-creation {
  display: flex;
  gap: 10px;
  margin: 16px;
  align-items: center;
}

.folder-input {
  flex: 1;
  padding: 12px;
  border: 2px solid var(--ion-color-light);
  border-radius: 8px;
  font-size: 16px;
  background: var(--ion-color-light-tint);
}

.folder-input:focus {
  border-color: var(--ion-color-primary);
  outline: none;
}

.folder-button {
  padding: 12px 20px;
  background: var(--ion-color-primary);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  white-space: nowrap;
}

.folder-button:hover {
  background: var(--ion-color-primary-shade);
}

/* Progress bar mobile optimization */
progress {
  width: 100%;
  max-width: 400px;
  margin: auto;
  display: block;
  margin-top: 20px;
  margin-bottom: 20px;
}

/* Mobile specific adjustments */
@media (max-width: 768px) {
  .folder-creation {
    flex-direction: column;
    margin: 12px;
  }
  
  .folder-input {
    width: 100%;
    margin-bottom: 8px;
  }
  
  .folder-button {
    width: 100%;
    padding: 14px;
  }
  
  progress {
    width: calc(100% - 32px);
    margin: 16px;
  }
  
  .drop-files {
    font-size: 16px;
    padding: 16px;
  }
  
  #file-drag-drop {
    margin: 16px;
  }
  
  .sub-item-card {
    min-height: 80px;
  }
  
  .sub-item-card ion-card-header {
    padding: 4px;
    font-size: 0.75rem;
  }
}

/* Sub-item cards styling */
.sub-item-card {
  cursor: pointer;
  transition: transform 0.2s;
  aspect-ratio: 1 / 1;
  padding: 0;
  text-align: center;
  min-height: 100px;
  margin: 4px 0;
  background: var(--ion-color-light-tint);
  border: 1px solid var(--ion-color-light-shade);
}

.sub-item-card:hover {
  transform: scale(1.03);
  background: var(--ion-color-light);
}

.sub-item-card ion-card-header {
  padding: 6px;
  font-size: 0.8rem;
}

.sub-item-card ion-card-header > img {
  height: 60%;
  width: 60%;
  object-fit: cover;
  border-radius: 3px;
}

.sub-item-card ion-card-header > ion-icon {
  height: 60%;
  width: 60%;
}

/* Modal item cards styling */
.modal-item-card {
  cursor: pointer;
  transition: transform 0.2s;
  aspect-ratio: 1 / 1;
  padding: 0;
  text-align: center;
  min-height: 120px;
  margin: 8px 0;
  background: var(--ion-color-light);
  border: 1px solid var(--ion-color-medium);
}

.modal-item-card:hover {
  transform: scale(1.05);
  background: var(--ion-color-light-shade);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.modal-item-card ion-card-header {
  padding: 8px;
  font-size: 0.85rem;
}

.modal-item-card ion-card-header > img {
  height: 70%;
  width: 70%;
  object-fit: cover;
  border-radius: 4px;
}

.modal-item-card ion-card-header > ion-icon {
  height: 70%;
  width: 70%;
}

.folder-modal-content {
  padding: 16px;
}
</style>
