<script setup>
import { Editor, useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { computed, onBeforeMount, onMounted, ref, watch, nextTick } from 'vue';
import PageBuilder from '@/composables/PageBuilder';
import Link from '@tiptap/extension-link';
import Underline from '@tiptap/extension-underline';
import DynamicModal from '@/Components/Modals/DynamicModal.vue';
import { usePageBuilderStateStore } from '@/stores/page-builder-state';
import { useMediaLibraryStore } from '@/stores/media-library';
import tailwindColors from '@/utils/builder/tailwaind-colors'; // Importiere die tailwind-colors

const mediaLibraryStore = useMediaLibraryStore();
const pageBuilderStateStore = usePageBuilderStateStore();
const showModalUrl = ref(false);

// use dynamic model
const typeModal = ref('');
const gridColumnModal = ref(Number(1));
const titleModal = ref('');
const descriptionModal = ref('');
const firstButtonModal = ref('');
const secondButtonModal = ref(null);
const thirdButtonModal = ref(null);
// set dynamic modal handle functions
const firstModalButtonFunction = ref(null);
const secondModalButtonFunction = ref(null);
const thirdModalButtonFunction = ref(null);

const pageBuilder = new PageBuilder(pageBuilderStateStore, mediaLibraryStore);

const getElement = computed(() => {
  return pageBuilderStateStore.getElement;
});

// Neu hinzugefügt: Holen des ausgewählten Text-Elements
const getSelectedTextElement = computed(() => {
  return pageBuilderStateStore.getSelectedTextElement;
});

const textContentVueModel = ref('');

// Initialer Inhalt für den Editor
const initialContent = ref('');

// Status für Link-Unterstreichung
const linkUnderlineEnabled = ref(true);

// Bereite den Inhalt für den Editor vor - diese Funktion wird vor der Editor-Initialisierung aufgerufen
const prepareEditorContent = () => {
  if (getSelectedTextElement.value) {
    // Wenn ein individuelles Text-Element ausgewählt ist, dessen Inhalt verwenden
    initialContent.value = getSelectedTextElement.value.innerHTML || '';
  } else if (getElement.value) {
    // Fallback zum gesamten Element
    initialContent.value = getElement.value.innerHTML || '';
  } else {
    // Leerer Fallback
    initialContent.value = '';
  }
  return initialContent.value;
};

// Prüfe, ob Links im Inhalt unterstrichen sind
const checkLinkUnderlineStatus = () => {
  const element = getSelectedTextElement.value || getElement.value;
  if (!element) return;
  
  // Suche nach Links im Element
  const links = element.querySelectorAll('a');
  if (links.length > 0) {
    // Prüfe, ob der erste Link unterstrichen ist
    const firstLink = links[0];
    const hasUnderlineClass = firstLink.classList.contains('underline');
    const hasTextDecorationStyle = window.getComputedStyle(firstLink).textDecoration.includes('underline');
    linkUnderlineEnabled.value = hasUnderlineClass || hasTextDecorationStyle;
  }
};

// Rufe prepareEditorContent auf, um den initialContent zu setzen
prepareEditorContent();

const textContent = computed(() => {
  if (editor.value) {
    return editor.value.getHTML();
  }
  return '';
});

const getElementtextContentLength = ref(0);

watch(getElement, (newVal) => {
  const tempContainer = document.createElement('div');

  if (newVal) {
    tempContainer.innerHTML = newVal;
    const textContent = tempContainer.textContent;
    getElementtextContentLength.value = textContent.length;
  }
});

// Erstelle einen benutzerdefinierten Link-Extension, bei dem wir die Klassen dynamisch ändern können
const linkExtension = Link.configure({
  openOnClick: false,
  HTMLAttributes: {
    // Die Klasse wird beim Erstellen eines Links gesetzt
    class: linkUnderlineEnabled.value ? 'text-indigo-600 underline cursor-pointer' : 'text-indigo-600 no-underline cursor-pointer',
  },
  // Sorgt dafür, dass das Link-Element vollständig erhalten bleibt
  parseHTML() {
    return [
      {
        tag: 'a[href]',
        getAttrs: el => ({
          href: el.getAttribute('href'),
          target: el.getAttribute('target'),
          rel: el.getAttribute('rel'),
          class: el.getAttribute('class')
        })
      }
    ]
  }
});

// Verbesserte Editor-Initialisierung mit dem vorbereiteten Inhalt
const editor = useEditor({
  content: initialContent.value,
  extensions: [
    StarterKit,
    linkExtension,
    Underline, // Füge die Underline Extension hinzu
  ],
  editorProps: {
    attributes: {
      class: 'prose-sm sm:prose-sm lg:prose-sm focus:outline-none',
    },
  },
});

// Funktion zum Umschalten der Link-Unterstreichung
const toggleLinkUnderline = () => {
  if (!editor.value) return;
  
  linkUnderlineEnabled.value = !linkUnderlineEnabled.value;
  
  // Alle Links im aktuellen Editor-Inhalt finden
  const editorElement = document.querySelector('#page-builder-editor');
  if (!editorElement) return;
  
  const links = editorElement.querySelectorAll('a');
  
  // Unterstreichung für alle Links ein- oder ausschalten
  links.forEach(link => {
    if (linkUnderlineEnabled.value) {
      link.classList.add('underline');
      link.classList.remove('no-underline');
    } else {
      link.classList.remove('underline');
      link.classList.add('no-underline');
    }
  });
  
  // Aktualisiere die HTMLAttributes für zukünftige Links
  editor.value.extensionManager.extensions.forEach(extension => {
    if (extension.name === 'link') {
      extension.options.HTMLAttributes.class = linkUnderlineEnabled.value 
        ? 'text-indigo-600 underline cursor-pointer' 
        : 'text-indigo-600 no-underline cursor-pointer';
    }
  });
};

// Neue Methode, die den Inhalt aus dem richtigen Element holt
const getContentFromActiveElement = () => {
  if (getSelectedTextElement.value) {
    // Wenn ein individuelles Text-Element ausgewählt ist, dessen Inhalt verwenden
    return getSelectedTextElement.value.innerHTML;
  } else if (getElement.value) {
    // Fallback zum gesamten Element
    return getElement.value.innerHTML;
  }
  return '';
};

// Setze die EditorContent initial und zukünftig ohne p-Tags für Link-Elemente
const TipTapSetContent = function () {
  if (editor.value) {
    const contentToEdit = getContentFromActiveElement();
    if (contentToEdit) {
      // Wenn wir ein a-Element bearbeiten, benötigen wir eine spezielle Behandlung
      const originalElement = getSelectedTextElement.value;
      if (originalElement && originalElement.tagName.toLowerCase() === 'a') {
        // Direkte Bearbeitung des Link-Textes ohne umschließende Tags
        editor.value.commands.setContent(contentToEdit, false);
        
        // Stelle sicher, dass der Link im Editor erkannt wird
        if (originalElement.hasAttribute('href')) {
          const href = originalElement.getAttribute('href');
          // Markiere den gesamten Text und mache ihn zum Link
          editor.value.chain().selectAll().setLink({ href }).run();
        }
      } else {
        // Normale Inhaltsbearbeitung
        editor.value.commands.setContent(contentToEdit);
      }
    }
    
    // Nachdem der Inhalt geladen wurde, prüfe den Unterstreichungsstatus der Links
    nextTick(() => {
      checkLinkUnderlineStatus();
    });
  }
};

// TipTap mit Inhalt füllen, wenn sich das ausgewählte Element ändert
watch([getElement, getSelectedTextElement], () => {
  // Stellen sicher, dass der Editor initialisiert ist, bevor wir versuchen, den Inhalt zu setzen
  if (editor.value) {
    nextTick(() => {
      TipTapSetContent();
    });
  }
});

// Manage URL
const urlEnteret = ref('');
const newUpdatedExistingURL = ref('');
const urlError = ref(null);

watch(urlEnteret, (newVal) => {
  newUpdatedExistingURL.value = newVal;
});

const handleURL = function () {
  if (!editor.value) return;
  
  urlEnteret.value = editor.value.getAttributes('link').href;

  showModalUrl.value = true;
  typeModal.value = 'success';
  gridColumnModal.value = 2;
  titleModal.value = 'Enter URL';
  descriptionModal.value = null;
  firstButtonModal.value = 'Close';
  secondButtonModal.value = urlEnteret.value ? 'Remove url' : null;
  thirdButtonModal.value = 'Save';

  // handle click
  firstModalButtonFunction.value = function () {
    showModalUrl.value = false;
    urlError.value = null;
  };

  // handle click
  secondModalButtonFunction.value = function () {
    if (editor.value) {
      editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    }
    showModalUrl.value = false;
  };

  // handle click
  thirdModalButtonFunction.value = function () {
    const isNotValidated = validateURL();
    if (isNotValidated) {
      return;
    }
    if (!isNotValidated && editor.value) {
      setEnteretURL();
    }
    showModalUrl.value = false;
  };
  // end modal
};

//
//
const validateURL = function () {
  // initial value
  urlError.value = null;

  // url validation
  const urlRegex = /^https?:\/\//;
  const isValidURL = ref(true);
  isValidURL.value = urlRegex.test(newUpdatedExistingURL.value);

  // cancelled
  if (isValidURL.value === false) {
    urlError.value =
      "The provided URL is invalid. Please ensure that it begins with 'https://' for proper formatting and security.";
    return true;
  }

  return false;
};

const setEnteretURL = function () {
  // update link
  if (editor.value) {
    editor.value
      .chain()
      .focus()
      .extendMarkRange('link')
      .setLink({ 
        href: newUpdatedExistingURL.value,
        class: linkUnderlineEnabled.value ? 'text-indigo-600 underline cursor-pointer' : 'text-indigo-600 no-underline cursor-pointer'
      })
      .run();
  }
};

onBeforeMount(() => {
  // Verhindere das Zerstören eines nicht-initialisierten Editors
  if (editor.value) {
    editor.value?.destroy();
  }
});

onMounted(() => {
  // Stellen sicher, dass der Editor initialisiert ist, bevor wir Operationen darauf ausführen
  if (editor.value) {
    nextTick(() => {
      TipTapSetContent();
    });
  }
});

// Angepasste handleTextInput Funktion
const handleTextSave = async () => {
  if (!editor.value) return;
  
  let newContent = editor.value.getHTML();
  
  try {
    if (getSelectedTextElement.value) {
      const element = getSelectedTextElement.value;
      
      // Speichere den ursprünglichen Tag-Namen
      const originalTagName = element.tagName.toLowerCase();

      // Spezielle Behandlung für <a>-Tags
      if (originalTagName === 'a') {
        // Für Links nur den Inhalt ändern, nicht die Struktur
        const tempContainer = document.createElement('div');
        tempContainer.innerHTML = newContent;
        
        // Wenn der Editor einen Link zurückgibt, diesen verwenden
        const editorLink = tempContainer.querySelector('a');
        if (editorLink) {
          // Kopiere nur den Innentext und die Inline-Formatierungen, lasse Struktur unangetastet
          element.innerHTML = editorLink.innerHTML;
          
          // Falls im Editor neue href/target/rel gesetzt wurden, diese übernehmen
          if (editorLink.hasAttribute('href')) {
            element.setAttribute('href', editorLink.getAttribute('href'));
          }
          if (editorLink.hasAttribute('target')) {
            element.setAttribute('target', editorLink.getAttribute('target'));
          }
          if (editorLink.hasAttribute('rel')) {
            element.setAttribute('rel', editorLink.getAttribute('rel'));
          }
          
          // CSS-Klassen vom Editor-Link übernehmen (wichtig für die Unterstreichung)
          if (editorLink.hasAttribute('class')) {
            // Erhalte bestehende Klassen, die beibehalten werden sollen
            const existingClasses = element.className.split(' ').filter(cls => 
              !['underline', 'no-underline'].includes(cls)
            );
            
            // Füge die neuen Klassen vom Editor-Link hinzu
            const editorClasses = editorLink.className.split(' ');
            
            // Kombiniere und setze die Klassen
            element.className = [...existingClasses, ...editorClasses].join(' ');
          }
          
          // Stelle sicher, dass die Unterstreichungs-Einstellung korrekt angewendet wird
          if (!linkUnderlineEnabled.value) {
            element.classList.remove('underline');
            element.classList.add('no-underline');
          } else {
            element.classList.add('underline');
            element.classList.remove('no-underline');
          }
        } else {
          // Fallback: Wenn kein Link gefunden wurde, nehme den gesamten Inhalt
          // Entferne p-Tags falls vorhanden
          const content = tempContainer.innerHTML;
          const pStart = content.indexOf('<p>');
          const pEnd = content.lastIndexOf('</p>');
          
          if (pStart === 0 && pEnd > 0) {
            // Extrahiere den Inhalt zwischen p-Tags
            element.innerHTML = content.substring(3, pEnd);
          } else {
            element.innerHTML = content;
          }
        }
        
        // Schließe den Modal-Dialog
        pageBuilderStateStore.setShowModalTipTap(false);
        return;
      }
      
      // Bereinige problematische verschachtelte Tags
      // Erhalte den neuen Inhalt als DOM-Objekt zur einfacheren Manipulation
      const tempContainer = document.createElement('div');
      tempContainer.innerHTML = newContent;
      
      // Spezielle Prüfung für p-Tags, wenn das Originalelement selbst ein p-Tag ist
      if (originalTagName === 'p') {
        // Prüfe auf verschachtelte p-Tags
        const nestedParagraphs = tempContainer.querySelectorAll('p p');
        
        if (nestedParagraphs.length > 0 || tempContainer.querySelector(':scope > p')) {
          // Wenn verschachtelte p-Tags gefunden wurden, extrahiere nur den Text- und Inline-Element-Inhalt
          let contentToKeep = '';
          
          // Wenn der Container ein einzelnes p-Tag enthält, nimm dessen Inhalt
          const firstLevelP = tempContainer.querySelector(':scope > p');
          if (firstLevelP) {
            contentToKeep = firstLevelP.innerHTML;
          } else {
            // Andernfalls sammle allen Inhalt
            contentToKeep = tempContainer.innerHTML;
          }
          
          // Ersetze den gesamten Inhalt mit dem bereinigten Inhalt
          tempContainer.innerHTML = contentToKeep;
        }
      }
      // Entferne unerwünschte p-Tags, wenn das Original-Element selbst ein Block-Element ist
      else if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span'].includes(originalTagName)) {
        // Suche nach p-Tags im ersten Level
        const paragraphs = tempContainer.querySelectorAll(':scope > p');
        
        // Wenn p-Tags gefunden wurden und sie der einzige Inhalt sind
        if (paragraphs.length > 0) {
          // Extrahiere den Inhalt aus den p-Tags und füge ihn direkt ein
          let extractedContent = '';
          paragraphs.forEach(p => {
            extractedContent += p.innerHTML;
          });
          
          // Setze den bereinigten Inhalt
          tempContainer.innerHTML = extractedContent;
        }
      }
      
      // Liste der strukturellen Tags, die problematisch sind, wenn sie verschachtelt werden
      const structuralTags = ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'section', 'article'];
      
      // Prüfe, ob unerwünschte verschachtelte Tags vorhanden sind
      const hasNestedStructuralTags = structuralTags.some(tag => {
        // Wenn das originale Element z.B. ein h2 ist, darf es kein p enthalten
        if (tag !== originalTagName) {
          return tempContainer.querySelector(`${originalTagName} ${tag}`) !== null;
        }
        return false;
      });
      
      if (hasNestedStructuralTags) {
        // Entferne unerwünschte verschachtelte Tags, ABER behalte deren Inhalt
        structuralTags.forEach(tag => {
          // Überspringe, wenn es der Tag des Originalelements ist
          if (tag === originalTagName) return;
          
          const nestedElements = tempContainer.querySelectorAll(tag);
          nestedElements.forEach(nestedEl => {
            // Ersetze das problematische Element durch seinen Inhalt
            const fragment = document.createDocumentFragment();
            
            // Füge alle Kinder des verschachtelten Elements zum Fragment hinzu (erhält Links)
            while (nestedEl.firstChild) {
              fragment.appendChild(nestedEl.firstChild);
            }
            
            // Ersetze das verschachtelte Element durch das Fragment
            if (nestedEl.parentNode) {
              nestedEl.parentNode.replaceChild(fragment, nestedEl);
            }
          });
        });
      }
      
      // Aktualisiere den Inhalt mit der bereinigten Version
      newContent = tempContainer.innerHTML;
      
      // Aktualisiere das Element mit dem bereinigten Inhalt
      element.innerHTML = newContent;
      
      // Schließe den Modal-Dialog
      pageBuilderStateStore.setShowModalTipTap(false);
    } else if (pageBuilder.selectedElementIsValidText()) {
      // Fallback zur alten Methode für das gesamte Element
      
      // Bereinige auch hier die p-Tags
      const tempContainer = document.createElement('div');
      tempContainer.innerHTML = newContent;
      
      // Entferne p-Tags auf der ersten Ebene, falls vorhanden
      const paragraphs = tempContainer.querySelectorAll(':scope > p');
      if (paragraphs.length > 0) {
        let extractedContent = '';
        paragraphs.forEach(p => {
          extractedContent += p.innerHTML;
        });
        newContent = extractedContent;
      }
      
      pageBuilder.handleTextInput(newContent);
      pageBuilderStateStore.setShowModalTipTap(false);
    }
  } catch (error) {
    console.error('Fehler beim Speichern des Texts:', error);
  }
};

