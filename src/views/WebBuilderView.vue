<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1" />
          <ion-col size="10">
            <!-- Header-Bereich mit Titel und Beschreibung -->
            <ion-card class="header-card">
              <ion-card-header>
                <ion-card-title>
                  <div class="title-container">
                    <ion-icon name="globe-outline" class="header-icon"></ion-icon>
                    <span>Web Builder</span>
                  </div>
                </ion-card-title>
                <ion-card-subtitle>
                  Erstellen und gestalten Sie Ihre Website mit unserem visuellen Web Builder
                </ion-card-subtitle>
              </ion-card-header>
              <ion-card-content>
                <p>
                  Der Web Builder ist ein leistungsstarkes Tool zur visuellen Erstellung von Webseiten.
                  Sie können Komponenten per Drag & Drop hinzufügen und bearbeiten,
                  ohne dass Programmierkenntnisse erforderlich sind.
                </p>

                <!-- Button zum direkten Öffnen des Web Builders -->
                <ion-button expand="block" color="tertiary" @click="openWebBuilder()" class="open-button">
                  <ion-icon name="open-outline" slot="start"></ion-icon>
                  Web Builder öffnen
                </ion-button>
              </ion-card-content>
            </ion-card>

            <!-- Web Builder Subdomain -->
            <ion-card>
              <ion-card-header>
                <ion-card-title>
                  <div class="section-title">Web Builder Subdomain</div>
                </ion-card-title>
                <ion-card-subtitle>
                  Verbinden Sie eine eigene Subdomain mit Ihrem Web Builder Projekt
                </ion-card-subtitle>
              </ion-card-header>
              <ion-card-content>
                <div v-if="loadingWebBuilderDomain" class="loading-container">
                  <ion-spinner name="crescent"></ion-spinner>
                  <p>Laden...</p>
                </div>
                <div v-else-if="!mainDomain" class="empty-state">
                  <ion-icon name="alert-circle-outline" size="large" color="warning"></ion-icon>
                  <p>Bitte zuerst eine Main Domain in Project Info konfigurieren</p>
                  <ion-button size="small" @click="$router.push(`/project/${projectName}/info`)">
                    Zu Project Info
                  </ion-button>
                </div>
                <div v-else>
                  <div class="info-box">
                    <ion-icon name="information-circle-outline"></ion-icon>
                    <span>Main Domain: <strong>{{ mainDomain }}</strong></span>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Subdomain</label>
                    <div class="domain-input-wrapper">
                      <input v-model="webBuilderDomain.subdomain" placeholder="blog"
                        class="modern-input subdomain-input" :disabled="savingWebBuilderDomain" />
                      <span class="domain-suffix">.{{ mainDomain }}</span>
                    </div>
                    <small class="help-text">Beispiel: blog.{{ mainDomain }}</small>
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

                  <div v-if="webBuilderDomain.ssl_status" class="form-group">
                    <label class="form-label">SSL Status</label>
                    <span :class="['status-badge', sslStatusColor(webBuilderDomain.ssl_status)]">
                      {{ webBuilderDomain.ssl_status }}
                    </span>
                  </div>

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
                    <ion-button v-if="webBuilderDomain.id" color="danger" @click="deleteWebBuilderDomain"
                      :disabled="savingWebBuilderDomain">
                      <ion-icon name="trash-outline" slot="start"></ion-icon>
                      Löschen
                    </ion-button>
                    <ion-button color="primary" @click="saveWebBuilderDomain"
                      :disabled="savingWebBuilderDomain || !webBuilderDomain.subdomain">
                      <ion-icon name="save-outline" slot="start"></ion-icon>
                      {{ webBuilderDomain.id ? 'Aktualisieren' : 'Speichern' }}
                    </ion-button>
                  </div>
                </div>
              </ion-card-content>
            </ion-card>

            <!-- Abschnitt für Projekt-Seiten -->
            <ion-card>
              <ion-card-header>
                <ion-card-title>
                  <div class="section-title">Projekt-Seiten</div>
                </ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <div v-if="isLoading" class="loading-container">
                  <ion-spinner name="crescent"></ion-spinner>
                  <p>Laden...</p>
                </div>

                <div v-else-if="pages.length === 0" class="empty-state">
                  <ion-icon name="document-outline" size="large"></ion-icon>
                  <p>Keine Seiten gefunden</p>
                  <ion-button size="small" @click="createNewPage">Erste Seite erstellen</ion-button>
                </div>

                <ion-list v-else lines="full">
                  <ion-item v-for="page in pages" :key="page.id" button @click="openPageEditor(page)">
                    <ion-icon :name="Number(page.is_home) === 1 ? 'home-outline' : 'document-outline'" slot="start"
                      :color="Number(page.is_home) === 1 ? 'primary' : ''"></ion-icon>
                    <ion-label>
                      <h2>{{ page.name }}</h2>
                      <p>{{ Number(page.is_home) === 1 ? 'Homepage' : 'URL: ' + page.slug }}</p>
                    </ion-label>
                    <ion-badge v-if="Number(page.is_home) === 1" color="primary" slot="end">Homepage</ion-badge>
                    <ion-buttons slot="end">
                      <ion-button @click.stop="editPage(page)">
                        <ion-icon name="create-outline" slot="icon-only"></ion-icon>
                      </ion-button>
                    </ion-buttons>
                  </ion-item>
                </ion-list>

                <div class="action-buttons">
                  <ion-button expand="block" color="primary" @click="createNewPage" class="action-button">
                    <ion-icon name="add-outline" slot="start"></ion-icon>
                    Neue Seite erstellen
                  </ion-button>
                </div>
              </ion-card-content>
            </ion-card>

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

            <!-- Info-Box mit Links und Hilfe -->
            <ion-card>
              <ion-card-header>
                <ion-card-title>
                  <div class="section-title">Hilfe & Ressourcen</div>
                </ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <ion-list lines="none">
                  <ion-item>
                    <ion-icon name="book-outline" slot="start" color="primary"></ion-icon>
                    <ion-label>
                      <h2>Dokumentation</h2>
                      <p>Umfassende Anleitungen zur Verwendung des Web Builders</p>
                    </ion-label>
                    <ion-buttons slot="end">
                      <ion-button @click="openDocumentation()">
                        <ion-icon name="open-outline" slot="icon-only"></ion-icon>
                      </ion-button>
                    </ion-buttons>
                  </ion-item>
                  <ion-item>
                    <ion-icon name="videocam-outline" slot="start" color="primary"></ion-icon>
                    <ion-label>
                      <h2>Video-Tutorials</h2>
                      <p>Schritt-für-Schritt-Anleitungen in Videoform</p>
                    </ion-label>
                    <ion-buttons slot="end">
                      <ion-button @click="openTutorials()">
                        <ion-icon name="open-outline" slot="icon-only"></ion-icon>
                      </ion-button>
                    </ion-buttons>
                  </ion-item>
                  <ion-item>
                    <ion-icon name="earth-outline" slot="start" color="primary"></ion-icon>
                    <ion-label>
                      <h2>Website anzeigen</h2>
                      <p>Öffnet die live-Version Ihrer Website</p>
                    </ion-label>
                    <ion-buttons slot="end">
                      <ion-button @click="openLiveWebsite()">
                        <ion-icon name="open-outline" slot="icon-only"></ion-icon>
                      </ion-button>
                    </ion-buttons>
                  </ion-item>
                </ion-list>
              </ion-card-content>
            </ion-card>
          </ion-col>
          <ion-col size="1" />
        </ion-row>
      </ion-grid>

      <!-- Modal für neue Seite -->
      <ion-modal :is-open="isNewPageModalOpen" @didDismiss="closeNewPageModal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="closeNewPageModal">Abbrechen</ion-button>
            </ion-buttons>
            <ion-title>Neue Seite erstellen</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="saveNewPage" :disabled="!newPage.name">Speichern</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <ion-list>
            <ion-item>
              <ion-label position="stacked">Seitenname*</ion-label>
              <ion-input v-model="newPage.name" placeholder="z.B. Über Uns"></ion-input>
            </ion-item>
            <ion-item>
              <ion-label position="stacked">URL-Slug</ion-label>
              <ion-input v-model="newPage.slug" placeholder="z.B. ueber-uns"></ion-input>
              <ion-note>Wird automatisch aus dem Namen generiert, wenn leer</ion-note>
            </ion-item>
            <ion-item>
              <ion-label position="stacked">Meta-Titel</ion-label>
              <ion-input v-model="newPage.title" placeholder="SEO-Titel der Seite"></ion-input>
            </ion-item>
            <ion-item>
              <ion-label position="stacked">Meta-Beschreibung</ion-label>
              <ion-textarea v-model="newPage.metaDescription"
                placeholder="Kurze Beschreibung für Suchmaschinen"></ion-textarea>
            </ion-item>
            <ion-item lines="none">
              <ion-checkbox v-model="newPage.isHome">Als Homepage festlegen</ion-checkbox>
            </ion-item>
          </ion-list>
        </ion-content>
      </ion-modal>

      <!-- Template-Vorschau Modal -->
      <ion-modal :is-open="isTemplatePreviewOpen" @didDismiss="closeTemplatePreview">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="closeTemplatePreview">Schließen</ion-button>
            </ion-buttons>
            <ion-title>{{ selectedTemplate ? selectedTemplate.title : 'Vorlage Vorschau' }}</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="useSelectedTemplate" color="primary">Verwenden</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content>
          <div v-if="selectedTemplate" class="template-preview">
            <!-- Hier könnte eine richtige Vorschau-Komponente sein -->
            <div class="html-preview" v-html="selectedTemplate.html_code"></div>
          </div>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import qs from 'qs';

