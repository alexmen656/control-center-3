<template>
  <ion-page>
    <ion-content>
      <LoadingSpinner v-if="loading" />
      <ion-grid v-if="!loading">
        <ion-row>
          <ion-col></ion-col>
          <ion-col size="11">
            <ion-list>
              <ion-item>
                <ion-label position="floating">Project Name</ion-label>
                <ion-input disabled :value="projectName"></ion-input>
              </ion-item>
              <ion-item>
                <ion-label>Vercel Project</ion-label>
                <template v-if="loadingVercelProject">
                  <ion-spinner name="crescent" />
                </template>
                <template v-else>
                  <div v-if="connectedVercelProject">
                    <ion-badge color="success">{{ connectedVercelProject.name }}</ion-badge>
                  </div>
                  <div v-else>
                    <ion-button size="small" @click="onOpenVercelModal">Vercel-Projekt verbinden</ion-button>
                  </div>
                </template>
              </ion-item>
    <ion-modal :is-open="openVercelModal" @didDismiss="openVercelModal = false">
      <ion-header>
        <ion-toolbar color="primary">
          <ion-title>Vercel-Projekt verbinden</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="openVercelModal = false">Schließen</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content>
        <ion-list>
          <ion-item v-for="project in vercelProjects" :key="project.id" @click="connectVercelProject(project)" button>
            <ion-label>{{ project.name }}</ion-label>
          </ion-item>
        </ion-list>
        <ion-text color="danger" v-if="vercelError">{{ vercelError }}</ion-text>
      </ion-content>
    </ion-modal>
              <ion-item>
                <ion-label>GitHub Repository</ion-label>
                <template v-if="loadingRepo">
                  <ion-spinner name="crescent" />
                </template>
                <template v-else>
                  <div v-if="connectedRepo">
                    <a :href="connectedRepo.repo_html_url || repoUrl(connectedRepo)" target="_blank">
                      {{ connectedRepo.repo_full_name }}
                    </a>
                  </div>
                  <div v-else>
                    <ion-button @click="onOpenRepoModal" size="small">Repo verbinden</ion-button>
                  </div>
                </template>
              </ion-item>
              <ion-item>
                <ion-label>Project Domain</ion-label>
                <template v-if="loadingDomain">
                  <ion-spinner name="crescent" />
                </template>
                <template v-else>
                  <div v-if="connectedDomain">
                    <ion-badge color="primary">{{ connectedDomain }}</ion-badge>
                  </div>
                  <div v-else>
                    <ion-input v-model="domainInput" placeholder="subdomain" style="max-width:180px;display:inline-block;" />
                    <span>.sites.control-center.eu</span>
                    <ion-button size="small" @click="connectDomain" :disabled="!domainInput || domainInput.length < 3">Connect</ion-button>
                  </div>
                </template>
              </ion-item>
              <ion-item v-if="domainError">
                <ion-text color="danger">{{ domainError }}</ion-text>
              </ion-item>
              <ion-item>
                <ion-label position="floating">Created On</ion-label>
                <ion-input disabled :value="creationDate"></ion-input>
              </ion-item>
                         <!--  <ion-item>
          <ion-label>Internes Projekt?</ion-label>
          <ion-checkbox v-model="isInternal"></ion-checkbox>
        </ion-item>
        <ion-item>
          <ion-label>Öffentliche Website?</ion-label>
          <ion-checkbox v-model="hasWebsite"></ion-checkbox>
        </ion-item>-->
            </ion-list>
          </ion-col>
          <ion-col></ion-col>
        </ion-row>
      </ion-grid>
    <ion-modal :is-open="openRepoModal" @didDismiss="openRepoModal = false">
      <ion-header>
        <ion-toolbar color="primary">
          <ion-title>GitHub Repo verbinden</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="openRepoModal = false">Schließen</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content>
        <ion-list>
          <ion-item v-for="repo in repos" :key="repo.id" @click="connectRepo(repo)" button>
            <ion-label>{{ repo.full_name }}</ion-label>
          </ion-item>
        </ion-list>
        <ion-text color="danger" v-if="repoError">{{ repoError }}</ion-text>
      </ion-content>
    </ion-modal>
  </ion-content>
  </ion-page>
</template>

<script>

import LoadingSpinner from "@/components/LoadingSpinner.vue";
import { getUserData } from "@/userData";

