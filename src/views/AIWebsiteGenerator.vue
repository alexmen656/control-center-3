<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button></ion-menu-button>
        </ion-buttons>
        <ion-title>AI Website Generator</ion-title>
      </ion-toolbar>
    </ion-header>
    
    <ion-content>
      <div class="container">
        <ion-card>
          <ion-card-header>
            <ion-card-title>
              <div class="title-container">
                <ion-icon name="rocket-outline" class="heading-icon"></ion-icon>
                <span>KI Website Generator</span>
              </div>
            </ion-card-title>
            <ion-card-subtitle>
              Beschreiben Sie Ihre Website und unsere KI erstellt sie für Sie
            </ion-card-subtitle>
          </ion-card-header>
          
          <ion-card-content>
            <div class="prompt-container">
              <ion-item>
                <ion-label position="stacked">Beschreiben Sie Ihre Website</ion-label>
                <ion-textarea
                  v-model="prompt"
                  placeholder="z.B. 'Erstellen Sie eine Website für ein Café mit modernem Design. Mit einer Startseite, Speisekarte, Über uns und Kontaktseite.'"
                  :rows="5"
                  class="prompt-input"
                  :counter="true"
                  :maxlength="500"
                ></ion-textarea>
              </ion-item>
              
              <div class="options-container">
                <ion-item>
                  <ion-label>Projektname</ion-label>
                  <ion-input v-model="projectName" placeholder="Geben Sie einen Namen für Ihre neue Website ein"></ion-input>
                </ion-item>
                
                <ion-item>
                  <ion-label>Design-Stil</ion-label>
                  <ion-select v-model="styleOption" interface="popover">
                    <ion-select-option value="modern">Modern</ion-select-option>
                    <ion-select-option value="minimalist">Minimalistisch</ion-select-option>
                    <ion-select-option value="classic">Klassisch</ion-select-option>
                    <ion-select-option value="colorful">Farbenfroh</ion-select-option>
                    <ion-select-option value="professional">Professionell</ion-select-option>
                    <ion-select-option value="playful">Verspielt</ion-select-option>
                    <ion-select-option value="elegant">Elegant</ion-select-option>
                  </ion-select>
                </ion-item>
                
                <ion-item>
                  <ion-label>Farbschema</ion-label>
                  <ion-select v-model="colorScheme" interface="popover">
                    <ion-select-option value="light">Hell</ion-select-option>
                    <ion-select-option value="dark">Dunkel</ion-select-option>
                    <ion-select-option value="colorful">Bunt</ion-select-option>
                    <ion-select-option value="monochrome">Monochrom</ion-select-option>
                    <ion-select-option value="pastel">Pastell</ion-select-option>
                    <ion-select-option value="vibrant">Lebhaft</ion-select-option>
                  </ion-select>
                </ion-item>

                <ion-item>
                  <ion-label>Branche/Kategorie</ion-label>
                  <ion-select v-model="industryType" interface="popover">
                    <ion-select-option value="restaurant">Restaurant/Café</ion-select-option>
                    <ion-select-option value="business">Business/Unternehmen</ion-select-option>
                    <ion-select-option value="retail">Einzelhandel</ion-select-option>
                    <ion-select-option value="portfolio">Portfolio/Künstler</ion-select-option>
                    <ion-select-option value="education">Bildung</ion-select-option>
                    <ion-select-option value="technology">Technologie</ion-select-option>
                    <ion-select-option value="health">Gesundheit/Wellness</ion-select-option>
                    <ion-select-option value="other">Andere</ion-select-option>
                  </ion-select>
                </ion-item>
                
                <ion-item lines="none">
                  <ion-label>Gewünschte Seiten</ion-label>
                </ion-item>
                <div class="checkbox-container">
                  <ion-item lines="none">
                    <ion-checkbox v-model="pages.home">Startseite</ion-checkbox>
                  </ion-item>
                  <ion-item lines="none">
                    <ion-checkbox v-model="pages.about">Über uns</ion-checkbox>
                  </ion-item>
                  <ion-item lines="none">
                    <ion-checkbox v-model="pages.services">Dienstleistungen</ion-checkbox>
                  </ion-item>
                  <ion-item lines="none">
                    <ion-checkbox v-model="pages.products">Produkte</ion-checkbox>
                  </ion-item>
                  <ion-item lines="none">
                    <ion-checkbox v-model="pages.gallery">Galerie</ion-checkbox>
                  </ion-item>
                  <ion-item lines="none">
                    <ion-checkbox v-model="pages.blog">Blog</ion-checkbox>
                  </ion-item>
                  <ion-item lines="none">
                    <ion-checkbox v-model="pages.contact">Kontakt</ion-checkbox>
                  </ion-item>
                </div>
              </div>
              
              <div class="button-container">
                <ion-button expand="block" @click="generateWebsite" :disabled="isGenerating || !prompt || !projectName">
                  <ion-icon v-if="!isGenerating" name="rocket-outline" slot="start"></ion-icon>
                  <ion-spinner v-else name="dots"></ion-spinner>
                  {{ isGenerating ? 'Generiere...' : 'Website generieren' }}
                </ion-button>
              </div>
            </div>
          </ion-card-content>
        </ion-card>
        
        <ion-card v-if="generationStatus !== ''">
          <ion-card-header>
            <ion-card-title>Generator Status</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-progress-bar v-if="isGenerating" type="indeterminate"></ion-progress-bar>
            <div class="status-container">
              <p>{{ generationStatus }}</p>
              
              <div v-if="generationComplete">
                <ion-button color="success" expand="block" @click="viewWebsite">
                  <ion-icon name="eye-outline" slot="start"></ion-icon>
                  Website ansehen
                </ion-button>
              </div>
            </div>
          </ion-card-content>
        </ion-card>
        
        <div class="examples-container">
          <h3>Beispiele</h3>
          <ion-list lines="full">
            <ion-item button @click="useExample('Erstellen Sie eine professionelle Website für eine Rechtsanwaltskanzlei mit einer Startseite, Anwaltsprofilen, Fachgebieten und Kontaktinformationen.')">
              <ion-icon name="business-outline" slot="start"></ion-icon>
              <ion-label>
                <h2>Rechtsanwaltskanzlei</h2>
                <p>Professionelle Website mit Anwaltsprofilen und Fachgebieten</p>
              </ion-label>
            </ion-item>
            <ion-item button @click="useExample('Erstellen Sie eine farbenfrohe Website für eine Bäckerei mit einer Homepage, Produktgalerie, Über uns Seite und einem Bestellformular.')">
              <ion-icon name="cafe-outline" slot="start"></ion-icon>
              <ion-label>
                <h2>Bäckerei Website</h2>
                <p>Farbenfrohe Website mit Produktgalerie und Bestellformular</p>
              </ion-label>
            </ion-item>
            <ion-item button @click="useExample('Erstellen Sie eine minimalistische Portfolio-Website für einen Fotografen mit Startseite, Galerie, Dienstleistungen und Kontaktbereichen.')">
              <ion-icon name="camera-outline" slot="start"></ion-icon>
              <ion-label>
                <h2>Fotografie Portfolio</h2>
                <p>Minimalistisches Design mit Galerie und Dienstleistungen</p>
              </ion-label>
            </ion-item>
            <ion-item button @click="useExample('Erstellen Sie eine moderne E-Commerce-Website für einen Online-Shop für Sportbekleidung mit Produktkategorien, Warenkorb und Zahlungssystem.')">
              <ion-icon name="shirt-outline" slot="start"></ion-icon>
              <ion-label>
                <h2>Online-Shop für Sportbekleidung</h2>
                <p>E-Commerce Website mit Produktkategorien und Warenkorb</p>
              </ion-label>
            </ion-item>
          </ion-list>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import axios from 'axios';