export default defineComponent({
  name: 'WebBuilderView',

  setup() {
    const route = useRoute();
    const projectName = ref(route.params.project);
    const pages = ref([]);
    const templates = ref([]);
    const isLoading = ref(true);

    // Web Builder Domain
    const loadingWebBuilderDomain = ref(true);
    const savingWebBuilderDomain = ref(false);
    const webBuilderDomainError = ref('');
    const webBuilderDomainSuccess = ref('');
    const mainDomain = ref(''); // Main Domain aus Project Info
    const webBuilderDomain = ref({
      id: null,
      subdomain: '',
      domain: '',
      is_enabled: true,
      ssl_status: ''
    });

    // Neue Seite Modal
    const isNewPageModalOpen = ref(false);
    const newPage = ref({
      name: '',
      slug: '',
      title: '',
      metaDescription: '',
      isHome: false
    });

    // Template Vorschau Modal
    const isTemplatePreviewOpen = ref(false);
    const selectedTemplate = ref(null);

    const fetchPages = async () => {
      try {
        const response = await axios.post(
          'web_pages.php',
          qs.stringify({
            getPagesByProject: true,
            project: projectName.value
          })
        );

        if (response.data) {// && Array.isArray(response.data)
          pages.value = response.data.pages;
        }
      } catch (error) {
        console.error('Fehler beim Laden der Seiten:', error);
      }
    };

    const fetchWebBuilderDomain = async () => {
      loadingWebBuilderDomain.value = true;
      webBuilderDomainError.value = '';
      try {
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

        // Dann Web Builder Domain laden
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
            is_enabled: data.is_enabled,
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

      // Subdomain validieren
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

      try {
        const response = await axios.post(
          'web_builder_domains.php',
          qs.stringify({
            action: 'save',
            project: projectName.value,
            subdomain: webBuilderDomain.value.subdomain,
            main_domain: mainDomain.value,
            is_enabled: webBuilderDomain.value.is_enabled ? 'true' : 'false'
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
      } finally {
        isLoading.value = false;
      }
    };

    const openWebBuilder = () => {
      axios.post(
        'projects.php',
        qs.stringify({
          openWebBuilder: true,
          project: projectName.value
        })
      )
        .then(response => {
          if (response.data.success && response.data.url) {
            window.open(response.data.url, '_blank');
          } else {
            console.error('Fehler beim Öffnen des Web Builders:', response.data.message);
          }
        })
        .catch(error => {
          console.error('Fehler beim Öffnen des Web Builders:', error);
        });
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

          if (window.Ionic) {
            const toast = await window.Ionic.toastController.create({
              message: 'Seite erfolgreich erstellt!',
              duration: 2000,
              position: 'bottom',
              color: 'success'
            });
            toast.present();
          }
        } else {
          console.error('Fehler beim Erstellen der Seite:', response.data.message);

          if (window.Ionic) {
            const toast = await window.Ionic.toastController.create({
              message: 'Fehler: ' + (response.data.message || 'Unbekannter Fehler'),
              duration: 3000,
              position: 'bottom',
              color: 'danger'
            });
            toast.present();
          }
        }
      } catch (error) {
        console.error('Fehler beim Erstellen der Seite:', error);

        if (window.Ionic) {
          const toast = await window.Ionic.toastController.create({
            message: 'Netzwerkfehler beim Erstellen der Seite',
            duration: 3000,
            position: 'bottom',
            color: 'danger'
          });
          toast.present();
        }
      }
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
      projectName
    };
  }
});
</script>

<style scoped>
.header-card {
  margin-bottom: 1rem;
  border-radius: 16px;
}

.header-icon {
  font-size: 24px;
  margin-right: 10px;
  vertical-align: middle;
}

.title-container {
  display: flex;
  align-items: center;
}

.section-title {
  font-size: 18px;
  font-weight: 500;
}

.open-button {
  margin-top: 20px;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 30px 0;
  color: var(--ion-color-medium);
  text-align: center;
}

.empty-state ion-icon {
  margin-bottom: 10px;
  font-size: 48px;
}

.action-buttons {
  margin-top: 16px;
}

.action-button+.action-button {
  margin-top: 8px;
}

.template-preview {
  padding: 16px;
  background-color: var(--ion-color-light);
}

.html-preview {
  border: 1px solid var(--ion-color-medium);
  padding: 16px;
  border-radius: 8px;
  background-color: white;
  min-height: 200px;
  overflow: auto;
}

@media (prefers-color-scheme: dark) {
  ion-list {
    background-color: #000;
  }

  ion-item {
    --background: #000;
  }

  .html-preview {
    background-color: #1e1e1e;
    color: #f8f8f8;
  }
}

/* Web Builder Domain Styles */
.form-group {
  margin-bottom: 20px;
}

.info-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: var(--ion-color-primary-tint);
  border-radius: 8px;
  margin-bottom: 20px;
  color: var(--ion-color-primary);
  font-size: 14px;
}

.info-box ion-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.help-text {
  display: block;
  margin-top: 6px;
  font-size: 13px;
  color: var(--ion-color-medium);
}

.form-label {
  display: block;
  font-weight: 500;
  margin-bottom: 8px;
  color: var(--ion-text-color);
}

.modern-input {
  width: 100%;
  padding: 12px;
  border: 1px solid var(--ion-color-medium);
  border-radius: 8px;
  font-size: 14px;
  background: var(--ion-background-color);
  color: var(--ion-text-color);
  transition: border-color 0.2s;
}

.modern-input:focus {
  outline: none;
  border-color: var(--ion-color-primary);
}

.modern-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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
  color: var(--ion-color-medium);
  font-size: 14px;
  white-space: nowrap;
}

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
  background-color: var(--ion-color-medium);
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
  background-color: var(--ion-color-primary);
}

