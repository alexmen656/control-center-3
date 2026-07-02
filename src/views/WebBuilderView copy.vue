<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="globe-outline" title="Web Builder" />

      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Web Builder</h1>
            <p>Erstellen und gestalten Sie Ihre Website mit unserem visuellen Web Builder</p>
          </div>
          <div class="header-actions">
            <button class="action-btn primary" @click="openWebBuilder()">
              <ion-icon name="open-outline"></ion-icon>
              <span>Web Builder öffnen</span>
            </button>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="document-text-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ pages.length }}</h3>
              <p>Seiten</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
              <ion-icon name="globe-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ webBuilderDomain.id ? 'Aktiv' : 'Inaktiv' }}</h3>
              <p>Domain Status</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
              <ion-icon name="shield-checkmark-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ webBuilderDomain.ssl_status || 'N/A' }}</h3>
              <p>SSL Status</p>
            </div>
          </div>
        </div>
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Projekt-Seiten</h3>
              <p class="entry-count">{{ pages.length }} Seite(n)</p>
            </div>
            <div class="header-actions">
              <button class="action-btn primary" @click="createNewPage">
                <ion-icon name="add-outline"></ion-icon>
                Neue Seite
              </button>
            </div>
          </div>

          <div class="table-wrapper">
            <div v-if="isLoading" class="loading-state">
              <ion-icon name="hourglass-outline" class="loading-icon"></ion-icon>
              <p>Laden...</p>
            </div>

            <div v-else-if="pages.length === 0" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="document-outline" class="no-data-icon"></ion-icon>
                <h4>Keine Seiten vorhanden</h4>
                <p>Erstellen Sie Ihre erste Seite, um mit dem Web Builder zu starten</p>
                <button class="action-btn primary" @click="createNewPage">
                  <ion-icon name="add-outline"></ion-icon>
                  Erste Seite erstellen
                </button>
              </div>
            </div>

            <div v-else class="modern-table">
              <div class="table-header">
                <div class="header-cell">
                  <span class="header-text">Name</span>
                </div>
                <div class="header-cell">
                  <span class="header-text">URL/Slug</span>
                </div>
                <div class="header-cell">
                  <span class="header-text">Status</span>
                </div>
                <div class="header-cell actions-header">
                  <span class="header-text">Aktionen</span>
                </div>
              </div>

              <div class="table-body">
                <div v-for="page in pages" :key="page.id" class="table-row">
                  <div class="table-cell">
                    <div class="page-info">
                      <ion-icon :name="Number(page.is_home) === 1 ? 'home' : 'document-text-outline'"
                        :style="{ color: Number(page.is_home) === 1 ? 'var(--primary-color)' : 'var(--text-secondary)' }"></ion-icon>
                      <span class="page-name">{{ page.name }}</span>
                    </div>
                  </div>
                  <div class="table-cell">
                    <span class="cell-content">{{ page.slug }}</span>
                  </div>
                  <div class="table-cell">
                    <span v-if="Number(page.is_home) === 1" class="status-badge status-active">
                      Homepage
                    </span>
                    <span v-else class="status-badge status-pending">
                      Seite
                    </span>
                  </div>
                  <div class="table-cell actions-cell">
                    <div class="action-buttons">
                      <button class="icon-btn edit-btn" @click="openPageEditor(page)" title="Im Editor öffnen">
                        <ion-icon name="open-outline"></ion-icon>
                      </button>
                      <button class="icon-btn assign-btn" @click="editPage(page)" title="Bearbeiten">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Domain Settings</h3>
            </div>
          </div>
          <div class="card-body">
            <div v-if="loadingWebBuilderDomain" class="loading-state">
              <ion-icon name="hourglass-outline" class="loading-icon"></ion-icon>
              <p>Laden...</p>
            </div>
            <div v-else-if="!mainDomain" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="alert-circle-outline" class="no-data-icon"></ion-icon>
                <h4>Main Domain erforderlich</h4>
                <p>Bitte konfigurieren Sie zuerst eine Main Domain in den Project Info Einstellungen</p>
                <button class="action-btn primary" @click="$router.push(`/project/${projectName}/info`)">
                  <ion-icon name="settings-outline"></ion-icon>
                  Zu Project Info
                </button>
              </div>
            </div>

            <div v-else class="domain-config">
              <div class="info-box">
                <ion-icon name="information-circle-outline"></ion-icon>
                <span>Main Domain: <strong>{{ mainDomain }}</strong></span>
              </div>

              <div class="form-group">
                <label class="form-label">Domain Typ</label>
                <div class="domain-type-selector" v-if="isSuperAdmin">
                  <label class="radio-option">
                    <input type="radio" v-model="selectedDomainType" value="subdomain" :disabled="savingWebBuilderDomain" />
                    <span>Subdomain ({{ mainDomain }})</span>
                  </label>
                  <label class="radio-option">
                    <input type="radio" v-model="selectedDomainType" value="custom" :disabled="savingWebBuilderDomain" />
                    <span>Custom Domain</span>
                  </label>
                </div>
              </div>

              <div v-if="selectedDomainType === 'subdomain'" class="form-group">
                <label class="form-label">Subdomain</label>
                <div class="domain-input-wrapper">
                  <input v-model="webBuilderDomain.subdomain" placeholder="blog" class="modern-input subdomain-input"
                    :disabled="savingWebBuilderDomain" />
                  <span class="domain-suffix">.{{ mainDomain }}</span>
                </div>
                <small class="form-help">Beispiel: blog.{{ mainDomain }}</small>
              </div>

              <div v-else-if="selectedDomainType === 'custom' && isSuperAdmin" class="form-group">
                <label class="form-label">Custom Domain auswählen</label>
                <select v-model="selectedCustomDomain" class="modern-input" :disabled="savingWebBuilderDomain" @change="webBuilderDomain.subdomain = ''">
                  <option value="">-- Bitte wählen --</option>
                  <option v-for="domain in availableDomains" :key="domain.id" :value="domain.domain">
                    {{ domain.domain }}
                  </option>
                </select>
                <small class="form-help">Wählen Sie eine Domain aus dem Domain Management</small>
              </div>

              <div v-if="selectedDomainType === 'custom' && isSuperAdmin && selectedCustomDomain" class="form-group">
                <label class="form-label">Subdomain (optional)</label>
                <div class="domain-input-wrapper">
                  <input v-model="webBuilderDomain.subdomain" placeholder="blog" class="modern-input subdomain-input" :disabled="savingWebBuilderDomain" />
                  <span class="domain-suffix">.{{ selectedCustomDomain }}</span>
                </div>
                <small class="form-help">Leer lassen für Root Domain</small>
              </div>

              <div class="form-group">
                <div class="toggle-wrapper">
                  <label class="form-label">Aktiviert</label>
                  <label class="toggle-switch">
                    <input type="checkbox" v-model="webBuilderDomain.is_enabled"
                      :disabled="savingWebBuilderDomain || !webBuilderDomain.subdomain" />
                    <span class="toggle-slider"></span>
                  </label>
                </div>
              </div>
              <!--    <div v-if="webBuilderDomain.ssl_status" class="form-group">
                <label class="form-label">SSL Status</label>
                <span :class="['status-badge', sslStatusColor(webBuilderDomain.ssl_status)]">
                  {{ webBuilderDomain.ssl_status }}
                </span>
              </div>-->
              <div v-if="webBuilderDomain.id" class="info-message success">
                <ion-icon name="checkmark-circle-outline"></ion-icon>
                <div>
                  <strong>Domain aktiv:</strong> {{ webBuilderDomain.subdomain }}.{{ mainDomain }}
                  <br>
                  <small>DNS und SSL werden automatisch konfiguriert</small>
                </div>
              </div>

              <div v-if="webBuilderDomainError" class="alert alert-error">
                <ion-icon name="alert-circle-outline"></ion-icon>
                {{ webBuilderDomainError }}
              </div>

              <div v-if="webBuilderDomainSuccess" class="alert alert-success">
                <ion-icon name="checkmark-circle-outline"></ion-icon>
                {{ webBuilderDomainSuccess }}
              </div>

              <div class="form-actions">
                <button v-if="webBuilderDomain.id" class="action-btn secondary" @click="deleteWebBuilderDomain"
                  :disabled="savingWebBuilderDomain">
                  <ion-icon name="trash-outline"></ion-icon>
                  Löschen
                </button>
                <button class="action-btn primary" @click="saveWebBuilderDomain"
                  :disabled="savingWebBuilderDomain || !webBuilderDomain.subdomain">
                  <ion-icon name="save-outline"></ion-icon>
                  {{ webBuilderDomain.id ? 'Aktualisieren' : 'Speichern' }}
                </button>
              </div>
            </div>
          </div>
        </div> <!-- Projekt-Seiten -->

        <!-- Abschnitt für Komponenten-Vorlagen -->
        <!--<ion-card>
              <ion-card-header>
                <ion-card-title>
                  <div class="section-title">Komponenten-Vorlagen</div>
                </ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <div v-if="isLoading" class="loading-container">
                  <ion-spinner name="crescent"></ion-spinner>
                  <p>Laden...</p>
                </div>
                
                <div v-else-if="templates.length === 0" class="empty-state">
                  <ion-icon name="cube-outline" size="large"></ion-icon>
                  <p>Keine Vorlagen gefunden</p>
                  <ion-button size="small" @click="createNewTemplate">Erste Vorlage erstellen</ion-button>
                </div>
                
                <ion-list v-else lines="full">
                  <ion-item 
                    v-for="template in templates" 
                    :key="template.id" 
                    button
                  >
                    <ion-icon name="cube-outline" slot="start"></ion-icon>
                    <ion-label>
                      <h2>{{ template.title }}</h2>
                      <p>{{ template.description || 'Keine Beschreibung' }}</p>
                    </ion-label>
                    <ion-buttons slot="end">
                      <ion-button @click.stop="previewTemplate(template)">
                        <ion-icon name="eye-outline" slot="icon-only"></ion-icon>
                      </ion-button>
                    </ion-buttons>
                  </ion-item>
                </ion-list>
              </ion-card-content>
            </ion-card>-->

        <!-- Hilfe & Ressourcen -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Hilfe & Ressourcen</h3>
            </div>
          </div>

          <div class="resources-grid">
            <div class="resource-card" @click="openDocumentation()">
              <div class="resource-icon">
                <ion-icon name="book-outline"></ion-icon>
              </div>
              <div class="resource-content">
                <h4>Dokumentation</h4>
                <p>Umfassende Anleitungen zur Verwendung des Web Builders</p>
              </div>
              <ion-icon name="chevron-forward-outline" class="resource-arrow"></ion-icon>
            </div>

            <div class="resource-card" @click="openTutorials()">
              <div class="resource-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <ion-icon name="videocam-outline"></ion-icon>
              </div>
              <div class="resource-content">
                <h4>Video-Tutorials</h4>
                <p>Schritt-für-Schritt-Anleitungen in Videoform</p>
              </div>
              <ion-icon name="chevron-forward-outline" class="resource-arrow"></ion-icon>
            </div>

            <div class="resource-card" @click="openLiveWebsite()">
              <div class="resource-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <ion-icon name="earth-outline"></ion-icon>
              </div>
              <div class="resource-content">
                <h4>Website anzeigen</h4>
                <p>Öffnet die live-Version Ihrer Website</p>
              </div>
              <ion-icon name="chevron-forward-outline" class="resource-arrow"></ion-icon>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal für neue Seite -->
      <div v-if="isNewPageModalOpen" class="custom-modal-overlay" @click="closeNewPageModal">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Neue Seite erstellen</h3>
            <button class="modal-close-btn" @click="closeNewPageModal">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="form-group">
              <label class="form-label">Seitenname*</label>
              <input v-model="newPage.name" placeholder="z.B. Über Uns" class="modern-input" />
            </div>

            <div class="form-group">
              <label class="form-label">URL-Slug</label>
              <input v-model="newPage.slug" placeholder="z.B. ueber-uns" class="modern-input" />
              <small class="form-help">Wird automatisch aus dem Namen generiert, wenn leer</small>
            </div>

            <div class="form-group">
              <label class="form-label">Meta-Titel</label>
              <input v-model="newPage.title" placeholder="SEO-Titel der Seite" class="modern-input" />
            </div>

            <div class="form-group">
              <label class="form-label">Meta-Beschreibung</label>
              <textarea v-model="newPage.metaDescription" placeholder="Kurze Beschreibung für Suchmaschinen"
                class="modern-input" rows="3"></textarea>
            </div>

            <div class="form-group">
              <div class="toggle-wrapper">
                <label class="form-label">Als Homepage festlegen</label>
                <label class="toggle-switch">
                  <input type="checkbox" v-model="newPage.isHome" />
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>

            <div class="form-actions">
              <button class="action-btn secondary" @click="closeNewPageModal">
                Abbrechen
              </button>
              <button class="action-btn primary" @click="saveNewPage" :disabled="!newPage.name">
                Speichern
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Template-Vorschau Modal (auskommentiert, aber modernisiert) -->
      <!--
      <div v-if="isTemplatePreviewOpen" class="custom-modal-overlay" @click="closeTemplatePreview">
        <div class="custom-modal-content large" @click.stop>
          <div class="custom-modal-header">
            <h3>{{ selectedTemplate ? selectedTemplate.title : 'Vorlage Vorschau' }}</h3>
            <button class="modal-close-btn" @click="closeTemplatePreview">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div v-if="selectedTemplate" class="template-preview">
              <div class="html-preview" v-html="selectedTemplate.html_code"></div>
            </div>
          </div>
          <div class="custom-modal-footer">
            <button class="action-btn secondary" @click="closeTemplatePreview">
              Schließen
            </button>
            <button class="action-btn primary" @click="useSelectedTemplate">
              Verwenden
            </button>
          </div>
        </div>
      </div>
      -->

      <!-- Success Message -->
      <div v-if="successMessage" class="success-toast">
        <ion-icon name="checkmark-circle-outline"></ion-icon>
        {{ successMessage }}
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import qs from 'qs';
import SiteTitle from '@/components/SiteTitle.vue';

