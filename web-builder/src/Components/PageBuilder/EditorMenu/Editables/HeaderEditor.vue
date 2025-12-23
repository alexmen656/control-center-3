<script setup>
import { ref, onMounted, watch } from 'vue';
import PageBuilder from '@/composables/PageBuilder';
import { usePageBuilderStateStore } from '@/stores/page-builder-state';
import { useMediaLibraryStore } from '@/stores/media-library';
import MediaLibraryModal from '@/Components/Modals/MediaLibraryModal.vue';
import EditorAccordion from '@/Components/PageBuilder/EditorMenu/EditorAccordion.vue';

const mediaLibraryStore = useMediaLibraryStore();
const pageBuilderStateStore = usePageBuilderStateStore();
const pageBuilder = new PageBuilder(pageBuilderStateStore, mediaLibraryStore);

const getElement = ref(null);
const headerElement = ref(null);
const existingLinks = ref([]);
const existingDropdowns = ref([]);

// Form state für neue Links und Dropdown-Menüs
const newLink = ref({
  text: '',
  url: '',
  active: false
});

const newDropdown = ref({
  text: '',
  items: []
});

const newDropdownItem = ref({
  text: '',
  url: ''
});

const showMediaLibraryModal = ref(false);
const logoUrl = ref('');
const logoAlt = ref('');

// Button options
const newButton = ref({
  text: '',
  url: '',
  type: 'primary' // primary oder secondary
});

// Beim Mounten der Komponente
onMounted(() => {
  if (pageBuilderStateStore.getElement) {
    getElement.value = pageBuilderStateStore.getElement;
    
    // Finde das Header-Element (entweder das aktuelle oder das übergeordnete)
    if (getElement.value.tagName === 'HEADER') {
      headerElement.value = getElement.value;
    } else {
      // Suchen wir nach dem nächsten header-Element im DOM
      headerElement.value = getElement.value.closest('header');
    }

    // Falls wir ein Logo haben, dessen URL auslesen
    if (headerElement.value) {
      const logoImg = headerElement.value.querySelector('[data-header-logo] img');
      if (logoImg) {
        logoUrl.value = logoImg.src || '';
        logoAlt.value = logoImg.alt || '';
      }
      
      // Bestehende Links auslesen
      loadExistingLinks();
      loadExistingDropdowns();
    }
  }
});

// Watch für Änderungen im pageBuilderStateStore
watch(() => pageBuilderStateStore.getElement, (newValue) => {
  if (newValue) {
    getElement.value = newValue;
    
    if (getElement.value.tagName === 'HEADER') {
      headerElement.value = getElement.value;
    } else {
      headerElement.value = getElement.value.closest('header');
    }

    // Logo-Informationen aktualisieren
    if (headerElement.value) {
      const logoImg = headerElement.value.querySelector('[data-header-logo] img');
      if (logoImg) {
        logoUrl.value = logoImg.src || '';
        logoAlt.value = logoImg.alt || '';
      }
      
      // Bestehende Links aktualisieren
      loadExistingLinks();
      loadExistingDropdowns();
    }
  }
});

// Stelle sicher, dass alle Links als bearbeitbar markiert sind
const markNavigationLinksAsEditable = () => {
  if (!headerElement.value) return;
  
  // Markiere alle Links im Header mit data-editable-text
  const navLinks = headerElement.value.querySelectorAll('[data-header-links] a');
  navLinks.forEach(link => {
    if (!link.hasAttribute('data-editable-text')) {
      link.setAttribute('data-editable-text', 'true');
    }
  });
  
  // Markiere auch alle Dropdown-Items
  const dropdownItems = headerElement.value.querySelectorAll('.dropdown-menu a');
  dropdownItems.forEach(item => {
    if (!item.hasAttribute('data-editable-text')) {
      item.setAttribute('data-editable-text', 'true');
    }
  });
  
  // Initialisiere die Event-Listener für die bearbeitbaren Textelemente
  pageBuilder.setEventListenersForElements();
};

// Lade alle bestehenden Links
const loadExistingLinks = () => {
  existingLinks.value = [];
  if (!headerElement.value) return;
  
  const linksContainer = headerElement.value.querySelector('[data-header-links]');
  if (!linksContainer) return;
  
  // Finde alle direkten <a> Elemente
  const links = Array.from(linksContainer.children).filter(el => 
    el.tagName === 'A' && !el.closest('.dropdown-container')
  );
  
  links.forEach(link => {
    // Füge data-editable-text Attribut hinzu
    if (!link.hasAttribute('data-editable-text')) {
      link.setAttribute('data-editable-text', 'true');
    }
    
    existingLinks.value.push({
      text: link.textContent.trim(),
      url: link.getAttribute('href') || '#',
      active: link.classList.contains('border-indigo-500'),
      element: link
    });
  });
  
  // Initialisiere die Event-Listener für die bearbeitbaren Textelemente
  markNavigationLinksAsEditable();
};

