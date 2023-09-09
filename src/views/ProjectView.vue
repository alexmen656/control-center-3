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
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <ion-card>
              <h2>Tools:</h2>
              <ion-list v-if="tools.length > 0">
                <ion-item v-for="tool in tools" :key="tool.id">
                  <ion-icon v-if="tool.icon" :name="tool.icon"></ion-icon>
                  <ion-label>
                    <h2>
                      {{
                        tool.name.charAt(0).toUpperCase() + tool.name.slice(1)
                      }}
                    </h2>
                    <p>Zugriffsrechte:</p>
                  </ion-label>
                </ion-item>
              </ion-list>
              <ion-item v-else>
                <ion-label>
                  <h2>
                    No tools yet
                  </h2>
                </ion-label>
              </ion-item>
            </ion-card>
            <ion-card>
              <h2>Components:</h2>
              <ion-list v-if="components.length > 0">
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
                  <h2>
                    No components yet
                  </h2>
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
    </ion-content>
  </ion-page>
</template>

<script>
import axios from "axios";

export default {
  name: "ProjectView",
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
    };
  },
  created() {
    this.$router.push("/project/"+this.$route.params.project+"/qr-code-scanner/");
    axios
      .get(
        "https://alex.polan.sk/control-center/sidebar.php?getSideBarByProjectName=" +
          this.$route.params.project
      )
      .then((response) => {
        this.tools = response.data.tools;
        this.components = response.data.components;
      });
  },
};
</script>
<style scoped>
ion-icon {
  margin-right: 0.75rem;
}

ion-card:first-of-type {
  margin-bottom: 2rem;
}
</style>
