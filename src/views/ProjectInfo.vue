<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="information-circle-outline" title="Project Information" />

      <div class="page-container">
        <PageHeader icon="information-circle-outline" title="Project Information" />

        <LoadingSpinner v-if="loading" />

        <div v-if="!loading">
          <DataCard title="General Information">
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
          </DataCard>

          <DataCard title="Project Sidebar" subtitle="Personalize your project's sidebar">
            <ActionButton variant="primary" @click="openSidebarEditor">Open Sidebar Editor</ActionButton>
          </DataCard>

          <DataCard title="Project Banner" subtitle="Upload a custom banner for your project header">
            <div v-if="projectBanner" class="banner-preview">
              <label class="form-label">Current Banner</label>
              <div class="banner-preview-container">
                <img :src="projectBanner" alt="Project Banner" />
                <ActionButton variant="danger" icon="trash-outline" @click="deleteBanner" :disabled="uploadingBanner">
                  Remove Banner</ActionButton>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Upload New Banner</label>
              <p class="form-hint">Recommended size: 1920x400px (JPG, PNG, max 5MB)</p>
              <input type="file" ref="bannerInput" @change="handleBannerUpload" accept="image/*" class="file-input"
                :disabled="uploadingBanner" />
            </div>

            <div v-if="bannerError" class="alert alert-error">
              <ion-icon name="alert-circle-outline"></ion-icon>
              {{ bannerError }}
            </div>

            <div v-if="bannerSuccess" class="alert alert-success">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
              {{ bannerSuccess }}
            </div>
          </DataCard>

          <DataCard title="Project Domain" subtitle="Configure your project's domain">
            <LoadingState v-if="loadingDomain" message="Loading domain information..." />
            <div v-else>
              <div v-if="connectedDomain" class="connected-info">
                <div class="connected-badge">
                  <ion-icon name="checkmark-circle-outline"></ion-icon>
                  <span>{{ connectedDomain }}</span>
                  <button class="action-btn icon-only danger" @click="deleteDomain" title="Remove Domain">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              </div>
              <div v-else>
                <div v-if="isSuperAdmin && availableDomains.length > 0" class="form-group">
                  <label class="form-label">Select Domain</label>
                  <select v-model="selectedDomainType" class="modern-select" @change="domainInput = ''">
                    <option value="subdomain">Subdomain (sites.control-center.eu)</option>
                    <option value="custom">Custom Domain from Domain Management</option>
                  </select>
                </div>

                <div v-if="!isSuperAdmin || selectedDomainType === 'subdomain'" class="form-group">
                  <label class="form-label">Subdomain</label>
                  <div class="domain-input-wrapper">
                    <input v-model="domainInput" placeholder="myproject" class="modern-input subdomain-input" />
                    <span class="domain-suffix">.sites.control-center.eu</span>
                  </div>
                </div>

                <div v-if="isSuperAdmin && selectedDomainType === 'custom'" class="form-group">
                  <label class="form-label">Select Custom Domain</label>
                  <select v-model="selectedCustomDomain" class="modern-select" @change="domainInput = ''">
                    <option value="">-- Select a domain --</option>
                    <option v-for="domain in availableDomains" :key="domain.id" :value="domain.domain">
                      {{ domain.domain }}
                    </option>
                  </select>
                </div>

                <div v-if="isSuperAdmin && selectedDomainType === 'custom' && selectedCustomDomain" class="form-group">
                  <label class="form-label">Subdomain (optional)</label>
                  <div class="domain-input-wrapper">
                    <input v-model="domainInput" placeholder="blog" class="modern-input subdomain-input" />
                    <span class="domain-suffix">.{{ selectedCustomDomain }}</span>
                  </div>
                  <small class="form-help">Leave empty to use the root domain</small>
                </div>

                <ActionButton variant="primary" @click="connectDomain"
                  :disabled="(selectedDomainType === 'custom' && !selectedCustomDomain) || (selectedDomainType === 'subdomain' && (!domainInput || domainInput.length < 3))"
                  style="margin-top: 12px;">Connect Domain</ActionButton>
              </div>
              <div v-if="domainError" class="alert alert-error">
                <ion-icon name="alert-circle-outline"></ion-icon>
                {{ domainError }}
              </div>
            </div>
          </DataCard>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>


<script>