export default defineComponent({
  name: 'WebBuilderView',
  components: {
    SiteTitle
  },

  setup() {
    const route = useRoute();
    const projectName = ref(route.params.project);
    const pages = ref([]);
    const templates = ref([]);
    const isLoading = ref(false);
    const successMessage = ref('');

    // Web Builder Domain
    const loadingWebBuilderDomain = ref(true);
    const savingWebBuilderDomain = ref(false);
    const webBuilderDomainError = ref('');
    const webBuilderDomainSuccess = ref('');
    const mainDomain = ref('');
    const webBuilderDomain = ref({
      id: null,
      subdomain: '',
      domain: '',
      is_enabled: true,
      ssl_status: ''
    });
    
    // Super Admin Domain Management
    const selectedDomainType = ref('subdomain');
    const availableDomains = ref([]);
    const isSuperAdmin = ref(false);
    const selectedCustomDomain = ref('');

    const isNewPageModalOpen = ref(false);
    const newPage = ref({
      name: '',
      slug: '',
      title: '',
      metaDescription: '',
      isHome: false
    });

    const isTemplatePreviewOpen = ref(false);
    const selectedTemplate = ref(null);

    const fetchPages = async () => {
      isLoading.value = true;
      try {
        const response = await axios.get(
          `sidebar.php?getSideBarByProjectName=${projectName.value}`
        );

        if (response.data && response.data.components) {
          // Extrahiere alle Web Builder Pages aus den components
          const webBuilderPages = [];
          response.data.components.forEach(component => {
            const componentId = component.id;
            const subItems = response.data.componentSubItems[componentId] || [];

            subItems.forEach(subItem => {
              if (subItem.type === 'page') {
                webBuilderPages.push({
                  id: subItem.id,
                  name: subItem.name,
                  slug: subItem.slug,
                  is_home: subItem.icon === 'home' ? 1 : 0,
                  wb_project_id: componentId
                });
              }
            });
          });

          pages.value = webBuilderPages;
        } else {
          pages.value = [];
        }
      } catch (error) {
        console.error('Fehler beim Laden der Seiten:', error);
        pages.value = [];
      } finally {
        isLoading.value = false;
      }
    };

    const fetchWebBuilderDomain = async () => {
      loadingWebBuilderDomain.value = true;
      webBuilderDomainError.value = '';
      try {
        // Check if user is super admin
        const { getUserData } = await import('@/utils/userData.js');
        const userData = getUserData();
        isSuperAdmin.value = userData && userData.userID === 152;
        
        // If super admin, fetch available domains from domain management
        if (isSuperAdmin.value) {
          try {
            const domainsResponse = await axios.get('v2/domains/available');
            
            if (domainsResponse.data.success) {
              availableDomains.value = domainsResponse.data.data || [];
            }
          } catch (error) {
            console.error('Fehler beim Laden der verfügbaren Domains:', error);
          }
        }
        
        // Erst Main Domain laden
        const mainDomainResponse = await axios.post(
          'project_domain.php',
          qs.stringify({
            action: 'get',
            project: projectName.value
          })
        );

        if (mainDomainResponse.data.domain) {
          mainDomain.value = mainDomainResponse.data.domain;
        } else {
          webBuilderDomainError.value = 'Bitte zuerst eine Main Domain in Project Info konfigurieren';
          loadingWebBuilderDomain.value = false;
          return;
        }

        const response = await axios.post(
          'web_builder_domains.php',
          qs.stringify({
            action: 'get',
            project: projectName.value
          })
        );

        if (response.data.success && response.data.data) {
          const data = response.data.data;
          webBuilderDomain.value = {
            id: data.id,
            subdomain: data.subdomain,
            domain: data.domain,
            is_enabled: data.is_enabled === true || data.is_enabled === 'true' || data.is_enabled === 1 || data.is_enabled === '1',
            ssl_status: data.ssl_status || ''
          };
        }
      } catch (error) {
        console.error('Fehler beim Laden der Web Builder Domain:', error);
      } finally {
        loadingWebBuilderDomain.value = false;
      }
    };

    const saveWebBuilderDomain = async () => {
      savingWebBuilderDomain.value = true;
      webBuilderDomainError.value = '';
      webBuilderDomainSuccess.value = '';

      if (selectedDomainType.value === 'subdomain') {
        if (!mainDomain.value) {
          webBuilderDomainError.value = 'Bitte zuerst eine Main Domain in Project Info konfigurieren';
          savingWebBuilderDomain.value = false;
          return;
        }
        
        if (!webBuilderDomain.value.subdomain) {
          webBuilderDomainError.value = 'Subdomain ist erforderlich';
          savingWebBuilderDomain.value = false;
          return;
        }

        if (!/^[a-z0-9-]+$/.test(webBuilderDomain.value.subdomain)) {
          webBuilderDomainError.value = 'Subdomain darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten';
          savingWebBuilderDomain.value = false;
          return;
        }

        if (webBuilderDomain.value.subdomain.length < 3) {
          webBuilderDomainError.value = 'Subdomain muss mindestens 3 Zeichen lang sein';
          savingWebBuilderDomain.value = false;
          return;
        }
      } else if (selectedDomainType.value === 'custom') {
        if (!selectedCustomDomain.value) {
          webBuilderDomainError.value = 'Bitte wähle eine Custom Domain';
          savingWebBuilderDomain.value = false;
          return;
        }
        
        // Validate subdomain if provided
        if (webBuilderDomain.value.subdomain && !/^[a-z0-9-]+$/.test(webBuilderDomain.value.subdomain)) {
          webBuilderDomainError.value = 'Subdomain darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten';
          savingWebBuilderDomain.value = false;
          return;
        }
      }

      try {
        const domainToSave = webBuilderDomain.value.subdomain || '';
        const customBaseDomain = selectedDomainType.value === 'custom' ? selectedCustomDomain.value : '';
          
        const response = await axios.post(
          'web_builder_domains.php',
          qs.stringify({
            action: 'save',
            project: projectName.value,
            subdomain: domainToSave,
            main_domain: selectedDomainType.value === 'custom' ? '' : mainDomain.value,
            is_enabled: webBuilderDomain.value.is_enabled ? 'true' : 'false',
            domain_type: selectedDomainType.value,
            custom_base_domain: customBaseDomain
          })
        );

        if (response.data.success) {
          webBuilderDomainSuccess.value = response.data.message || 'Erfolgreich gespeichert';
          fetchWebBuilderDomain();
          setTimeout(() => { webBuilderDomainSuccess.value = ''; }, 5000);
        } else {
          webBuilderDomainError.value = response.data.error || 'Fehler beim Speichern';
        }
      } catch (error) {
        webBuilderDomainError.value = 'Fehler beim Speichern';
        console.error('Fehler beim Speichern der Web Builder Domain:', error);
      } finally {
        savingWebBuilderDomain.value = false;
      }
    };

    const deleteWebBuilderDomain = async () => {
      if (!confirm('Web Builder Subdomain wirklich löschen?')) return;

      savingWebBuilderDomain.value = true;
      webBuilderDomainError.value = '';

      try {
        const response = await axios.post(
          'web_builder_domains.php',
          qs.stringify({
            action: 'delete',
            project: projectName.value
          })
        );

        if (response.data.success) {
          webBuilderDomain.value = {
            id: null,
            subdomain: '',
            domain: '',
            is_enabled: true,
            ssl_status: ''
          };
          webBuilderDomainSuccess.value = 'Web Builder Subdomain gelöscht';
          setTimeout(() => { webBuilderDomainSuccess.value = ''; }, 3000);
        } else {
          webBuilderDomainError.value = response.data.error || 'Fehler beim Löschen';
        }
      } catch (error) {
        webBuilderDomainError.value = 'Fehler beim Löschen';
        console.error('Fehler beim Löschen der Web Builder Domain:', error);
      } finally {
        savingWebBuilderDomain.value = false;
      }
    };

    const sslStatusColor = (status) => {
      if (status === 'active') return 'success';
      if (status === 'pending') return 'warning';
      return 'danger';
    };

    const fetchTemplates = async () => {
      try {
        const response = await axios.post(
          'web_pages.php',
          qs.stringify({
            getTemplates: true
          })
        );

        if (response.data && response.data.success && response.data.templates) {
          templates.value = response.data.templates;
        }
      } catch (error) {
        console.error('Fehler beim Laden der Vorlagen:', error);
      }
    };

    const openWebBuilder = () => {
      window.open(`http://localhost:5174/project/${route.params.wb_project}`, '_blank');
    };

    const openPageEditor = (page) => {
      const url = `https://web-builder.control-center.eu/editor/${projectName.value}/${page.slug}`;
      window.open(url, '_blank');
    };

    const createNewPage = () => {
      newPage.value = {
        name: '',
        slug: '',
        title: '',
        metaDescription: '',
        isHome: false
      };
      isNewPageModalOpen.value = true;
    };

    const closeNewPageModal = () => {
      isNewPageModalOpen.value = false;
    };

    const saveNewPage = async () => {
      if (!newPage.value.name) return;

      try {
        const pageData = {
          newPage: true,
          project: projectName.value,
          name: newPage.value.name,
          slug: newPage.value.slug || generateSlug(newPage.value.name),
          title: newPage.value.title || newPage.value.name,
          meta_description: newPage.value.metaDescription,
          is_home: newPage.value.isHome ? 1 : 0
        };

        const response = await axios.post('web_pages.php', qs.stringify(pageData));

        if (response.data.success) {
          closeNewPageModal();
          fetchPages();
          showSuccessMessage('Seite erfolgreich erstellt!');
        } else {
          console.error('Fehler beim Erstellen der Seite:', response.data.message);
          webBuilderDomainError.value = 'Fehler: ' + (response.data.message || 'Unbekannter Fehler');
        }
      } catch (error) {
        console.error('Fehler beim Erstellen der Seite:', error);
        webBuilderDomainError.value = 'Netzwerkfehler beim Erstellen der Seite';
      }
    };

    const showSuccessMessage = (message) => {
      successMessage.value = message;
      setTimeout(() => {
        successMessage.value = '';
      }, 3000);
    };

    const editPage = (page) => {
      console.log('Seite bearbeiten:', page);
    };

    const createNewTemplate = () => {
      console.log('Neue Vorlage erstellen');
    };

    const previewTemplate = (template) => {
      selectedTemplate.value = template;
      isTemplatePreviewOpen.value = true;
    };

    const closeTemplatePreview = () => {
      isTemplatePreviewOpen.value = false;
    };

    const useSelectedTemplate = () => {
      console.log('Template verwenden:', selectedTemplate.value);
      closeTemplatePreview();
    };

    const generateSlug = (text) => {
      return text
        .toLowerCase()
        .replace(/[äöüß]/g, match => (
          { 'ä': 'ae', 'ö': 'oe', 'ü': 'ue', 'ß': 'ss' }[match]
        ))
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    };

    const openDocumentation = () => {
      window.open('https://docs.control-center.eu/web-builder', '_blank');
    };

    const openTutorials = () => {
      window.open('https://tutorials.control-center.eu/web-builder', '_blank');
    };

    const openLiveWebsite = () => {
      window.open(`https://alex.polan.sk/${projectName.value}`, '_blank');
    };

    onMounted(() => {
      fetchPages();
      fetchTemplates();
      fetchWebBuilderDomain();
    });

    return {
      pages,
      templates,
      isLoading,
      openWebBuilder,
      openPageEditor,
      createNewPage,
      editPage,
      isNewPageModalOpen,
      newPage,
      closeNewPageModal,
      saveNewPage,
      createNewTemplate,
      previewTemplate,
      isTemplatePreviewOpen,
      selectedTemplate,
      closeTemplatePreview,
      useSelectedTemplate,
      openDocumentation,
      openTutorials,
      openLiveWebsite,
      loadingWebBuilderDomain,
      savingWebBuilderDomain,
      webBuilderDomain,
      webBuilderDomainError,
      webBuilderDomainSuccess,
      mainDomain,
      saveWebBuilderDomain,
      deleteWebBuilderDomain,
      sslStatusColor,
      projectName,
      successMessage
    };
  }
});
</script>

