<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="12" size-lg="8">
            <ion-item
              @dblclick="
                code = name
                  .toLowerCase()
                  .replaceAll(' ', '-')
                  .replaceAll(`'`, '')
              "
            >
              <ion-label position="floating">Name</ion-label>
              <ion-input
                type="text"
                v-model="name"
                :value="name"
                @ionInput="name = $event.target.value"
                placeholder="Enter Icon Code"
              />
            </ion-item>
          </ion-col>
          <ion-col size="12" size-lg="8">
            <ion-item>
              <ion-label position="floating">Code</ion-label>
              <ion-input
                v-model="code"
                :value="code"
                @ionInput="code = $event.target.value"
                type="text"
                placeholder="Enter Project Name"
              />
            </ion-item>
          </ion-col>
          <ion-col size="12" size-lg="8">
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
            <ion-button @click="submit()"> Create </ion-button>
            <ion-button @click="submitMenu()"> Create Menu</ion-button>
          </ion-col>
          <!--<ion-col size="12" size-lg="8">
            Create new Menu:

            <ion-item
              @dblclick="
                code = name
                  .toLowerCase()
                  .replaceAll(' ', '-')
                  .replaceAll(`'`, '')
              "
            >
              <ion-label position="floating">Name</ion-label>
              <ion-input
                type="text"
                v-model="name"
                :value="name"
                @ionInput="name = $event.target.value"
                placeholder="Enter Icon Code"
              />
            </ion-item>
          </ion-col>
          <ion-col size="12" size-lg="8">
            <ion-item>
              <ion-label position="floating">Code</ion-label>
              <ion-input
                v-model="code"
                :value="code"
                @ionInput="code = $event.target.value"
                type="text"
                placeholder="Enter Project Name"
              />
            </ion-item>
          </ion-col>-->
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import axios from "axios";
import qs from "qs";
import {
  IonPage,
  IonGrid,
  IonContent,
  IonCol,
  IonInput,
  IonLabel,
  IonRow,
  IonItem,
  IonButton,
} from "@ionic/vue";

export default {
  name: "NewProject",
  components: {
    IonPage,
    IonGrid,
    IonContent,
    IonCol,
    IonInput,
    IonLabel,
    IonRow,
    IonItem,
    IonButton,
  },
  data() {
    return {
      name: "",
      code: "",
      dragAndDropCapable: false,
      files: [],
      uploadPercentage: 0,
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
  },
  methods: {
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

    submit() {
      if (this.name != "" && this.code != "") {
        const formData = new FormData();

        for (let i = 0; i < this.files.length; i++) {
          const file = this.files[i];

          formData.append("files[" + i + "]", file);
        }

        formData.append("name", this.name);
        formData.append("code", this.code);
        formData.append("project", this.$route.params.project);
        formData.append("newComponent", "newComponent");

        axios
          .post("/control-center/components.php", formData, {
            headers: {
              "Content-Type": "multipart/form-data",
            },
            onUploadProgress: function (progressEvent) {
              this.uploadPercentage = parseInt(
                Math.round((progressEvent.loaded * 100) / progressEvent.total)
              );
            }.bind(this),
          })
          .then(function () {
            console.log("SUCCESS!!");
          })
          .catch(function () {
            console.log("FAILURE!!");
          });
      } else {
        alert("Name or Code empty!");
      }
    },
    submitMenu() {
      if (this.name != "" && this.code != "") {
        axios
          .post(
            "/control-center/components.php",
            qs.stringify({
              name: this.name,
              code: this.code,
              project: this.$route.params.project,
              newComponent: "newComponent",
              type: "menu",
            })
          )
          .then(() => {
            console.log("SUCCESS!!");
          })
          .catch(() => {
            console.log("FAILURE!!");
          });
      } else {
        alert("Name or Code empty!");
      }
    },
    removeFile(key) {
      this.files.splice(key, 1);
    },
  },
};
</script>
<style>
form {
  display: block;
  height: 400px;
  width: 400px;
  background: #ccc;
  margin: auto;
  margin-top: 40px;
  text-align: center;
  line-height: 400px;
  border-radius: 4px;
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
</style>
