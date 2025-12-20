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

              <!-- Custom Login Section -->
              <ion-item-divider>
                <ion-label>Custom Login Domain</ion-label>
              </ion-item-divider>

              <!-- Domain Type Selection -->
              <ion-item>
                <ion-label>Domain-Typ</ion-label>
                <ion-select v-model="customLogin.domain_type" interface="popover" :disabled="customLogin.id">
                  <ion-select-option value="internal">Subdomain von control-center.eu (automatisch)</ion-select-option>
                  <ion-select-option value="external">Externe Domain (manuelles Setup)</ion-select-option>
                </ion-select>
              </ion-item>

              <!-- Internal Domain Input -->
              <ion-item v-if="customLogin.domain_type === 'internal'">
                <ion-label position="stacked">Subdomain</ion-label>
                <div style="display: flex; align-items: center; width: 100%;">
                  <ion-input 
                    v-model="customLogin.subdomain" 
                    placeholder="meinefirma"
                    :disabled="loadingCustomLogin || customLogin.id"
                    style="max-width: 200px;"
                    @ionInput="updateInternalDomain"
                  ></ion-input>
                  <span style="margin-left: 8px; color: var(--ion-color-medium);">.control-center.eu</span>
                </div>
              </ion-item>

              <!-- External Domain Input -->
              <ion-item v-if="customLogin.domain_type === 'external'">
                <ion-label position="stacked">Externe Domain</ion-label>
                <ion-input 
                  v-model="customLogin.domain" 
                  placeholder="login.meinefirma.de"
                  :disabled="loadingCustomLogin || customLogin.id"
                ></ion-input>
              </ion-item>

              <!-- Setup Instructions for External Domains -->
              <template v-if="customLogin.domain_type === 'external' && customLogin.id">
                <ion-item-divider color="warning">
                  <ion-label>⚠️ Manuelles Setup erforderlich</ion-label>
                </ion-item-divider>
                <ion-item>
                  <ion-label class="ion-text-wrap">
                    <h3>1. DNS Konfiguration</h3>
                    <p>Erstelle einen A-Record bei deinem DNS Provider:</p>
                    <code style="display: block; background: var(--ion-color-light); padding: 8px; margin: 8px 0; border-radius: 4px;">
                      Typ: A<br>
                      Name: {{ customLogin.domain }}<br>
                      Wert: 92.5.112.145
                    </code>
                  </ion-label>
                </ion-item>
                <ion-item>
                  <ion-label class="ion-text-wrap">
                    <h3>2. Nginx & SSL</h3>
                    <p>Nginx und SSL müssen auf dem Server manuell konfiguriert werden.</p>
                  </ion-label>
                </ion-item>
              </template>

              <ion-item>
                <ion-label>Aktiviert</ion-label>
                <ion-toggle 
                  v-model="customLogin.is_enabled" 
                  :disabled="loadingCustomLogin || !customLogin.domain"
                ></ion-toggle>
              </ion-item>

              <ion-item>
                <ion-label position="stacked">Firmenname</ion-label>
                <ion-input 
                  v-model="customLogin.company_name" 
                  placeholder="z.B. Meine Firma GmbH"
                ></ion-input>
              </ion-item>

              <ion-item>
                <ion-label position="stacked">Primärfarbe</ion-label>
                <div style="display: flex; align-items: center; gap: 10px; padding: 8px 0;">
                  <input 
                    type="color" 
                    v-model="customLogin.primary_color" 
                    style="width: 50px; height: 35px; border: none; cursor: pointer;"
                  />
                  <ion-input 
                    v-model="customLogin.primary_color" 
                    placeholder="#e53e3e"
                    style="max-width: 120px;"
                  ></ion-input>
                </div>
              </ion-item>

              <ion-item>
                <ion-label position="stacked">Logo URL</ion-label>
                <ion-input 
                  v-model="customLogin.logo_url" 
                  placeholder="https://example.com/logo.png"
                ></ion-input>
              </ion-item>

              <ion-item v-if="customLogin.logo_url">
                <ion-label>Logo Vorschau</ion-label>
                <img 
                  :src="customLogin.logo_url" 
                  style="max-height: 50px; max-width: 150px;" 
                  @error="customLogin.logo_url = ''"
                />
              </ion-item>

              <ion-item v-if="customLogin.ssl_status">
                <ion-label>SSL Status</ion-label>
                <ion-badge :color="sslStatusColor">
                  {{ sslStatusText }}
                </ion-badge>
              </ion-item>

              <ion-item v-if="customLoginError">
                <ion-text color="danger">{{ customLoginError }}</ion-text>
              </ion-item>

              <ion-item v-if="customLoginSuccess">
                <ion-text color="success">{{ customLoginSuccess }}</ion-text>
              </ion-item>

              <ion-item>
                <ion-button 
                  @click="saveCustomLogin" 
                  :disabled="savingCustomLogin || !customLogin.domain"
                  color="primary"
                >
                  <ion-spinner v-if="savingCustomLogin" name="crescent" style="margin-right: 8px;" />
                  Speichern
                </ion-button>
                <ion-button 
                  v-if="customLogin.id"
                  @click="deleteCustomLogin" 
                  :disabled="savingCustomLogin"
                  color="danger"
                  style="margin-left: 10px;"
                >
                  Löschen
                </ion-button>
              </ion-item>

              <ion-item-divider>
                <ion-label>Allgemein</ion-label>
              </ion-item-divider>
              <!--<ion-item>
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
</ion-item>-->
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
                    <ion-input v-model="domainInput" placeholder="subdomain"
                      style="max-width:180px;display:inline-block;" /><!--:disabled="!connectedVercelProject"-->
                    <span>.sites.control-center.eu</span>
                    <ion-button size="small" @click="connectDomain"
                      :disabled="!domainInput || domainInput.length < 3">Connect</ion-button><!--|| !connectedVercelProject-->
                    <!-- <ion-text color="medium" v-if="!connectedVercelProject" style="display:block;margin-top:4px;">
                      Bitte zuerst ein Vercel-Projekt verbinden.
                    </ion-text>-->
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
  computed: {
    sslStatusColor() {
      const status = this.customLogin.ssl_status;
      if (status === 'active') return 'success';
      if (status === 'pending') return 'warning';
      if (status === 'manual') return 'medium';
      return 'danger';
    },
    sslStatusText() {
      const status = this.customLogin.ssl_status;
      if (status === 'manual') return 'Manuell (externe Domain)';
      return status;
    }
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
      // Custom Login
      loadingCustomLogin: true,
      savingCustomLogin: false,
      customLoginError: '',
      customLoginSuccess: '',
      customLogin: {
        id: null,
        domain: '',
        domain_type: 'internal',
        subdomain: '',
        is_enabled: false,
        primary_color: '#e53e3e',
        logo_url: '',
        company_name: '',
        ssl_status: ''
      }
    };
  },
  methods: {
    updateInternalDomain() {
      if (this.customLogin.domain_type === 'internal' && this.customLogin.subdomain) {
        this.customLogin.domain = this.customLogin.subdomain + '.control-center.eu';
      }
    },
    async fetchCustomLogin() {
      this.loadingCustomLogin = true;
      this.customLoginError = '';
      try {
        const res = await this.$axios.post('custom_login_domains.php', this.$qs.stringify({
          action: 'get',
          project: this.$route.params.project,
        }));
        if (res.data.success && res.data.data) {
          const data = res.data.data;
          const isInternal = data.is_internal || data.domain?.endsWith('.control-center.eu');
          
          this.customLogin = {
            id: data.id,
            domain: data.domain,
            domain_type: isInternal ? 'internal' : 'external',
            subdomain: isInternal ? data.domain.replace('.control-center.eu', '') : '',
            is_enabled: data.is_enabled,
            primary_color: data.primary_color || '#e53e3e',
            logo_url: data.logo_url || '',
            company_name: data.company_name || '',
            ssl_status: data.ssl_status || ''
          };
        }
      } catch (e) {
        console.error('Error fetching custom login:', e);
      } finally {
        this.loadingCustomLogin = false;
      }
    },
    async saveCustomLogin() {
      this.savingCustomLogin = true;
      this.customLoginError = '';
      this.customLoginSuccess = '';
      
      // Domain zusammensetzen für interne Domains
      if (this.customLogin.domain_type === 'internal') {
        if (!this.customLogin.subdomain) {
          this.customLoginError = 'Subdomain ist erforderlich';
          this.savingCustomLogin = false;
          return;
        }
        // Subdomain validieren
        if (!/^[a-z0-9-]+$/.test(this.customLogin.subdomain)) {
          this.customLoginError = 'Subdomain darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten';
          this.savingCustomLogin = false;
          return;
        }
        this.customLogin.domain = this.customLogin.subdomain + '.control-center.eu';
      }
      
      if (!this.customLogin.domain) {
        this.customLoginError = 'Domain ist erforderlich';
        this.savingCustomLogin = false;
        return;
      }
      
      try {
        const res = await this.$axios.post('custom_login_domains.php', this.$qs.stringify({
          action: 'save',
          project: this.$route.params.project,
          domain: this.customLogin.domain,
          is_enabled: this.customLogin.is_enabled ? 'true' : 'false',
          primary_color: this.customLogin.primary_color,
          logo_url: this.customLogin.logo_url,
          company_name: this.customLogin.company_name,
        }));
        
        if (res.data.success) {
          let message = res.data.message || 'Erfolgreich gespeichert';
          if (res.data.is_internal === false) {
            message += ' - Bitte DNS manuell konfigurieren (siehe Anleitung)';
          }
          this.customLoginSuccess = message;
          this.fetchCustomLogin();
          setTimeout(() => { this.customLoginSuccess = ''; }, 5000);
        } else {
          this.customLoginError = res.data.error || 'Fehler beim Speichern';
        }
      } catch (e) {
        this.customLoginError = 'Fehler beim Speichern';
      } finally {
        this.savingCustomLogin = false;
      }
    },
    async deleteCustomLogin() {
      if (!confirm('Custom Login Domain wirklich löschen?')) return;
      
      this.savingCustomLogin = true;
      this.customLoginError = '';
      
      try {
        const res = await this.$axios.post('custom_login_domains.php', this.$qs.stringify({
          action: 'delete',
          project: this.$route.params.project,
        }));
        
        if (res.data.success) {
          this.customLogin = {
            id: null,
            domain: '',
            domain_type: 'internal',
            subdomain: '',
            is_enabled: false,
            primary_color: '#e53e3e',
            logo_url: '',
            company_name: '',
            ssl_status: ''
          };
          this.customLoginSuccess = 'Custom Login Domain gelöscht';
          setTimeout(() => { this.customLoginSuccess = ''; }, 3000);
        } else {
          this.customLoginError = res.data.error || 'Fehler beim Löschen';
        }
      } catch (e) {
        this.customLoginError = 'Fehler beim Löschen';
      } finally {
        this.savingCustomLogin = false;
      }
    },
    /* onOpenVercelModal() {
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
     },*/
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
    /*async connectRepo(repo) {
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
    },*/
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
    //this.fetchConnectedRepo();
    this.fetchConnectedDomain();
    this.fetchCustomLogin();
    //this.fetchConnectedVercelProject();
  },
};
</script>