<style scoped>
/* Modern Design System - Same as ManageUsers */
.modern-content {
  --primary-color: #f97316;
  --primary-hover: #ea580c;
  --secondary-color: #64748b;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 20px;
}

.header-content h1 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  line-height: 1.2;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.5;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* Action Buttons */
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover:not(:disabled) {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
}

.action-btn.secondary {
  background: var(--secondary-color);
  color: white;
  border-color: var(--secondary-color);
}

.action-btn.secondary:hover:not(:disabled) {
  background: #475569;
}

.action-btn ion-icon {
  font-size: 16px;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 20px;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  /*color: white;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);*/
  background: rgba(249, 115, 22, 0.1);
  color: var(--primary-color);
  flex-shrink: 0;
}

.stat-content h3 {
  margin: 0 0 4px 0;
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

/* Data Card */
.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 16px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.header-subtitle {
  color: var(--text-secondary);
  font-size: 14px;
  margin: 4px 0 0 0;
}

.entry-count {
  color: var(--text-secondary);
  font-size: 14px;
}

.card-body {
  padding: 24px;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
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

.loading-state p {
  margin: 0;
  font-size: 14px;
}

/* No Data State */
.no-data-state {
  padding: 60px 20px;
  text-align: center;
  background: var(--surface);
}

.no-data-content {
  max-width: 400px;
  margin: 0 auto;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
}

.no-data-content h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-content p {
  margin: 0 0 24px 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

/* Domain Configuration */
.domain-config {
  max-width: 800px;
}

.info-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: var(--primary-color);
  background: rgba(249, 115, 22, 0.1);
  border-radius: var(--radius);
  margin-bottom: 20px;
  color: var(--primary-color);
  font-size: 14px;
}

.info-box ion-icon {
  font-size: 20px;
  flex-shrink: 0;
}

/* Form Styles */
.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  font-weight: 500;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-size: 14px;
}

.form-help {
  display: block;
  margin-top: 6px;
  font-size: 13px;
  color: var(--text-secondary);
}

.modern-input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.modern-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

textarea.modern-input {
  resize: vertical;
  font-family: inherit;
}

.domain-input-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.subdomain-input {
  flex: 0 1 250px;
  min-width: 150px;
}

.domain-suffix {
  color: var(--text-secondary);
  font-size: 14px;
  white-space: nowrap;
}

/* Domain Type Selector */
.domain-type-selector {
  display: flex;
  gap: 16px;
  margin-bottom: 8px;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.radio-option:hover {
  border-color: var(--primary-color);
  background: rgba(249, 115, 22, 0.05);
}

.radio-option input[type="radio"] {
  cursor: pointer;
}

.radio-option span {
  font-size: 14px;
  color: var(--text-primary);
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

.toggle-switch input:checked+.toggle-slider {
  background-color: var(--primary-color);
}

.toggle-switch input:checked+.toggle-slider:before {
  transform: translateX(22px);
}

.toggle-switch input:disabled+.toggle-slider {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.success,
.status-badge.status-active {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.status-badge.warning,
.status-badge.status-pending {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
  border: 1px solid rgba(217, 119, 6, 0.2);
}

.status-badge.danger {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

/* Info/Alert Messages */
.info-message {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px;
  border-radius: var(--radius);
  margin-top: 16px;
  font-size: 14px;
  line-height: 1.5;
}

.info-message ion-icon {
  font-size: 24px;
  flex-shrink: 0;
  margin-top: 2px;
}

.info-message.success {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.info-message small {
  opacity: 0.8;
  font-size: 13px;
}

.alert {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  border-radius: var(--radius);
  margin-top: 12px;
  font-size: 14px;
}

.alert ion-icon {
  font-size: 20px;
}

.alert-error {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

.alert-success {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--border);
}

/* Modern Table */
.table-wrapper {
  overflow-x: auto;
}

.modern-table {
  width: 100%;
  min-width: 800px;
}

.table-header {
  display: flex;
  background: var(--background);
  border-bottom: 2px solid var(--border);
}

.header-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
}

.actions-header {
  flex: 0 0 140px;
  justify-content: center;
}

.header-text {
  font-weight: 600;
}

/* Table Body */
.table-body {
  background: var(--surface);
}

.table-row {
  display: flex;
  border-bottom: 1px solid var(--border);
  transition: all 0.2s ease;
}

.table-row:hover {
  background: var(--background);
}

.table-row:last-child {
  border-bottom: none;
}

.table-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  font-size: 14px;
  color: var(--text-primary);
}

.actions-cell {
  flex: 0 0 140px;
  justify-content: center;
  padding: 12px 16px;
}

.cell-content {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 200px;
}

/* Page Info */
.page-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-info ion-icon {
  font-size: 20px;
}

.page-name {
  font-weight: 500;
}

/* Action Buttons in Table */
.action-buttons {
  display: flex;
  gap: 8px;
}

.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
}

.edit-btn {
  background: #fff7ed;
  color: var(--primary-color);
}

.edit-btn:hover {
  background: #ffedd5;
  transform: scale(1.05);
}

.assign-btn {
  background: rgba(249, 115, 22, 0.1);
  color: var(--primary-color);
}

.assign-btn:hover {
  background: rgba(249, 115, 22, 0.2);
  transform: scale(1.05);
}

/* Resources Grid */
.resources-grid {
  padding: 24px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.resource-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
}

.resource-card:hover {
  background: var(--surface);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.resource-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
  flex-shrink: 0;
}

.resource-content {
  flex: 1;
}

.resource-content h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.resource-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.resource-arrow {
  color: var(--text-muted);
  font-size: 20px;
  transition: transform 0.2s ease;
}

.resource-card:hover .resource-arrow {
  transform: translateX(4px);
}

/* Modal Styles */
.custom-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: modalFadeIn 0.2s ease;
}

.custom-modal-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border);
  max-width: 90vw;
  max-height: 90vh;
  width: 600px;
  display: flex;
  flex-direction: column;
  animation: modalSlideIn 0.3s ease;
}

.custom-modal-content.large {
  width: 900px;
  max-width: 90vw;
}

.custom-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.custom-modal-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.modal-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.custom-modal-body {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
  min-height: 0;
}

.custom-modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 20px 24px;
  border-top: 1px solid var(--border);
  background: var(--background);
}

