<template>
  <ion-page>
    <!--   <ion-header>
        <ion-toolbar>
          <ion-title>Projektübersicht</ion-title>
        </ion-toolbar>
      </ion-header>-->
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1" />
          <ion-col size="10">
            <ion-card>
              <h2 class="info-card-heading">Tools</h2>
              <ion-list v-if="tools.length > 0">
                <ion-item v-for="tool in tools" :key="tool.id">
                  <ion-icon v-if="tool.icon" :name="tool.icon" />
                  <ion-label>
                    <h2 @click="goToTool(tool.name)">
                      {{
                        tool.name.charAt(0).toUpperCase() + tool.name.slice(1)
                      }}
                    </h2>
                    <!--  <p>Zugriffsrechte:</p>-->
                  </ion-label>
                </ion-item>
              </ion-list>
              <ion-item v-else>
                <ion-label>
                  <h2>No tools yet</h2>
                </ion-label>
              </ion-item>
            </ion-card>
            <ion-card>
              <h2 class="info-card-heading">
                Components
                <div
                  style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                  "
                >
                  <ion-icon @click="openWebBuilder()" name="globe-outline" style="margin-right: 8px;" />
                  <ion-icon @click="exportWeb()" name="download-outline" />
                  <ion-icon @click="viewWWW()" name="earth-outline" />
                </div>
              </h2>

              <a
                v-if="downloadLink"
                :href="
                  'https://alex.polan.sk/control-center/website_builder/exports/' +
                  downloadLink
                "
                download
                >{{ downloadLink }}</a
              >
              <ion-list v-if="components && components.length > 0">
                <ion-item v-for="component in components" :key="component.id">
                  <ion-icon
                    v-if="component.type == 'script'"
                    name="code-slash-outline"
                  ></ion-icon>
                  <ion-icon
                    v-if="component.type == 'image'"
                    name="image-outline"
                  ></ion-icon>
                  <ion-label>
                    <h2>
                      {{
                        component.name.charAt(0).toUpperCase() +
                        component.name.slice(1)
                      }}
                    </h2>
                    <p>
                      Type:
                      {{
                        component.type.charAt(0).toUpperCase() +
                        component.type.slice(1)
                      }}
                    </p>
                  </ion-label>
                </ion-item>
              </ion-list>
              <ion-item v-else>
                <ion-label>
                  <h2>No components yet</h2>
                </ion-label>
              </ion-item>
            </ion-card>

            <ion-card>
              <h2 class="info-card-heading">
                Users
                <div
                  style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                  "
                >
                  <ion-icon @click="setOpen(true)" name="add" />
                </div>
              </h2>
              <ion-list v-if="users.length > 0">
                <ion-item v-for="user in users" :key="user.id">
                  <!-- <ion-icon v-if="tool.icon" :name="tool.icon" />-->
                  <ion-label>
                    <h2>
                      {{
                        user.name.charAt(0).toUpperCase() + user.name.slice(1)
                      }}
                    </h2>
                    <p>Permissions: Read, Edit & Write</p>
                  </ion-label>
                </ion-item>
              </ion-list>
              <ion-item v-else>
                <ion-label>
                  <h2>An error occured</h2>
                </ion-label>
              </ion-item>
            </ion-card>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
      <!--<ion-list>
          <ion-item v-for="(user, index) in users" :key="index">
            <ion-label>
              <h2>{{ user.name }}</h2>
              <p>Zugriffsrechte: {{ user.access }}</p>
            </ion-label>
          </ion-item>
        </ion-list>-->

      <!-- Modal -->
      <ion-modal :is-open="isOpen" ref="modal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button color="primary" @click="cancel()">Cancel</ion-button>
            </ion-buttons>
            <ion-title style="text-align: center">Invite User</ion-title>
            <ion-buttons slot="end">
              <ion-button color="primary" :strong="true" @click="confirm()"
                >Confirm</ion-button
              >
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <FloatingInput
            defaultVal=""
            label="Email"
            placeholder="john.due@control-center.eu"
            type="email"
            v-model="email"
          />
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import FloatingInput from "@/components/FloatingInput.vue";
import { ref } from "vue";

export default {
  name: "ProjectView",
  components: {
    FloatingInput,
  },
  data() {
    return {
      /*  users: [
        { name: "Benutzer 1", access: "Lesezugriff" },
        { name: "Benutzer 2", access: "Schreibzugriff" },
        { name: "Benutzer 3", access: "Adminzugriff" },
        // Füge hier weitere Benutzer mit ihren Zugriffsrechten hinzu
      ],*/
      tools: [],
      components: [],
      users: [],
      downloadLink: "",
      email: "",
    };
  },
  setup() {
    const isOpen = ref(false);
    const setOpen = (open) => {
      isOpen.value = open;
      console.log(1);
    };

    return {
      isOpen,
      setOpen,
    };
  },
  created() {
    this.$axios
      .get("sidebar.php?getSideBarByProjectName=" + this.$route.params.project)
      .then((response) => {
        this.tools = response.data.tools;
        this.components = response.data.components;
      });
    this.$axios
      .post(
        "projects.php",
        this.$qs.stringify({
          getProjectUsers: "getProjectUsers",
          project: this.$route.params.project,
        })
      )
      .then((response2) => {
        this.users = response2.data;
      });
  },
  methods: {
    viewWWW() {
      window
        .open("https://alex.polan.sk/" + this.$route.params.project, "_blank")
        .focus();
    },
    openWebBuilder() {
      // Öffne den Web Builder für das aktuelle Projekt
      const project = this.$route.params.project;
      
      // Konstruiere URL für den Web Builder
      const url = `https://web-builder.control-center.eu/project/${project}`;
      
      // Öffne in einem neuen Tab
      window.open(url, '_blank').focus();
    },
    exportWeb() {
      this.$axios.post("website_builder/export.php").then((response) => {
        this.downloadLink = response.data;
      });
    },
    cancel() {
      this.setOpen(false);
    },
    async confirm() {
      this.setOpen(false);
      this.$axios
        .post(
          "projects.php",
          this.$qs.stringify({
            addUserToProject: "addUserToProject",
            project: this.$route.params.project,
            email: this.email,
          })
        )
        .then(() => {
          alert("User Invite Success");
        });
    },
    goToTool(tool) {
      //alert("/project/"+this.$route.params.project+"/"+tool.toLowerCase().replaceAll(" ", "-"));
      if (tool.toLowerCase().includes("dashboard-")) {
        this.$router.push(
          "/project/" +
            this.$route.params.project +
            "/dashboard/" +
            tool.toLowerCase().replaceAll(" ", "-")
        );
      }else{
        this.$router.push(
          "/project/" +
            this.$route.params.project +
            "/" +
            tool.toLowerCase().replaceAll(" ", "-")
        );
      }
    },
  },
};
</script>
<style scoped>
ion-card {
  border-radius: 28px;
}
ion-icon {
  margin-right: 0.75rem;
}

ion-card:first-of-type,
ion-card:nth-of-type(2) {
  margin-bottom: 1rem;
}

@media (prefers-color-scheme: dark) {
  ion-list,
  ion-item {
    background: black;
    --background: black;
  }
}

.info-card-heading {
  padding-left: 0.8rem;
  margin-top: 8px;
  margin-bottom: 4px;
  display: flex;
  justify-content: space-between;
}
</style>
