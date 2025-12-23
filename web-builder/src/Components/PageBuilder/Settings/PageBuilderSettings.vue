<script setup>
import SlideOverRightParent from '@/Components/PageBuilder/Slidebars/SlideOverRightParent.vue';
import AdvancedPageBuilderSettings from '@/Components/PageBuilder/Settings/AdvancedPageBuilderSettings.vue';
import { ref, computed } from 'vue';
import fullHTMLContent from '@/utils/builder/html-doc-declaration-with-components';
import { usePageBuilderStateStore } from '@/stores/page-builder-state';

const pageBuilderStateStore = usePageBuilderStateStore();

const showAdvancedSettingsSlideOverRight = ref(false);
const titleSettingsSlideOverRight = ref('');
const downloadedComponents = ref(null);

// handle slideover window
const handleAdvancedSettingsSlideOver = function () {
  titleSettingsSlideOverRight.value = 'Advanced Page Builder Settings';
  showAdvancedSettingsSlideOverRight.value = true;
};

// handle slideover window
const settingsAdvancedSlideOverButton = function () {
  showAdvancedSettingsSlideOverRight.value = false;
};

// Multi-Page-Support für HTML-Download
const getPages = computed(() => {
  return pageBuilderStateStore.pages;
});

const currentPageId = computed(() => {
  return pageBuilderStateStore.currentPageId;
});

const getComponents = computed(() => {
  return pageBuilderStateStore.getComponents;
});

// generate HTML
const generateHTML = function (filename, HTML) {
  const element = document.createElement('a');
  element.setAttribute(
    'href',
    'data:text/html;charset=utf-8,' + encodeURIComponent(fullHTMLContent(HTML))
  );
  element.setAttribute('download', filename);

  element.style.display = 'none';
  document.body.appendChild(element);

  element.click();

  document.body.removeChild(element);
};

// Funktion zum Erstellen von sauberem HTML für den Export
const cleanHtmlForExport = function(html) {
  // Erstelle ein temporäres Container-Element
  const tempContainer = document.createElement('div');
  tempContainer.innerHTML = html;
  
  // 1. Links wiederherstellen und deren Editor-Attribute entfernen
  tempContainer.querySelectorAll('a').forEach(link => {
    // Original-URL wiederherstellen
    if (link.hasAttribute('data-original-href')) {
      link.href = link.getAttribute('data-original-href');
      link.removeAttribute('data-original-href');
    }
    
    // Original-Target wiederherstellen
    if (link.hasAttribute('data-original-target')) {
      link.target = link.getAttribute('data-original-target');
      link.removeAttribute('data-original-target');
    }
  });
  
  // 2. Entferne alle Editor-spezifischen Attribute von allen Elementen
  // Statt [data-*] (ungültiger Selektor) verwenden wir alle Elemente und prüfen ihre Attribute
  const allElements = tempContainer.querySelectorAll('*');
  allElements.forEach(element => {
    // Liste der zu entfernenden Attribute
    const attributesToRemove = [
      'data-componentid',
      'data-editable-text',
      'data-image',
      'data-header-logo',
      'data-header-links',
      'data-header-actions',
      'data-header-mobile'
    ];
    
    // Entferne die spezifischen Editor-Attribute
    attributesToRemove.forEach(attr => {
      if (element.hasAttribute(attr)) {
        element.removeAttribute(attr);
      }
    });
    
    // Entferne auch 'hovered' und 'selected' Attribute
    if (element.hasAttribute('hovered')) {
      element.removeAttribute('hovered');
    }
    if (element.hasAttribute('selected')) {
      element.removeAttribute('selected');
    }
    
    // Entferne ID "page-builder-editor-editable-area", wenn vorhanden
    if (element.id === 'page-builder-editor-editable-area') {
      element.removeAttribute('id');
    }
  });
  
  // Gib den bereinigten HTML-Content zurück
  return tempContainer.innerHTML;
};

// Funktion zum Exportieren einer einzelnen Seite
const exportSinglePage = function(page) {
  const components = page.components;
  const cleanedHtml = components.map(component => {
    return cleanHtmlForExport(component.html_code);
  }).join('');
  
  return cleanedHtml;
};