/* Success Toast */
.success-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: rgba(5, 150, 105, 0.95);
  color: white;
  padding: 16px 20px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  z-index: 10001;
  backdrop-filter: blur(8px);
  box-shadow: var(--shadow-lg);
  animation: slideInRight 0.3s ease;
}

/* Animations */
@keyframes modalFadeIn {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }

  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    justify-content: stretch;
  }

  .action-btn {
    flex: 1;
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .card-header {
    flex-direction: column;
    align-items: stretch;
  }

  .domain-input-wrapper {
    flex-direction: column;
    align-items: stretch;
  }

  .subdomain-input {
    flex: 1;
    min-width: 100%;
  }

  .form-actions {
    flex-direction: column;
  }

  .form-actions .action-btn {
    width: 100%;
  }

  .resources-grid {
    grid-template-columns: 1fr;
  }

  .custom-modal-content {
    width: 95vw;
    max-width: none;
    margin: 20px;
  }

  .modern-table {
    min-width: 600px;
  }
}

@media (max-width: 480px) {
  .header-content h1 {
    font-size: 24px;
  }

  .stat-content h3 {
    font-size: 24px;
  }

  .custom-modal-header,
  .custom-modal-body {
    padding: 20px;
  }

  .success-toast {
    bottom: 16px;
    right: 16px;
    left: 16px;
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }

  .toggle-slider:before {
    background-color: #f1f5f9;
  }
}
</style>