// Lade alle bestehenden Dropdown-Menüs
const loadExistingDropdowns = () => {
  existingDropdowns.value = [];
  if (!headerElement.value) return;
  
  const linksContainer = headerElement.value.querySelector('[data-header-links]');
  if (!linksContainer) return;
  
  // Finde alle dropdown-container Elemente
  const dropdownContainers = linksContainer.querySelectorAll('.dropdown-container');
  
  dropdownContainers.forEach(container => {
    const trigger = container.querySelector('.dropdown-trigger');
    const menu = container.querySelector('.dropdown-menu');
    const items = [];
    
    // Markiere den Trigger als bearbeitbar, falls es noch nicht getan wurde
    if (trigger && !trigger.hasAttribute('data-editable-text')) {
      trigger.setAttribute('data-editable-text', 'true');
    }
    
    if (menu) {
      const menuLinks = menu.querySelectorAll('a');
      menuLinks.forEach(link => {
        // Markiere jeden Link als bearbeitbar
        if (!link.hasAttribute('data-editable-text')) {
          link.setAttribute('data-editable-text', 'true');
        }
        
        items.push({
          text: link.textContent.trim(),
          url: link.getAttribute('href') || '#'
        });
      });
    }
    
    existingDropdowns.value.push({
      text: trigger ? trigger.textContent.trim().replace(/\s*↓\s*$/, '') : 'Dropdown',
      items: items,
      element: container
    });
  });
  
  // Initialisiere die Event-Listener für die bearbeitbaren Textelemente
  markNavigationLinksAsEditable();
};

// Link löschen
const deleteLink = (index) => {
  if (!headerElement.value || !existingLinks.value[index]) return;
  
  const link = existingLinks.value[index];
  if (link.element) {
    link.element.remove();
    existingLinks.value.splice(index, 1);
  }
};

// Link aktiv/inaktiv setzen
const toggleLinkActive = (index) => {
  if (!headerElement.value || !existingLinks.value[index]) return;
  
  const link = existingLinks.value[index];
  const element = link.element;
  
  if (element) {
    // Aktiven Status ändern
    const allLinks = headerElement.value.querySelectorAll('[data-header-links] > a');
    allLinks.forEach(l => {
      l.classList.remove('border-indigo-500', 'text-gray-900');
      l.classList.add('border-transparent', 'text-gray-500');
    });
    
    if (link.active) {
      element.classList.remove('border-transparent', 'text-gray-500');
      element.classList.add('border-indigo-500', 'text-gray-900');
    }
  }
};

// Dropdown-Link löschen
const deleteDropdownItem = (dropdownIndex, itemIndex) => {
  if (!headerElement.value || !existingDropdowns.value[dropdownIndex]) return;
  
  const dropdown = existingDropdowns.value[dropdownIndex];
  const dropdownElement = dropdown.element;
  
  if (dropdownElement) {
    const menu = dropdownElement.querySelector('.dropdown-menu');
    if (!menu) return;
    
    const menuItems = Array.from(menu.querySelectorAll('a'));
    if (menuItems[itemIndex]) {
      menuItems[itemIndex].remove();
      dropdown.items.splice(itemIndex, 1);
    }
  }
};

// Dropdown löschen
const deleteDropdown = (index) => {
  if (!headerElement.value || !existingDropdowns.value[index]) return;
  
  const dropdown = existingDropdowns.value[index];
  if (dropdown.element) {
    dropdown.element.remove();
    existingDropdowns.value.splice(index, 1);
  }
};

// Handler-Funktionen
const addLink = () => {
  if (!headerElement.value || !newLink.value.text) return;
  
  // Verwenden der PageBuilder-Methode zur Initialisierung des Headers
  if (!pageBuilder.headerUtils) return;
  
  // Link hinzufügen
  pageBuilder.headerUtils.addNavigationLink(headerElement.value, {
    text: newLink.value.text,
    url: newLink.value.url || '#',
    active: newLink.value.active
  });
  
  // Formular zurücksetzen
  newLink.value = { text: '', url: '', active: false };
  
  // Bestehende Links aktualisieren und als bearbeitbar markieren
  loadExistingLinks();
};

