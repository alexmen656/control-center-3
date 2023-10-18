<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col />
          <ion-col size="11">
            <h1
              style="
                text-align: center;
                margin-top: 5rem;
                margin-bottom: 5rem;
                font-size: 4rem;
              "
            >
              Module Store
            </h1>
            <ion-searchbar
              style="--border-radius: 1rem"
              @ionInput="handleInput($event)"
            />
            <ion-grid>
              <ion-row v-if="results.length > 0">
                <modules-list-item :modules="results" />
              </ion-row>
              <ion-row v-else>
                <modules-list-item :modules="modules" />
              </ion-row>
            </ion-grid>
          </ion-col>
          <ion-col />
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>
<script>
import lunr from "lunr";
import axios from "axios";
import qs from "qs";
import ModulesListItem from "@/components/ModulesListItem.vue";
import {
  IonSearchbar,
  IonPage,
  IonContent,
  IonGrid,
  IonRow,
  IonCol,
} from "@ionic/vue";

export default {
  components: {
    IonSearchbar,
    IonPage,
    IonContent,
    IonGrid,
    IonRow,
    IonCol,
    ModulesListItem,
  },
  data() {
    return {
      modules: [],
      keyword: "",
      searchIndex: [],
      results: [],
    };
  },
  mounted() {
    this.emitter.on("install", (data) => {
      this.install(data.moduleID, data.index);
    });

    this.emitter.on("deinstall", (data) => {
      this.deinstall(data.moduleID, data.index);
    });

    this.$watch(
      () => this.keyword,
      () => {
        this.results = [];

        if (this.keyword != "") {
          this.searchIndex.search(this.keyword).forEach((result) => {
            this.results.push(
              this.modules.find(({ ref }) => ref === result.ref)
            );
          });
        }
      }
    );
  },
  async created() {
    const installedModules = [];
    axios
      .post(
        "/control-center/modules.php",
        qs.stringify({ project: this.$route.params.project })
      )
      .then((response) => {
        if (response.data) {
          response.data.forEach((module) => {
            installedModules.push(module.name);
          });
        }
      });

    axios
      .post(
        "/control-center/form.php",
        qs.stringify({
          get_form_data: "get_form_data",
          form: "modules",
          project: "module_store",
        })
      )
      .then((search) => {
        this.searchIndex = lunr(function () {
          this.ref("ref");
          this.field("name");

          search.data.forEach((doc) => {
            this.add({ ref: doc.ref, name: doc.name });
          });
        });
      });

    const response = await axios.post(
      "/control-center/form.php",
      qs.stringify({
        get_form_data: "get_form_data",
        form: "modules",
        project: "module_store",
      })
    );

    response.data.map((module) => {
      module.progress = 0;
      let block = false;
      if (!block) {
        module.status = "not_installed";
      }
      installedModules.forEach((installedModule) => {
        if (module.display_name == installedModule) {
          module.status = "installed";
          block = true;
        }
      });
    });

    this.modules = response.data;
  },
  methods: {
    handleInput(event) {
      this.keyword = event.target.value;
    },
    install(moduleID, index) {
      axios.post(
        "https://alex.polan.sk/control-center/install.php",
        qs.stringify({
          install: "install",
          moduleID: moduleID,
          project: this.$route.params.project,
        })
      );
      this.modules[index].status = "installing";
      const intervalId = setInterval(() => {
        if (this.modules[index].progress < 100) {
          this.modules[index].progress = this.modules[index].progress + 4;
        } else {
          this.modules[index].status = "installed";
          clearInterval(intervalId);
        }
      }, 480);
    },
    deinstall(moduleID, index) {
      axios
        .post(
          "https://alex.polan.sk/control-center/install.php",
          qs.stringify({
            deinstall: "deinstall",
            moduleID: moduleID,
            project: this.$route.params.project,
          })
        )
        .then(() => {
          this.modules[index].status = "not_installed";
        });
    },
  },
};
</script>
<style scoped>
@media (prefers-color-scheme: dark) {
  ion-searchbar,
  .bg-card {
    --background: #000000;
  }
}

@media (prefers-color-scheme: light) {
  ion-searchbar {
    --background: #e9e9e9;
  }
}
</style>