.toggle-switch input:checked+.toggle-slider:before {
  transform: translateX(22px);
}

.toggle-switch input:disabled+.toggle-slider {
  opacity: 0.5;
  cursor: not-allowed;
}

.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.success {
  background: var(--ion-color-success-tint);
  color: var(--ion-color-success);
}

.status-badge.warning {
  background: var(--ion-color-warning-tint);
  color: var(--ion-color-warning);
}

.status-badge.danger {
  background: var(--ion-color-danger-tint);
  color: var(--ion-color-danger);
}

.info-message {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px;
  border-radius: 8px;
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
  background: var(--ion-color-success-tint);
  color: var(--ion-color-success-shade);
  border: 1px solid var(--ion-color-success);
}

.info-message small {
  opacity: 0.8;
  font-size: 13px;
}

.setup-instructions {
  background: var(--ion-color-light);
  border: 1px solid var(--ion-color-medium);
  border-radius: 8px;
  padding: 16px;
  margin-top: 16px;
}

.instruction-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.instruction-header ion-icon {
  font-size: 24px;
  color: var(--ion-color-primary);
}

.instruction-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.instruction-content {
  margin-top: 12px;
}

.instruction-step {
  margin-bottom: 12px;
}

.instruction-step h5 {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
}

.instruction-step p {
  margin: 0 0 8px 0;
  font-size: 14px;
  color: var(--ion-color-medium);
}

.code-block {
  background: var(--ion-color-dark);
  color: var(--ion-color-light);
  padding: 12px;
  border-radius: 6px;
  font-family: monospace;
  font-size: 13px;
  line-height: 1.6;
}

.code-block div {
  margin: 4px 0;
}

.alert {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  border-radius: 8px;
  margin-top: 12px;
  font-size: 14px;
}

.alert ion-icon {
  font-size: 20px;
}

.alert-error {
  background: var(--ion-color-danger-tint);
  color: var(--ion-color-danger);
}

.alert-success {
  background: var(--ion-color-success-tint);
  color: var(--ion-color-success);
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--ion-color-light);
}

@media (max-width: 768px) {
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

  .form-actions ion-button {
    width: 100%;
  }
}
</style>