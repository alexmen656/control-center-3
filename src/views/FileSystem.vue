<template>
  <ion-page>
    <ion-content>
      <div class="file-system-grid" @dragover.prevent @drop="handleRootDrop">
        <ion-grid>
          <ion-row>
            <ion-col size="2" v-for="item in fileSystem" :key="item.name">
              <ion-card
                @click="item.type === 'folder' && toggleFolder(item)"
                @dragover.prevent
                @drop="item.type === 'folder' && handleDrop($event, item)"
              >
                <ion-card-header>
                  <img
                    v-if="item.type === 'file' && imageStatus[item.location]"
                    :src="
                      'https://alex.polan.sk/control-center/file_provider.php?path=' +
                      item.location
                    "
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
                <ion-card-content v-if="item.open && item.type === 'folder'">
                  <ion-grid>
                    <ion-row>
                      <ion-col
                        size="12"
                        v-for="subItem in item.children"
                        :key="subItem.name"
                      >
                        <ion-icon
                          :name="
                            subItem.type === 'folder' ? 'folder' : 'document'
                          "
                        ></ion-icon>
                        {{ subItem.name }}
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
          <input v-model="newFolderName" placeholder="Neuer Ordnername" />
          <button @click="createFolder">Ordner erstellen</button>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import { IonPage, IonContent, IonIcon } from "@ionic/vue";
import axios from "axios";

export default defineComponent({
  name: "FileSystem",
  components: {
    IonPage,
    IonContent,
    IonIcon,
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
    async getImage(location) {
      console.log(1);
      try {
        const res = await axios.get(
          "https://alex.polan.sk/control-center/file_provider.php?path=" +
            location
        );
        this.imageStatus[location] = res.status === 200;
      } catch (error) {
        this.imageStatus.push(location, false);
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
      event.preventDefault();
      for (let i = 0; i < event.dataTransfer.files.length; i++) {
        this.files.push(event.dataTransfer.files[i]);
      }
      this.submit(folder.name);
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
          this.fileSystem = response.data;
          // Bildstatus für jedes Element abrufen
          this.fileSystem.forEach((item) => {
            if (item.type === "file") {
              this.getImage(item.location);
            }
          });
        })
        .catch((error) => {
          console.error(
            "Es gab ein Problem beim Abrufen der Dateisystemdaten:",
            error
          );
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
      folder.open = !folder.open;
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
}

ion-card-header > img, ion-icon {
  height: 75%;
  width: 75%;
}
</style>
