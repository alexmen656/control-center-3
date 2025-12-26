<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="information-circle-outline" title="Project Information" />

      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Project Information</h1>
            <p>Manage project settings and configurations</p>
          </div>
        </div>

        <LoadingSpinner v-if="loading" />

        <div v-if="!loading">
          <!-- General Information -->
          <div class="data-card">
            <div class="card-header">
              <h3>General Information</h3>
            </div>
            <div class="card-body">
              <div class="info-grid">
                <div class="info-item">
                  <label class="info-label">Project Name</label>
                  <div class="info-value">{{ projectName }}</div>
                </div>
                <div class="info-item">
                  <label class="info-label">Created On</label>
                  <div class="info-value">{{ creationDate }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Custom Login Domain -->
          <div class="data-card">
            <div class="card-header">
              <div class="header-left">
                <h3>Custom Login Domain</h3>
                <p class="card-subtitle">Configure a branded login page for your project</p>
              </div>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Domain Type</label>
                <select 
                  v-model="customLogin.domain_type" 
                  class="modern-select"
                  :disabled="customLogin.id"
                >
                  <option value="internal">Subdomain of control-center.eu (automatic)</option>
                  <option value="external">External Domain (manual setup)</option>
                </select>
              </div>

              <!-- Internal Domain Input -->
              <div v-if="customLogin.domain_type === 'internal'" class="form-group">
                <label class="form-label">Subdomain</label>
                <div class="domain-input-wrapper">
                  <input
                    v-model="customLogin.subdomain"
                    placeholder="mycompany"
                    class="modern-input subdomain-input"
                    :disabled="loadingCustomLogin || customLogin.id"
                    @input="updateInternalDomain"
                  />
                  <span class="domain-suffix">.control-center.eu</span>
                </div>
              </div>

              <!-- External Domain Input -->
              <div v-if="customLogin.domain_type === 'external'" class="form-group">
                <label class="form-label">External Domain</label>
                <input
                  v-model="customLogin.domain"
                  placeholder="login.mycompany.com"
                  class="modern-input"
                  :disabled="loadingCustomLogin || customLogin.id"
                />
              </div>

              <!-- Setup Instructions for External Domains -->
              <div v-if="customLogin.domain_type === 'external' && customLogin.id" class="setup-instructions">
                <div class="instruction-header">
                  <ion-icon name="warning-outline"></ion-icon>
                  <h4>Manual Setup Required</h4>
                </div>
                <div class="instruction-content">
                  <div class="instruction-step">
                    <h5>1. DNS Configuration</h5>
                    <p>Create an A-Record with your DNS provider:</p>
                    <div class="code-block">
                      <div>Type: A</div>
                      <div>Name: {{ customLogin.domain }}</div>
                      <div>Value: 92.5.112.145</div>
                    </div>
                  </div>
                  <div class="instruction-step">
                    <h5>2. Nginx & SSL</h5>
                    <p>Nginx and SSL must be manually configured on the server.</p>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <div class="toggle-wrapper">
                  <label class="form-label">Enabled</label>
                  <label class="toggle-switch">
                    <input
                      type="checkbox"
                      v-model="customLogin.is_enabled"
                      :disabled="loadingCustomLogin || !customLogin.domain"
                    />
                    <span class="toggle-slider"></span>
                  </label>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Company Name</label>
                <input
                  v-model="customLogin.company_name"
                  placeholder="e.g., My Company GmbH"
                  class="modern-input"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Primary Color</label>
                <div class="color-picker-wrapper">
                  <input
                    type="color"
                    v-model="customLogin.primary_color"
                    class="color-input"
                  />
                  <input
                    type="text"
                    v-model="customLogin.primary_color"
                    class="modern-input"
                    placeholder="#e53e3e"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Logo URL</label>
                <input
                  v-model="customLogin.logo_url"
                  placeholder="https://example.com/logo.png"
                  class="modern-input"
                />
              </div>

              <div v-if="customLogin.logo_url" class="logo-preview">
                <label class="form-label">Logo Preview</label>
                <img
                  :src="customLogin.logo_url"
                  alt="Logo Preview"
                  @error="customLogin.logo_url = ''"
                />
              </div>

              <div v-if="customLogin.ssl_status" class="form-group">
                <label class="form-label">SSL Status</label>
                <span :class="['status-badge', sslStatusColor]">
                  {{ sslStatusText }}
                </span>
              </div>

              <div v-if="customLoginError" class="alert alert-error">
                <ion-icon name="alert-circle-outline"></ion-icon>
                {{ customLoginError }}
              </div>

              <div v-if="customLoginSuccess" class="alert alert-success">
                <ion-icon name="checkmark-circle-outline"></ion-icon>
                {{ customLoginSuccess }}
              </div>

              <div class="form-actions">
                <button
                  class="action-btn secondary"
                  @click="deleteCustomLogin"
                  :disabled="!customLogin.id || savingCustomLogin"
                  v-if="customLogin.id"
                >
                  <ion-icon name="trash-outline"></ion-icon>
                  Delete
                </button>
                <button
                  class="action-btn primary"
                  @click="saveCustomLogin"
                  :disabled="savingCustomLogin"
                >
                  <ion-icon name="save-outline"></ion-icon>
                  {{ customLogin.id ? 'Update' : 'Save' }} Configuration
                </button>
              </div>
            </div>
          </div>

          <!-- Project Domain -->
          <div class="data-card">
            <div class="card-header">
              <div class="header-left">
                <h3>Project Domain</h3>
                <p class="card-subtitle">Configure your project's subdomain</p>
              </div>
            </div>
            <div class="card-body">
              <div v-if="loadingDomain" class="loading-state">
                <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
                <p>Loading domain information...</p>
              </div>
              <div v-else>
                <div v-if="connectedDomain" class="connected-info">
                  <div class="connected-badge">
                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                    <span>{{ connectedDomain }}</span>
                  </div>
                </div>
                <div v-else class="form-group">
                  <label class="form-label">Subdomain</label>
                  <div class="domain-input-wrapper">
                    <input
                      v-model="domainInput"
                      placeholder="myproject"
                      class="modern-input subdomain-input"
                    />
                    <span class="domain-suffix">.sites.control-center.eu</span>
                  </div>
                  <button
                    class="action-btn primary"
                    @click="connectDomain"
                    :disabled="!domainInput || domainInput.length < 3"
                    style="margin-top: 12px;"
                  >
                    Connect Domain
                  </button>
                </div>
                <div v-if="domainError" class="alert alert-error">
                  <ion-icon name="alert-circle-outline"></ion-icon>
                  {{ domainError }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>


<script>

import LoadingSpinner from "@/components/LoadingSpinner.vue";
import SiteTitle from "@/components/SiteTitle.vue";
import { getUserData } from "@/userData";

export default {
  name: "ProjectInfo",
  components: {
    LoadingSpinner,
    SiteTitle
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

<style scoped>
.modern-content {
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

/* Page Header */
.page-header {
  margin-bottom: 32px;
}

.header-content h1 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
}

/* Data Card */
.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  margin-bottom: 24px;
}

.card-header {
  padding: 24px;
  border-bottom: 1px solid var(--border);
}

.card-header h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.card-subtitle {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.card-body {
  padding: 24px;
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
}

.info-item {
  padding: 16px;
  background: var(--background);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.info-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-secondary);
  font-weight: 500;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

/* Form Elements */
.form-group {
  margin-bottom: 24px;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.modern-input,
.modern-select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
  font-family: inherit;
}

.modern-input:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-input:disabled,
.modern-select:disabled {
  background: var(--background);
  cursor: not-allowed;
  opacity: 0.6;
}

/* Domain Input */
.domain-input-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.subdomain-input {
  max-width: 250px;
}

.domain-suffix {
  color: var(--text-secondary);
  font-size: 14px;
  white-space: nowrap;
}

/* Toggle Switch */
.toggle-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 26px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: var(--border);
  transition: 0.3s;
  border-radius: 26px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
  background-color: var(--primary-color);
}

.toggle-switch input:checked + .toggle-slider:before {
  transform: translateX(22px);
}

.toggle-switch input:disabled + .toggle-slider {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Color Picker */
.color-picker-wrapper {
  display: flex;
  gap: 12px;
  align-items: center;
}

.color-input {
  width: 60px;
  height: 44px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  padding: 4px;
}

.color-picker-wrapper .modern-input {
  max-width: 150px;
}

/* Logo Preview */
.logo-preview {
  padding: 16px;
  background: var(--background);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  text-align: center;
}

.logo-preview img {
  max-height: 80px;
  max-width: 200px;
  object-fit: contain;
  margin-top: 12px;
}

/* Setup Instructions */
.setup-instructions {
  background: #fffbeb;
  border: 1px solid #fbbf24;
  border-radius: var(--radius);
  padding: 20px;
  margin: 20px 0;
}

.instruction-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.instruction-header ion-icon {
  font-size: 24px;
  color: #f59e0b;
}

.instruction-header h4 {
  margin: 0;
  color: #92400e;
  font-size: 16px;
  font-weight: 600;
}

.instruction-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.instruction-step h5 {
  margin: 0 0 8px 0;
  color: #78350f;
  font-size: 14px;
  font-weight: 600;
}

.instruction-step p {
  margin: 0 0 12px 0;
  color: #92400e;
  font-size: 14px;
  line-height: 1.5;
}

.code-block {
  background: #fef3c7;
  border: 1px solid #fbbf24;
  border-radius: var(--radius);
  padding: 12px;
  font-family: monospace;
  font-size: 13px;
  color: #78350f;
  line-height: 1.6;
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.success {
  background: #d1fae5;
  color: #059669;
}

.status-badge.warning {
  background: #fed7aa;
  color: #c2410c;
}

.status-badge.medium {
  background: #e0e7ff;
  color: #4f46e5;
}

.status-badge.danger {
  background: #fee2e2;
  color: #dc2626;
}

/* Alerts */
.alert {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: var(--radius);
  font-size: 14px;
  margin-bottom: 16px;
}

.alert ion-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.alert-error {
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.alert-success {
  background: #d1fae5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

/* Action Buttons */
.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn.secondary:hover:not(:disabled) {
  background: var(--background);
  border-color: var(--text-primary);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border: 1px solid var(--primary-color);
  box-shadow: var(--shadow);
}

.action-btn.primary:hover:not(:disabled) {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-btn ion-icon {
  font-size: 16px;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 32px;
  color: var(--primary-color);
  margin-bottom: 12px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Connected Info */
.connected-info {
  padding: 16px;
  background: #d1fae5;
  border: 1px solid #a7f3d0;
  border-radius: var(--radius);
}

.connected-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #059669;
  font-weight: 600;
  font-size: 16px;
}

.connected-badge ion-icon {
  font-size: 24px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }

  .action-btn {
    width: 100%;
  }

  .domain-input-wrapper {
    flex-direction: column;
    align-items: stretch;
  }

  .subdomain-input {
    max-width: 100%;
  }

  .color-picker-wrapper {
    flex-direction: column;
    align-items: stretch;
  }

  .color-picker-wrapper .modern-input {
    max-width: 100%;
  }
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
}
</style>