// Funktion zum Erstellen einer ZIP-Datei mit allen Seiten
const createMultiPageZip = async function() {
  // Importiere JSZip-Bibliothek dynamisch
  try {
    // Prüfen, ob JSZip bereits als globale Variable verfügbar ist
    if (typeof JSZip === 'undefined') {
      // Füge das JSZip-Skript hinzu, wenn es nicht vorhanden ist
      const script = document.createElement('script');
      script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js';
      document.head.appendChild(script);
      
      // Warte, bis das Skript geladen ist
      await new Promise(resolve => {
        script.onload = resolve;
      });
    }
    
    // Erstelle eine neue ZIP-Datei
    const zip = new JSZip();
    
    // Füge jede Seite als separate HTML-Datei hinzu, wobei die erste Seite als index.html
    getPages.value.forEach((page, index) => {
      const pageHtml = exportSinglePage(page);
      
      // Für die erste Seite immer index.html verwenden, für alle anderen Seiten den Slug oder die ID
      const fileName = index === 0 ? "index.html" : `${page.slug || page.id}.html`;
      zip.file(fileName, fullHTMLContent(pageHtml));
    });
    
    // Erstelle eine sitemap.html mit Links zu allen Seiten
    const sitemapContent = `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seitenübersicht</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
    h1 { color: #333; }
    ul { list-style-type: none; padding: 0; }
    li { margin: 10px 0; }
    a { color: #0066cc; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <h1>Seitenübersicht</h1>
  <ul>
    ${getPages.value.map((page, index) => {
      const fileName = index === 0 ? "index.html" : `${page.slug || page.id}.html`;
      return `<li><a href="${fileName}">${page.name}</a></li>`;
    }).join('')}
  </ul>
</body>
</html>`;
    
    zip.file("sitemap.html", sitemapContent);
    
    // Generiere die ZIP-Datei und biete sie zum Download an
    const content = await zip.generateAsync({type: "blob"});
    
    // Erstelle einen Download-Link und klicke ihn automatisch
    const element = document.createElement('a');
    element.href = URL.createObjectURL(content);
    element.download = "website.zip";
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
    
  } catch (error) {
    console.error("Fehler beim Erstellen der ZIP-Datei:", error);
    alert("Fehler beim Erstellen der ZIP-Datei. Details sind in der Konsole verfügbar.");
  }
};

// handle download HTML - verbessert mit Multi-Page-Support
const handleDownloadHTML = function () {
  // Prüfe, ob mehrere Seiten existieren
  if (getPages.value.length > 1) {
    // Bei mehreren Seiten als ZIP exportieren
    createMultiPageZip();
  } else {
    // Bei nur einer Seite als index.html exportieren
    const currentPage = getPages.value.find(page => page.id === currentPageId.value) || getPages.value[0];
    const components = currentPage.components;
    
    // Bereinige den HTML-Code jeder Komponente
    downloadedComponents.value = components.map((component) => {
      const componentHtml = component.html_code;
      const cleanedHtml = cleanHtmlForExport(componentHtml);
      return cleanedHtml;
    });

    generateHTML('index.html', downloadedComponents.value.join(''));
  }
};
</script>

<template>
  <SlideOverRightParent
    :open="showAdvancedSettingsSlideOverRight"
    :title="titleSettingsSlideOverRight"
    @slideOverButton="settingsAdvancedSlideOverButton"
  >
    <AdvancedPageBuilderSettings></AdvancedPageBuilderSettings>
  </SlideOverRightParent>
  <!-- Advanced Settings - start -->
  <div class="mt-4 mb-4 py-8 border-b border-myPrimbryLightGrayColor">
    <div class="flex items-left flex-col gap-1">
      <h3 class="myQuaternaryHeader">Advanced Settings</h3>
      <p class="myPrimaryParagraph text-xs">
        Manage advanced settings here. Like an overview of Selected Element,
        Component, and Components in real-time.
      </p>
    </div>
    <div class="mt-4">
      <button
        @click="handleAdvancedSettingsSlideOver"
        type="button"
        class="myPrimaryButton text-xs"
      >
        Advanced Settings
      </button>
    </div>
  </div>
  <!-- Advanced Settings - end -->
  <!-- Download Layout HTML - start -->
  <div class="mt-4 mb-4 py-8 border-b border-myPrimbryLightGrayColor">
    <div class="flex items-left flex-col gap-1">
      <h3 class="myQuaternaryHeader">Download Page as HTML</h3>
      <p class="myPrimaryParagraph text-xs">Download current page layout.</p>
    </div>
    <div class="mt-4">
      <button
        @click="handleDownloadHTML"
        type="button"
        class="myPrimaryButton text-xs"
      >
        Download HTML file
      </button>
    </div>
  </div>
  <!-- Download Layout HTML - end -->
</template>