import LoadingSpinner from "@/components/LoadingSpinner.vue";
import SiteTitle from "@/components/SiteTitle.vue";
import PageHeader from "@/components/PageHeader.vue";
import DataCard from "@/components/DataCard.vue";
import ActionButton from "@/components/ActionButton.vue";
import LoadingState from "@/components/LoadingState.vue";
import { getUserData } from "@/userData";

export default {
  name: "ProjectInfo",
  components: {
    LoadingSpinner,
    SiteTitle,
    PageHeader,
    DataCard,
    ActionButton,
    LoadingState
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
      selectedDomainType: 'subdomain',
      availableDomains: [],
      isSuperAdmin: false,
      selectedCustomDomain: '',
      loadingVercelProject: true,
      connectedVercelProject: null,
      openVercelModal: false,
      vercelProjects: [],
      vercelError: '',
      projectBanner: null,
      uploadingBanner: false,
      bannerError: '',
      bannerSuccess: ''
    };
  },
  methods: {
    openSidebarEditor() {
      this.$router.push({ name: 'sidebar-editor', params: { project: this.$route.params.project } });
    },
    async fetchConnectedDomain() {
      this.loadingDomain = true;
      this.domainError = '';
      try {
        const user = getUserData();

        this.isSuperAdmin = user && user.userID == 152;

        if (this.isSuperAdmin) {
          try {
            const domainsRes = await this.$axios.get('v2/domains/available');
            if (domainsRes.data.success) {
              this.availableDomains = domainsRes.data.domains;
            }
          } catch (e) {
            console.error('Error fetching available domains:', e);
          }
        }

        const res = await this.$axios.post('v2/project-domain/get', {
          project: this.$route.params.project,
        });
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

      let finalDomain = this.domainInput;
      let customBaseDomain = '';

      if (this.isSuperAdmin && this.selectedDomainType === 'custom') {
        if (!this.selectedCustomDomain) {
          this.domainError = 'Bitte wähle eine Custom Domain.';
          return;
        }
        customBaseDomain = this.selectedCustomDomain;

        if (this.domainInput) {
          if (!/^[a-z0-9-]+$/.test(this.domainInput)) {
            this.domainError = 'Subdomain: Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt.';
            return;
          }
          finalDomain = this.domainInput;
        } else {
          finalDomain = '';
        }
      } else {
        if (!/^[a-z0-9-]+$/.test(this.domainInput)) {
          this.domainError = 'Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt.';
          return;
        }
      }

      try {
        const res = await this.$axios.post('v2/project-domain/connect', {
          project: this.$route.params.project,
          user_id: user.userID,
          domain: finalDomain,
          domain_type: this.selectedDomainType,
          custom_base_domain: customBaseDomain
        });
        if (res.data && res.data.success) {
          this.connectedDomain = res.data.domain;
          this.domainInput = '';
          this.selectedCustomDomain = '';
        } else {
          this.domainError = res.data && res.data.error ? res.data.error : 'Fehler beim Verbinden.';
        }
      } catch (e) {
        this.domainError = 'Fehler beim Verbinden.';
      }
    },
    async deleteDomain() {
      if (!confirm('Are you sure you want to remove the domain from this project?')) return;

      this.domainError = '';
      try {
        const res = await this.$axios.post('v2/project-domain/delete', {
          project: this.$route.params.project
        });

        if (res.data && res.data.success) {
          this.connectedDomain = null;
        } else {
          this.domainError = res.data.error || 'Failed to remove domain';
        }
      } catch (e) {
        this.domainError = 'Error removing domain';
        console.error(e);
      }
    },
    async loadProjectBanner() {
      try {
        const projectResponse = await this.$axios.get(
          `v2/projects/${this.$route.params.project}/info`
        );

        if (!projectResponse.data || !projectResponse.data.projectID) {
          return;
        }

        const projectID = projectResponse.data.projectID;

        const fileQuery = await this.$axios.post(
          "v2/filesystem/get-file",
          {
            project: this.$route.params.project,
            name: "banner.jpg",
            directory: ".dev"
          }
        );

        if (!fileQuery.data.success || !fileQuery.data.file || !fileQuery.data.file.location) {
          return;
        }

        const fileLocation = fileQuery.data.file.location;

        const signedResponse = await this.$axios.post(
          "signed_url_generator.php",
          JSON.stringify({
            path: fileLocation,
            projectID: projectID,
            validitySeconds: 3600
          }),
          {
            headers: {
              'Content-Type': 'application/json'
            }
          }
        );

        if (signedResponse.data && signedResponse.data.url) {
          this.projectBanner = signedResponse.data.url + '&t=' + Date.now();
        }
      } catch (error) {
        console.error("Failed to load project banner:", error);
      }
    },
    async handleBannerUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      if (!file.type.startsWith('image/')) {
        this.bannerError = 'Please select an image file';
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        this.bannerError = 'File size must be less than 5MB';
        return;
      }

      this.uploadingBanner = true;
      this.bannerError = '';
      this.bannerSuccess = '';

      try {
        const reader = new FileReader();

        reader.onload = async (e) => {
          try {
            const base64Content = e.target.result.split(',')[1];

            const response = await this.$axios.post(
              'filesystem_upload.php',
              JSON.stringify({
                action: 'upload_file',
                project: this.$route.params.project,
                name: 'banner.jpg',
                directory: '.dev',
                content: base64Content,
                isBase64: true
              }),
              {
                headers: {
                  'Content-Type': 'application/json'
                }
              }
            );

            if (response.data.success) {
              this.bannerSuccess = 'Banner uploaded successfully!';
              await this.loadProjectBanner();
              this.$refs.bannerInput.value = '';

              setTimeout(() => {
                this.bannerSuccess = '';
              }, 3000);
            } else {
              this.bannerError = response.data.message || 'Failed to upload banner';
            }
          } catch (error) {
            console.error("Failed to upload banner:", error);
            this.bannerError = error.response?.data?.message || 'Failed to upload banner. Please try again.';
          } finally {
            this.uploadingBanner = false;
          }
        };

        reader.onerror = () => {
          this.bannerError = 'Failed to read file';
          this.uploadingBanner = false;
        };

        reader.readAsDataURL(file);
      } catch (error) {
        console.error("Failed to process file:", error);
        this.bannerError = 'Failed to process file. Please try again.';
        this.uploadingBanner = false;
      }
    },
    async deleteBanner() {
      if (!confirm('Are you sure you want to delete the project banner?')) return;

      this.uploadingBanner = true;
      this.bannerError = '';
      this.bannerSuccess = '';

      try {
        const response = await this.$axios.post(
          "v2/filesystem/delete",
          {
            project: this.$route.params.project,
            name: "banner.jpg",
            directory: ".dev"
          }
        );

        if (response.data.success) {
          this.projectBanner = null;
          this.bannerSuccess = 'Banner deleted successfully!';

          setTimeout(() => {
            this.bannerSuccess = '';
          }, 3000);
        } else {
          this.bannerError = response.data.message || 'Failed to delete banner';
        }
      } catch (error) {
        console.error("Failed to delete banner:", error);
        this.bannerError = error.response?.data?.message || 'Failed to delete banner. Please try again.';
      } finally {
        this.uploadingBanner = false;
      }
    },
  },
  async created() {
    this.$axios
      .get(
        `v2/projects/${this.$route.params.project}/info`
      )
      .then((res) => {
        this.projectName = res.data.name;
        this.creationDate = new Date(res.data.createdOn)
          .toLocaleDateString("en-GB")
          .replaceAll("/", ".");
        this.loading = false;
      });

    this.fetchConnectedDomain();
    this.loadProjectBanner();
  },
};
</script>

<style scoped>
.page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

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

.banner-preview {
  margin-bottom: 20px;
}

.banner-preview-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  background: var(--background);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.banner-preview-container img {
  width: 100%;
  max-height: 200px;
  object-fit: cover;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}

.file-input {
  width: 100%;
  padding: 12px;
  border: 2px dashed var(--border);
  border-radius: var(--radius);
  background: var(--background);
  cursor: pointer;
  transition: all 0.2s ease;
}

.file-input:hover:not(:disabled) {
  border-color: var(--primary-color);
  background: var(--surface);
}

.file-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

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
  width: 100%;
  justify-content: space-between;
}

.connected-badge ion-icon {
  font-size: 24px;
}

.action-btn.icon-only {
  padding: 8px;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-left: auto;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn.icon-only.danger {
  color: #dc2626;
  background: transparent;
  border: 1px solid transparent;
}

.action-btn.icon-only.danger:hover {
  background: #fee2e2;
  border-color: #fca5a5;
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .info-grid {
    grid-template-columns: 1fr;
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
}
</style>