export default {
  name: "ProjectInfo",
  components: {
    LoadingSpinner,
  },
  data() {
    return {
      projectName: "",
      creationDate: "",
      loading: true,
      loadingRepo: true,
      connectedRepo: null,
      repos: [],
      openRepoModal: false,
      repoError: '',
      loadingDomain: true,
      connectedDomain: null,
      domainInput: '',
      domainError: '',
      loadingVercelProject: true,
      connectedVercelProject: null,
      openVercelModal: false,
      vercelProjects: [],
      vercelError: '',
    };
  },
  methods: {
    onOpenVercelModal() {
      this.openVercelModal = true;
      this.fetchVercelProjects();
    },
    async fetchVercelProjects() {
      this.vercelProjects = [];
      this.vercelError = '';
      try {
        const user = getUserData();
        if (!user || !user.userID) {
          this.vercelError = 'Kein User.';
          return;
        }
        const res = await this.$axios.get(`vercel_projects.php?user_id=${user.userID}`);
        if (Array.isArray(res.data.projects)) {
          this.vercelProjects = res.data.projects;
        } else {
          this.vercelError = 'Fehler beim Laden der Vercel-Projekte.';
        }
      } catch (e) {
        this.vercelError = 'Fehler beim Laden der Vercel-Projekte.';
      }
    },
    async fetchConnectedVercelProject() {
      this.loadingVercelProject = true;
      try {
        const res = await this.$axios.post('project_vercel.php', this.$qs.stringify({
          action: 'get',
          project: this.$route.params.project,
        }));
        if (res.data && res.data.vercel_project_id) {
          this.connectedVercelProject = { id: res.data.vercel_project_id, name: res.data.vercel_project_name };
        } else {
          this.connectedVercelProject = null;
        }
      } catch (e) {
        this.connectedVercelProject = null;
      } finally {
        this.loadingVercelProject = false;
      }
    },
    async connectVercelProject(project) {
      this.vercelError = '';
      try {
        const user = getUserData();
        if (!user || !user.userID) {
          this.vercelError = 'Kein User.';
          return;
        }
        const res = await this.$axios.post('project_vercel.php', this.$qs.stringify({
          action: 'connect',
          project: this.$route.params.project,
          user_id: user.userID,
          vercel_project_id: project.id,
          vercel_project_name: project.name,
        }));
        if (res.data && res.data.success) {
          this.openVercelModal = false;
          this.fetchConnectedVercelProject();
        } else {
          this.vercelError = res.data && res.data.error ? res.data.error : 'Fehler beim Verbinden.';
        }
      } catch (e) {
        this.vercelError = 'Fehler beim Verbinden.';
      }
    },
    onOpenRepoModal() {
      this.openRepoModal = true;
      this.fetchRepos();
    },
    repoUrl(repo) {
      if (repo && repo.repo_full_name) {
        return `https://github.com/${repo.repo_full_name}`;
      }
      return '#';
    },
    async fetchRepos() {
      this.repos = [];
      this.repoError = '';
      try {
        const user = getUserData();
        if (!user || !user.userID) {
          this.repoError = 'Kein User.';
          return;
        }
        const res = await this.$axios.get(`github_repos.php?userID=${user.userID}`);
        if (Array.isArray(res.data)) {
          this.repos = res.data;
        } else if (res.data && res.data.error) {
          this.repoError = res.data.error;
        } else {
          this.repoError = 'Fehler beim Laden der Repos.';
        }
      } catch (e) {
        this.repoError = 'Fehler beim Laden der Repos.';
      }
    },
    async fetchConnectedRepo() {
      this.loadingRepo = true;
      try {
        const res = await this.$axios.post('project_repo.php', this.$qs.stringify({
          action: 'get',
          project: this.$route.params.project,
        }));
        this.connectedRepo = res.data.repo;
      } catch (e) {
        this.connectedRepo = null;
      } finally {
        this.loadingRepo = false;
      }
    },
    async fetchConnectedDomain() {
      this.loadingDomain = true;
      this.domainError = '';
      try {
        const user = getUserData();
        const res = await this.$axios.post('project_domain.php', this.$qs.stringify({
          action: 'get',
          project: this.$route.params.project,
        }));
        this.connectedDomain = res.data.domain;
      } catch (e) {
        this.connectedDomain = null;
      } finally {
        this.loadingDomain = false;
      }
    },
    async connectDomain() {
      this.domainError = '';
      const user = getUserData();
      if (!user || !user.userID) {
        this.domainError = 'Kein User.';
        return;
      }
      if (!/^[a-z0-9-]+$/.test(this.domainInput)) {
        this.domainError = 'Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt.';
        return;
      }
      try {
        const res = await this.$axios.post('project_domain.php', this.$qs.stringify({
          action: 'connect',
          project: this.$route.params.project,
          user_id: user.userID,
          domain: this.domainInput,
        }));
        if (res.data && res.data.success) {
          this.connectedDomain = res.data.domain;
          this.domainInput = '';
        } else {
          this.domainError = res.data && res.data.error ? res.data.error : 'Fehler beim Verbinden.';
        }
      } catch (e) {
        this.domainError = 'Fehler beim Verbinden.';
      }
    },
    async connectRepo(repo) {
      this.repoError = '';
      try {
        const user = getUserData();
        if (!user || !user.userID) {
          this.repoError = 'Kein User.';
          return;
        }
        const res = await this.$axios.post('project_repo.php', this.$qs.stringify({
          action: 'connect',
          project: this.$route.params.project,
          user_id: user.userID,
          repo: JSON.stringify(repo),
        }));
        if (res.data && res.data.success) {
          this.openRepoModal = false;
          this.fetchConnectedRepo();
        } else {
          this.repoError = res.data && res.data.error ? res.data.error : 'Fehler beim Verbinden.';
        }
      } catch (e) {
        this.repoError = 'Fehler beim Verbinden.';
      }
    },
  },
  async created() {
    this.$axios
      .post(
        "projects.php",
        this.$qs.stringify({
          getProjectInfo: "getProjectInfo",
          project: this.$route.params.project,
        })
      )
      .then((res) => {
        this.projectName = res.data.name;
        this.creationDate = new Date(res.data.createdOn)
          .toLocaleDateString("en-GB")
          .replaceAll("/", ".");
        this.loading = false;
      });
    this.fetchConnectedRepo();
    this.fetchConnectedDomain();
    this.fetchConnectedVercelProject();
  },
};
</script>