import qs from 'qs';
import { defineComponent } from 'vue';
import { toastController } from '@ionic/vue';

export default defineComponent({
  name: "AIWebsiteGenerator",
  data() {
    return {
      prompt: "",
      projectName: "",
      styleOption: "modern",
      colorScheme: "light",
      industryType: "business",
      pages: {
        home: true,
        about: true,
        services: false,
        products: false,
        gallery: false,
        blog: false,
        contact: true
      },
      isGenerating: false,
      generationStatus: "",
      generationComplete: false,
      generatedProjectId: null,
      backendUrl: "http://localhost:3000" // URL to the AI generation backend
    };
  },
  methods: {
    useExample(example) {
      this.prompt = example;
      
      // Generate a project name based on the prompt
      const keywords = ["Website", "Site", "Page", "Portal", "Platform"];
      
      // Determine project type based on example content
      const type = example.includes("Rechtsanwaltskanzlei") ? "Kanzlei" :
                  example.includes("Bäckerei") ? "Bäckerei" :
                  example.includes("Fotografen") ? "Fotografie" : 
                  example.includes("Sportbekleidung") ? "Shop" :
                  "Projekt";
                  
      this.projectName = `${type} ${keywords[Math.floor(Math.random() * keywords.length)]}`;
      
      // Set appropriate industry type
      if (example.includes("Rechtsanwaltskanzlei")) {
        this.industryType = "business";
        this.styleOption = "professional";
        this.pages.services = true;
      } else if (example.includes("Bäckerei")) {
        this.industryType = "restaurant";
        this.styleOption = "colorful";
        this.pages.products = true;
        this.pages.gallery = true;
      } else if (example.includes("Fotografen")) {
        this.industryType = "portfolio";
        this.styleOption = "minimalist";
        this.pages.gallery = true;
        this.pages.services = true;
      } else if (example.includes("Sportbekleidung")) {
        this.industryType = "retail";
        this.styleOption = "modern";
        this.pages.products = true;
      }
    },
    
    async generateWebsite() {
      if (!this.prompt || !this.projectName) return;
      
      this.isGenerating = true;
      this.generationStatus = "Starte Website-Generierung...";
      this.generationComplete = false;
      
      try {
        // Update status messages for better user experience
        await this.updateStatusWithDelay("Analysiere deine Anforderungen...", 1000);
        await this.updateStatusWithDelay("Entwerfe Website-Struktur...", 2000);
        await this.updateStatusWithDelay("Generiere Seiteninhalt...", 3000);
        await this.updateStatusWithDelay("Erstelle Komponenten und Styles...", 2000);
        
        // Get selected pages as string
        const selectedPages = Object.keys(this.pages)
          .filter(key => this.pages[key])
          .join(', ');
        
        // Format style string that combines style and color scheme
        const styleDescription = `${this.styleOption}${this.colorScheme !== 'light' ? ' with ' + this.colorScheme + ' color scheme' : ''}`;
        
        // Enhanced prompt that includes all the information
        const enhancedPrompt = `${this.prompt} 
Industry: ${this.industryType}. 
Include these pages: ${selectedPages}.
The site should be for: ${this.projectName}.`;
        
        // Make API call to generate website using the backend format from the example
        const response = await axios.post(
          `${this.backendUrl}/generate`,
          {
            projectName: this.projectName,
            prompt: enhancedPrompt,
            style: styleDescription
          },
          {
            headers: {
              'Content-Type': 'application/json'
            }
          }
        );
        
        // Handle the response
        if (response.data && response.data.success) {
          this.generatedProjectId = response.data.projectId || this.projectName.toLowerCase().replace(/\s+/g, '-');
          this.generationStatus = "Website erfolgreich generiert!";
          this.generationComplete = true;
          
          // After successful AI generation, create the project in our system
          await this.createProjectInSystem(response.data);
          
          // Show success toast using Ionic's toastController
          const toast = await toastController.create({
            message: "Ihre Website wurde erfolgreich generiert!",
            duration: 3000,
            position: "bottom",
            color: "success"
          });
          await toast.present();
          
          // Update sidebar
          this.emitter.emit("updateSidebar");
        } else {
          throw new Error(response.data?.message || "Failed to generate website");
        }
      } catch (error) {
        console.error("Error generating website:", error);
        this.generationStatus = "Fehler: " + (error.message || "Ein unerwarteter Fehler ist aufgetreten");
        
        // Show error toast
        const toast = await toastController.create({
          message: "Fehler bei der Verbindung zum Server. Bitte versuchen Sie es erneut.",
          duration: 3000,
          position: "bottom",
          color: "danger"
        });
        await toast.present();
      } finally {
        this.isGenerating = false;
      }
    },
    
    async createProjectInSystem(aiResponse) {
      // Create the project in our system using the AI generation result
      try {
        const projectResponse = await this.$axios.post(
          "projects.php",
          qs.stringify({
            createProject: "createProject",
            projectName: this.projectName,
            projectIcon: "globe-outline",
            ai_generator: true,
            aiGenerated: JSON.stringify(aiResponse)
          })
        );
        
        console.log("Project created in system:", projectResponse.data);
      } catch (error) {
        console.error("Error creating project in system:", error);
        
        // Show error toast for project creation failure
        const toast = await toastController.create({
          message: "Website wurde generiert, konnte aber nicht im System gespeichert werden.",
          duration: 3000,
          position: "bottom",
          color: "warning"
        });
        await toast.present();
      }
    },
    
    updateStatusWithDelay(status, delay) {
      return new Promise(resolve => {
        setTimeout(() => {
          this.generationStatus = status;
          resolve();
        }, delay);
      });
    },
    
    viewWebsite() {
      if (this.generatedProjectId) {
        this.$router.push(`/project/${this.generatedProjectId}`);
      }
    }
  }
});
</script>

<style scoped>
.container {
  padding: 16px;
  max-width: 800px;
  margin: 0 auto;
}

.prompt-container {
  margin-bottom: 20px;
}

.options-container {
  margin: 20px 0;
}

.checkbox-container {
  display: flex;
  flex-wrap: wrap;
  margin: 0 16px 16px;
}

.checkbox-container ion-item {
  width: 50%;
}

.button-container {
  margin-top: 20px;
}

.status-container {
  margin: 10px 0;
}

.examples-container {
  margin-top: 30px;
}

.prompt-input {
  --background: var(--ion-color-light);
  --padding-start: 10px;
  border-radius: 8px;
}

.title-container {
  display: flex;
  align-items: center;
}

.heading-icon {
  font-size: 24px;
  margin-right: 10px;
}

h3 {
  margin-left: 16px;
  color: var(--ion-color-medium);
}

@media (max-width: 576px) {
  .checkbox-container ion-item {
    width: 100%;
  }
}
</style>