const addDropdownItem = () => {
  if (!newDropdownItem.value.text) return;
  
  newDropdown.value.items.push({
    text: newDropdownItem.value.text,
    url: newDropdownItem.value.url || '#'
  });
  
  // Formular zurücksetzen
  newDropdownItem.value = { text: '', url: '' };
};

const removeDropdownItem = (index) => {
  newDropdown.value.items.splice(index, 1);
};

const addDropdown = () => {
  if (!headerElement.value || !newDropdown.value.text || newDropdown.value.items.length === 0) return;
  
  // Verwenden der PageBuilder-Methode zur Initialisierung des Headers
  if (!pageBuilder.headerUtils) return;
  
  // Dropdown hinzufügen
  pageBuilder.headerUtils.addDropdownMenu(headerElement.value, {
    text: newDropdown.value.text,
    items: newDropdown.value.items
  });
  
  // Formular zurücksetzen
  newDropdown.value = { text: '', items: [] };
  
  // Bestehende Dropdowns aktualisieren und als bearbeitbar markieren
  loadExistingDropdowns();
};

const updateLogo = () => {
  if (!headerElement.value) return;
  
  // Verwenden der PageBuilder-Methode zur Initialisierung des Headers
  if (!pageBuilder.headerUtils) return;
  
  // Logo aktualisieren
  pageBuilder.headerUtils.changeLogo(headerElement.value, {
    src: logoUrl.value,
    alt: logoAlt.value
  });
};

const openMediaLibrary = () => {
  showMediaLibraryModal.value = true;
};

const handleMediaSelected = () => {
  if (mediaLibraryStore.getCurrentImage && mediaLibraryStore.getCurrentImage.file) {
    logoUrl.value = mediaLibraryStore.getCurrentImage.file;
    updateLogo();
  }
  showMediaLibraryModal.value = false;
};

const closeMediaLibrary = () => {
  showMediaLibraryModal.value = false;
};

const addButton = () => {
  if (!headerElement.value || !newButton.value.text) return;
  
  // Verwenden der PageBuilder-Methode zur Initialisierung des Headers
  if (!pageBuilder.headerUtils) return;
  
  // Button hinzufügen
  pageBuilder.headerUtils.addActionButton(headerElement.value, {
    text: newButton.value.text,
    url: newButton.value.url || undefined,
    type: newButton.value.type
  });
  
  // Formular zurücksetzen
  newButton.value = { text: '', url: '', type: 'primary' };
};

// Bestehende Header leeren (alle Links und Dropdowns entfernen)
const clearHeaderLinks = () => {
  if (!headerElement.value) return;
  
  const linksContainer = headerElement.value.querySelector('[data-header-links]');
  if (!linksContainer) return;
  
  // Entferne alle Inhalte, aber behalte den Container
  linksContainer.innerHTML = '';
  
  // Listen aktualisieren
  existingLinks.value = [];
  existingDropdowns.value = [];
};

// Öffne den TipTap-Editor für einen Link
const editLinkWithTipTap = (element) => {
  if (!element) return;
  
  // Element für die Bearbeitung auswählen
  pageBuilder.handleTextElementClick({ stopPropagation: () => {} }, element);
};
</script>