// Überwache das Schließen des Modals, um die Text-Auswahl zurückzusetzen
watch(() => pageBuilderStateStore.getShowModalTipTap, (newValue, oldValue) => {
  if (oldValue === true && newValue === false) {
    // Modal wurde geschlossen, setze die Textauswahl zurück
    pageBuilder.clearTextElementSelection();
  }
});

// Zeige das Text-Farben-Dropdown an oder verberge es
const showTextColorDropdown = ref(false);

// Halte die aktuell ausgewählte Textfarbe
const selectedTextColor = ref('none');

// Liste der verfügbaren Textfarben
const textColorOptions = computed(() => {
  // Begrenzte Auswahl an Textfarben für ein übersichtliches Dropdown
  return [
    { name: 'Schwarz', value: 'text-black' },
    { name: 'Weiß', value: 'text-white' },
    { name: 'Grau 400', value: 'text-gray-400' },
    { name: 'Grau 500', value: 'text-gray-500' },
    { name: 'Grau 600', value: 'text-gray-600' },
    { name: 'Grau 700', value: 'text-gray-700' },
    { name: 'Grau 800', value: 'text-gray-800' },
    { name: 'Rot 500', value: 'text-red-500' },
    { name: 'Rot 600', value: 'text-red-600' },
    { name: 'Rot 700', value: 'text-red-700' },
    { name: 'Orange 500', value: 'text-orange-500' },
    { name: 'Orange 600', value: 'text-orange-600' },
    { name: 'Gelb 500', value: 'text-yellow-500' },
    { name: 'Gelb 600', value: 'text-yellow-600' },
    { name: 'Grün 500', value: 'text-green-500' },
    { name: 'Grün 600', value: 'text-green-600' },
    { name: 'Blau 500', value: 'text-blue-500' },
    { name: 'Blau 600', value: 'text-blue-600' },
    { name: 'Indigo 500', value: 'text-indigo-500' },
    { name: 'Indigo 600', value: 'text-indigo-600' },
    { name: 'Lila 500', value: 'text-purple-500' },
    { name: 'Lila 600', value: 'text-purple-600' },
    { name: 'Pink 500', value: 'text-pink-500' },
    { name: 'Pink 600', value: 'text-pink-600' },
    { name: 'Keine Farbe', value: 'none' }
  ];
});

