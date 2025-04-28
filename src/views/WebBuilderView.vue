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
                <ion-button 
                  expand="block" 
                  color="tertiary" 
                  @click="openWebBuilder()"
                  class="open-button"
                >
                  <ion-icon name="open-outline" slot="start"></ion-icon>
                  Web Builder öffnen
                </ion-button>
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
                  <ion-item 
                    v-for="page in pages" 
                    :key="page.id" 
                    button 
                    @click="openPageEditor(page)"
                  >
                    <ion-icon 
                      :name="Number(page.is_home) === 1 ? 'home-outline' : 'document-outline'" 
                      slot="start"
                      :color="Number(page.is_home) === 1 ? 'primary' : ''"
                    ></ion-icon>
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
                  <ion-button 
                    expand="block" 
                    color="primary" 
                    @click="createNewPage"
                    class="action-button"
                  >
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
              <ion-textarea v-model="newPage.metaDescription" placeholder="Kurze Beschreibung für Suchmaschinen"></ion-textarea>
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
      // API-Aufruf, um die Web Builder URL zu erhalten
      axios.post(
        'projects.php',
        qs.stringify({
          openWebBuilder: true,
          project: projectName.value
        })
      )
      .then(response => {
        if (response.data.success && response.data.url) {
          // Öffnen des Web Builders in einem neuen Tab
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
          newPage: true,  // Verwenden wir nur einen eindeutigen Parameter
          project: projectName.value,
          name: newPage.value.name,
          slug: newPage.value.slug || generateSlug(newPage.value.name),
          title: newPage.value.title || newPage.value.name,
          meta_description: newPage.value.metaDescription,
          is_home: newPage.value.isHome ? 1 : 0
        };
        
        const response = await axios.post('web_pages.php', qs.stringify(pageData));
        
        if (response.data.success) {
          // Schließen des Modals und Neuladen der Seiten
          closeNewPageModal();
          fetchPages();
          
          // Erfolgsmeldung anzeigen
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
          
          // Fehler-Toast anzeigen
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
        
        // Fehler-Toast anzeigen
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
      // Hier könnte ein Bearbeitungs-Modal für eine bestehende Seite geöffnet werden
      console.log('Seite bearbeiten:', page);
    };
    
    const createNewTemplate = () => {
      // Hier könnte ein Modal zum Erstellen einer neuen Vorlage geöffnet werden
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
      // Hier könnte Code zum Anwenden des Templates sein
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
      // Daten laden, wenn die Komponente eingebunden wird
      fetchPages();
      fetchTemplates();
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
      openLiveWebsite
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

.action-button + .action-button {
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
</style>