<template>
  <EditorAccordion>
    <template #title>Header Bearbeitung</template>
    <template #content>
      <div v-if="!headerElement" class="flex items-center justify-center p-4 text-gray-500">
        <p>Bitte wähle einen Header zum Bearbeiten aus</p>
      </div>
      
      <template v-else>
        <!-- Logo Bearbeiten -->
        <div class="mb-6">
          <p class="myPrimaryParagraph font-medium py-0 my-4">Logo</p>
          <div class="my-3 py-3">
            <label class="myPrimaryInputLabel">Logo URL</label>
            <div class="flex items-center gap-2">
              <input 
                v-model="logoUrl" 
                class="myPrimaryInput flex-1" 
                type="text" 
                placeholder="URL zum Logo-Bild"
              />
              <button 
                @click="openMediaLibrary"
                class="h-10 w-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 aspect-square hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
              >
                <span class="material-symbols-outlined">image</span>
              </button>
            </div>
          </div>
          
          <div class="my-3 py-3">
            <label class="myPrimaryInputLabel">Alt-Text</label>
            <input 
              v-model="logoAlt" 
              class="myPrimaryInput" 
              type="text" 
              placeholder="Beschreibung des Logos für Screenreader"
            />
          </div>
          
          <button 
            @click="updateLogo"
            class="myPrimaryButton w-full"
          >
            Logo aktualisieren
          </button>
        </div>
        
        <!-- Hinweis zur Bearbeitung von Links -->
        <div class="mb-6 p-3 bg-blue-50 border border-blue-200 rounded-md">
          <p class="myPrimaryParagraph text-sm">
            <span class="font-bold">Tipp:</span> Doppelklicke auf einen Link im Header, um seinen Text mit dem visuellen Editor zu bearbeiten.
          </p>
        </div>
        
        <!-- Bestehende Links verwalten -->
        <div v-if="existingLinks.length > 0" class="mb-6">
          <p class="myPrimaryParagraph font-medium py-0 my-4">Bestehende Links</p>
          <ul class="space-y-3">
            <li v-for="(link, index) in existingLinks" :key="index" class="border border-gray-200 rounded-md p-3">
              <div class="flex items-center justify-between mb-2">
                <button 
                  @click="editLinkWithTipTap(link.element)"
                  class="mySecondaryButton text-sm px-3"
                >
                  Text bearbeiten
                </button>
                <span class="text-gray-500 text-sm">{{ link.text }}</span>
              </div>
              
              <div class="space-y-2">
                <div>
                  <label class="myPrimaryInputLabel">URL</label>
                  <input 
                    v-model="link.element.href" 
                    class="myPrimaryInput" 
                    type="text" 
                    placeholder="URL"
                  />
                </div>
                
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <input 
                      :id="`link-active-${index}`" 
                      v-model="link.active" 
                      type="checkbox" 
                      class="myRadio mr-2"
                      @change="toggleLinkActive(index)"
                    />
                    <label :for="`link-active-${index}`" class="myPrimaryInputLabel m-0">Aktiv</label>
                  </div>
                  <button 
                    @click="deleteLink(index)"
                    class="myPrimaryErrorButton"
                  >
                    Löschen
                  </button>
                </div>
              </div>
            </li>
          </ul>
        </div>
        
        <!-- Bestehende Dropdowns verwalten -->
        <div v-if="existingDropdowns.length > 0" class="mb-6">
          <p class="myPrimaryParagraph font-medium py-0 my-4">Bestehende Dropdown-Menüs</p>
          <div v-for="(dropdown, dIndex) in existingDropdowns" :key="dIndex" class="border border-gray-200 rounded-md p-3 mb-3">
            <div class="flex items-center justify-between mb-2">
              <button 
                @click="editLinkWithTipTap(dropdown.element.querySelector('.dropdown-trigger'))"
                class="mySecondaryButton text-sm px-3"
              >
                Text bearbeiten
              </button>
              <span class="text-gray-500 text-sm">{{ dropdown.text }}</span>
            </div>
            
            <div class="border-t border-gray-200 mt-2 pt-2">
              <label class="myPrimaryInputLabel mb-2">Menüpunkte</label>
              <ul class="space-y-2 mb-3">
                <li v-for="(item, iIndex) in dropdown.items" :key="iIndex" class="bg-gray-50 p-2 rounded">
                  <div class="flex items-center justify-between mb-2">
                    <button 
                      @click="editLinkWithTipTap(dropdown.element.querySelector('.dropdown-menu').querySelectorAll('a')[iIndex])"
                      class="mySecondaryButton text-xs px-2"
                    >
                      Text bearbeiten
                    </button>
                    <span class="text-gray-500 text-xs">{{ item.text }}</span>
                  </div>
                  
                  <div class="flex items-center gap-2">
                    <input 
                      v-model="dropdown.element.querySelector('.dropdown-menu').querySelectorAll('a')[iIndex].href" 
                      class="myPrimaryInput flex-1" 
                      type="text" 
                      placeholder="URL"
                    />
                    <button 
                      @click="deleteDropdownItem(dIndex, iIndex)"
                      class="mySecondaryErrorButton text-xs"
                    >
                      Löschen
                    </button>
                  </div>
                </li>
              </ul>
            </div>
            
            <button 
              @click="deleteDropdown(dIndex)"
              class="myPrimaryErrorButton w-full mt-2"
            >
              Dropdown löschen
            </button>
          </div>
        </div>
        
        <!-- Header zurücksetzen -->
        <div class="mb-6">
          <button 
            @click="clearHeaderLinks"
            class="myPrimaryErrorButton w-full"
          >
            Header leeren (alle Links/Dropdowns entfernen)
          </button>
        </div>
        
        <!-- Neuen Link hinzufügen -->
        <div class="mb-6">
          <p class="myPrimaryParagraph font-medium py-0 my-4">Link hinzufügen</p>
          <div class="my-3 py-3">
            <label class="myPrimaryInputLabel">Link-Text</label>
            <input 
              v-model="newLink.text" 
              class="myPrimaryInput" 
              type="text" 
              placeholder="Text für den Link"
            />
          </div>
          
          <div class="my-3 py-3">
            <label class="myPrimaryInputLabel">Link-URL</label>
            <input 
              v-model="newLink.url" 
              class="myPrimaryInput" 
              type="text" 
              placeholder="#oder-eine-url"
            />
          </div>
          
          <div class="my-3 py-3 flex items-center">
            <input 
              id="active-link" 
              v-model="newLink.active" 
              type="checkbox" 
              class="myRadio mr-2"
            />
            <label for="active-link" class="myPrimaryInputLabel m-0">Aktiver Link</label>
          </div>
          
          <button 
            @click="addLink"
            class="myPrimaryButton w-full"
          >
            Link hinzufügen
          </button>
        </div>
        
        <!-- Dropdown-Menü hinzufügen -->
        <div class="mb-6">
          <p class="myPrimaryParagraph font-medium py-0 my-4">Dropdown-Menü hinzufügen</p>
          <div class="my-3 py-3">
            <label class="myPrimaryInputLabel">Menü-Titel</label>
            <input 
              v-model="newDropdown.text" 
              class="myPrimaryInput" 
              type="text" 
              placeholder="Titel des Dropdown-Menüs"
            />
          </div>
          
          <!-- Dropdown-Items -->
          <div class="border border-gray-200 rounded-md p-3 mb-4">
            <p class="myPrimaryInputLabel mb-2">Dropdown-Menüpunkte</p>
            
            <!-- Liste vorhandener Items -->
            <ul v-if="newDropdown.items.length > 0" class="mb-3 space-y-2">
              <li v-for="(item, index) in newDropdown.items" :key="index" 
                  class="flex items-center justify-between p-2 bg-gray-50 rounded">
                <div>
                  <span class="font-medium">{{ item.text }}</span>
                  <span class="text-xs text-gray-500 ml-2">{{ item.url }}</span>
                </div>
                <button @click="removeDropdownItem(index)" class="text-red-500 hover:text-red-700">
                  <span class="material-symbols-outlined">delete</span>
                </button>
              </li>
            </ul>
            
            <!-- Neues Item hinzufügen -->
            <div class="space-y-2">
              <input 
                v-model="newDropdownItem.text" 
                class="myPrimaryInput" 
                type="text" 
                placeholder="Text des Menüpunkts"
              />
              <input 
                v-model="newDropdownItem.url" 
                class="myPrimaryInput" 
                type="text" 
                placeholder="#oder-eine-url"
              />
              <button 
                @click="addDropdownItem"
                class="mySecondaryButton w-full"
              >
                Menüpunkt hinzufügen
              </button>
            </div>
          </div>
          
          <button 
            @click="addDropdown"
            class="myPrimaryButton w-full"
            :disabled="newDropdown.items.length === 0"
          >
            Dropdown-Menü hinzufügen
          </button>
        </div>
        
        <!-- Button hinzufügen -->
        <div class="mb-6">
          <p class="myPrimaryParagraph font-medium py-0 my-4">Button hinzufügen</p>
          <div class="my-3 py-3">
            <label class="myPrimaryInputLabel">Button-Text</label>
            <input 
              v-model="newButton.text" 
              class="myPrimaryInput" 
              type="text" 
              placeholder="Text für den Button"
            />
          </div>
          
          <div class="my-3 py-3">
            <label class="myPrimaryInputLabel">Button-URL (optional)</label>
            <input 
              v-model="newButton.url" 
              class="myPrimaryInput" 
              type="text" 
              placeholder="#oder-eine-url"
            />
          </div>
          
          <div class="my-3 py-3">
            <label class="myPrimaryInputLabel">Button-Typ</label>
            <select v-model="newButton.type" class="myPrimarySelect">
              <option value="primary">Primär (farbig)</option>
              <option value="secondary">Sekundär (umrandet)</option>
            </select>
          </div>
          
          <button 
            @click="addButton"
            class="myPrimaryButton w-full"
          >
            Button hinzufügen
          </button>
        </div>
      </template>
    </template>
  </EditorAccordion>
  
  <!-- Media Library Modal -->
  <MediaLibraryModal
    v-if="showMediaLibraryModal"
    :open="showMediaLibraryModal"
    title="Bild auswählen"
    description="Wähle ein Bild für dein Logo aus"
    firstButtonText="Abbrechen"
    secondButtonText="Auswählen"
    @firstMediaButtonFunction="closeMediaLibrary"
    @secondMediaButtonFunction="handleMediaSelected"
  />
</template>