// Bestimme die aktuelle Textfarbe aus dem Element, wenn vorhanden
const getCurrentTextColorFromElement = () => {
  if (getSelectedTextElement.value) {
    const element = getSelectedTextElement.value;
    // Suche nach einer Textfarbklasse im Element
    for (const colorClass of tailwindColors.textColorVariables) {
      if (colorClass !== 'none' && element.classList.contains(colorClass)) {
        selectedTextColor.value = colorClass;
        return;
      }
    }
  }
  selectedTextColor.value = 'none';
};

// Wende eine neue Textfarbe auf das ausgewählte Element an
const applyTextColor = (newColor) => {
  if (!getSelectedTextElement.value) return;
  
  const element = getSelectedTextElement.value;
  
  // Entferne bestehende Textfarben
  tailwindColors.textColorVariables.forEach(colorClass => {
    if (colorClass !== 'none' && element.classList.contains(colorClass)) {
      element.classList.remove(colorClass);
    }
  });
  
  // Füge neue Textfarbe hinzu, außer wenn "none" ausgewählt wurde
  if (newColor !== 'none') {
    element.classList.add(newColor);
  }
  
  // Aktualisiere die ausgewählte Textfarbe
  selectedTextColor.value = newColor;
  
  // Verberge das Dropdown nach der Auswahl
  showTextColorDropdown.value = false;
};

// Initialisiere die aktuelle Textfarbe, wenn der Editor geöffnet wird
watch(() => pageBuilderStateStore.getShowModalTipTap, (newValue) => {
  if (newValue === true) {
    // Wenn der Modal geöffnet wird, bestimme die aktuelle Textfarbe
    nextTick(() => {
      getCurrentTextColorFromElement();
    });
  }
});
</script>
<template>
  <DynamicModal
    :show="showModalUrl"
    :type="typeModal"
    :gridColumnAmount="gridColumnModal"
    :title="titleModal"
    :description="descriptionModal"
    :firstButtonText="firstButtonModal"
    :secondButtonText="secondButtonModal"
    :thirdButtonText="thirdButtonModal"
    @firstModalButtonFunction="firstModalButtonFunction"
    @secondModalButtonFunction="secondModalButtonFunction"
    @thirdModalButtonFunction="thirdModalButtonFunction"
  >
    <header></header>
    <main>
      <div class="myInputGroup">
        <label
          class="myPrimaryInputLabel"
          for="roles"
          ><span>Enter URL</span></label
        ><input
          v-model="urlEnteret"
          class="myPrimaryInput mt-1"
          type="url"
          placeholder="url"
        />
        <div
          v-if="urlError"
          class="min-h-[2.5rem] flex items-center justify-start"
        >
          <p class="myPrimaryInputError mt-2 mb-0 py-0 self-start">
            {{ urlError }}
          </p>
        </div>
      </div>
    </main>
  </DynamicModal>

  <div class="blockease-linear duration-200 block ease-linear">
    <div v-if="(pageBuilder.selectedElementIsValidText() || getSelectedTextElement) && editor">
      <div class="relative rounded-lg">
        <div
          class="flex justify-between myPrimaryGap items-center divide-x divide-gray-200 py-4 px-4 overflow-x-auto border-b border-gray-20"
        >
          <div class="flex items-center 0 divide-x divide-gray-200">
            <div class="px-2 flex items-center justify-start gap-2">
              <button
                @click="editor.chain().focus().setHardBreak().run()"
                type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
              >
                <span class="material-symbols-outlined"> keyboard_return </span>
                <span>Line break</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button
                @click="editor.chain().focus().toggleBold().run()"
                type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': editor.isActive('bold'),
                }"
              >
                <span class="material-symbols-outlined"> format_bold </span>
                <span>Bold</span>
              </button>
            </div>
            
            <!-- Neue Schaltfläche für allgemeine Unterstreichung -->
            <div class="px-2 flex items-center justify-start gap-2">
              <button
                @click="editor.chain().focus().toggleUnderline().run()"
                type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': editor.isActive('underline'),
                }"
              >
                <span class="material-symbols-outlined"> format_underlined </span>
                <span>Unterstreichen</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button
                @click="handleURL"
                type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': editor.isActive('link'),
                }"
              >
                <span class="material-symbols-outlined"> link </span>
                <span>Link</span>
              </button>
            </div>
            
            <!-- Link-Unterstreichungs-Button nur anzeigen wenn ein Link aktiv ist -->
            <div class="px-2 flex items-center justify-start gap-2" v-if="editor.isActive('link')">
              <button
                @click="toggleLinkUnderline"
                type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': linkUnderlineEnabled,
                }"
              >
                <span class="material-symbols-outlined"> format_underlined </span>
                <span>Link unterstreichen</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button
                @click="
                  editor.chain().focus().toggleHeading({ level: 2 }).run()
                "
                type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': editor.isActive(
                    'heading',
                    {
                      level: 2,
                    }
                  ),
                }"
              >
                <span class="material-symbols-outlined"> titlecase </span>
                <span>Header 2</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button
                @click="
                  editor.chain().focus().toggleHeading({ level: 3 }).run()
                "
                type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': editor.isActive(
                    'heading',
                    {
                      level: 3,
                    }
                  ),
                }"
              >
                <span class="material-symbols-outlined"> titlecase </span>
                <span>Header 3</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button
                @click="editor.chain().focus().toggleBulletList().run()"
                type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white':
                    editor.isActive('bulletList'),
                }"
              >
                <span class="material-symbols-outlined"> list </span>
                <span>List</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button
                @click="showTextColorDropdown = !showTextColorDropdown"
                type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
              >
                <span class="material-symbols-outlined"> palette </span>
                <span>Textfarbe</span>
              </button>
            </div>
          </div>
          <div>
            <div>
              <div class="px-2 flex items-center justify-start gap-2">
                <button
                  @click="handleTextSave"
                  type="button"
                  class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2"
                >
                  Speichern und schließen
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="showTextColorDropdown" class="absolute z-10 bg-white border border-gray-300 rounded-lg shadow-lg mt-2">
          <ul class="py-2">
            <li
              v-for="option in textColorOptions"
              :key="option.value"
              @click="applyTextColor(option.value)"
              class="px-4 py-2 cursor-pointer hover:bg-gray-100"
              :class="{ 'font-bold': selectedTextColor === option.value }"
            >
              {{ option.name }}
            </li>
          </ul>
        </div>

        <editor-content
          v-if="editor"
          id="page-builder-editor"
          :editor="editor"
          class="px-4 pt-6 pb-12 bg-white rounded-lg lg:min-h-[20rem] lg:max-h-[30rem] md:min-h-[20rem] md:max-h-[20rem] min-h-[20rem] max-h-[20rem] overflow-y-auto"
        />
      </div>
    </div>
  </div>
</template>
