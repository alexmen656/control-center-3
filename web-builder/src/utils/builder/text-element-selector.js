/**
 * Text Element Selector
 * Ermöglicht die feine Auswahl einzelner Text-Elemente innerhalb eines Blocks zur gezielten Bearbeitung
 */

/**
 * Identifiziert bearbeitbare Text-Elemente in einem Container und fügt ihnen spezielle Attribute hinzu
 * @param {HTMLElement} container - Container-Element für die Suche
 */
export function markEditableTextElements(container) {
  if (!container) return;
  
  // Arten von Text-Elementen, die wir separat editierbar machen wollen
  const textElementSelectors = 'h1, h2, h3, h4, h5, h6, p, span, li, a, strong, em';
  
  // Alle Text-Elemente finden
  const textElements = container.querySelectorAll(textElementSelectors);
  
  // Jedem Element ein besonderes Attribut geben
  textElements.forEach((element, index) => {
    // Nur direkt bearbeitbare Elemente markieren (keine verschachtelten)
    if (!elementHasEditableParent(element, textElementSelectors)) {
      element.setAttribute('data-editable-text', `text-${Date.now()}-${index}`);
      element.classList.add('individually-editable');
    }
  });
}

/**
 * Prüft, ob das Element bereits einen bearbeitbaren Elternteil hat
 * @param {HTMLElement} element - Zu prüfendes Element
 * @param {string} selectors - Selektoren für bearbeitbare Elemente
 */
function elementHasEditableParent(element, selectors) {
  let parent = element.parentElement;
  while (parent && parent.tagName !== 'SECTION') {
    if (parent.matches(selectors)) {
      return true;
    }
    parent = parent.parentElement;
  }
  return false;
}

/**
 * Erstellt eine vollständige Kopie des ursprünglichen Element-Zustands
 * @param {HTMLElement} element - Das zu kopierende Element
 * @returns {Object} - Objekt mit allen relevanten Informationen zum Element
 */
function captureElementState(element) {
  if (!element) return null;
  
  // Erstelle ein Objekt, das alle wichtigen Aspekte des Elements speichert
  return {
    tagName: element.tagName,
    outerHTML: element.outerHTML,
    innerHTML: element.innerHTML,
    textContent: element.textContent,
    className: element.className,
    classList: Array.from(element.classList),
    attributes: Array.from(element.attributes).map(attr => ({
      name: attr.name,
      value: attr.value
    }))
  };
}

/**
 * Wählt ein einzelnes Text-Element zur Bearbeitung aus
 * @param {HTMLElement} element - Das zu bearbeitende Element
 * @param {Object} pageBuilderStateStore - Store für den PageBuilder-Status
 */
export function selectTextElementForEditing(element, pageBuilderStateStore) {
  // Entferne bestehende Auswahl
  document.querySelectorAll('[text-selected]').forEach(el => {
    el.removeAttribute('text-selected');
  });
  
  // Markiere dieses Element als ausgewählt
  element.setAttribute('text-selected', '');
  
  // Setze das Element im Store
  pageBuilderStateStore.setSelectedTextElement(element);

  // Speichere den vollständigen ursprünglichen Zustand
  const originalState = captureElementState(element);
  
  // Setze diese Informationen im Store
  pageBuilderStateStore.setSelectedElementOriginalTag(originalState);
}

/**
 * Hebt die Auswahl eines Text-Elements auf
 * @param {Object} pageBuilderStateStore - Store für den PageBuilder-Status
 */
export function clearTextElementSelection(pageBuilderStateStore) {
  // Entferne bestehende Auswahl bei allen Elementen
  document.querySelectorAll('[text-selected]').forEach(el => {
    el.removeAttribute('text-selected');
  });
  
  // Setze den Store-Wert zurück
  pageBuilderStateStore.setSelectedTextElement(null);
  pageBuilderStateStore.setSelectedElementOriginalTag(null);
}

/**
 * Aktualisiert präzise nur den Textinhalt eines Elements unter Beibehaltung aller Tags und Formatierungen
 * @param {HTMLElement} element - Element, dessen Inhalt aktualisiert werden soll
 * @param {string} content - Der neue Inhalt
 * @param {Object} originalState - Der ursprüngliche Zustand des Elements
 * @returns {HTMLElement} - Das aktualisierte Element
 */
export function updateTextElementContent(element, content, originalState) {
  if (!element || !originalState) return element;
  
  try {
    // Erstelle ein temporäres Element zum Verarbeiten des neuen Inhalts
    const tempContainer = document.createElement('div');
    tempContainer.innerHTML = content;
    
    // Extrahiere Links aus dem neuen Inhalt
    const links = Array.from(tempContainer.querySelectorAll('a')).map(link => {
      return {
        href: link.getAttribute('href'),
        text: link.textContent,
        target: link.getAttribute('target'),
        title: link.getAttribute('title'),
        class: link.getAttribute('class')
      };
    });
    
    // Extrahiere andere wichtige Formatierungen (fett, kursiv)
    const boldTexts = Array.from(tempContainer.querySelectorAll('strong, b')).map(b => b.textContent);
    const italicTexts = Array.from(tempContainer.querySelectorAll('em, i')).map(i => i.textContent);
    
    // Erstelle ein neues Element mit dem ursprünglichen Tag
    const newElement = document.createElement(originalState.tagName);
    
    // Übertrage alle ursprünglichen Attribute
    originalState.attributes.forEach(attr => {
      if (attr.name !== 'innerHTML' && attr.name !== 'innerText' && attr.name !== 'textContent') {
        newElement.setAttribute(attr.name, attr.value);
      }
    });
    
    // Vergleiche, ob der Inhalt geändert wurde
    const contentHasChanged = tempContainer.textContent !== originalState.textContent || 
                              links.length > 0 || 
                              boldTexts.length > 0 || 
                              italicTexts.length > 0;
    
    if (contentHasChanged) {
      // Einfacherer Ansatz: Verwende reinen Text als Basis und füge dann Formatierungen hinzu
      const baseText = tempContainer.textContent;
      
      if (links.length > 0 || boldTexts.length > 0 || italicTexts.length > 0) {
        // Es gibt Formatierungen - wir verwenden den kompletten HTML-Inhalt vom TipTap
        newElement.innerHTML = content;
      } else {
        // Kein formatierter Text - verwende nur den Textinhalt
        newElement.textContent = baseText;
      }
    } else {
      // Wenn sich der Inhalt nicht geändert hat, behalte die ursprüngliche Struktur
      newElement.innerHTML = originalState.innerHTML;
    }
    
    // Ersetze das alte Element mit dem neuen
    if (element.parentNode) {
      element.parentNode.replaceChild(newElement, element);
      return newElement;
    }
    
    return element;
  } catch (error) {
    console.error("Fehler beim Aktualisieren des Text-Elements:", error);
    // Fallback: Direktes Setzen des Inhalts
    element.innerHTML = content;
    return element;
  }
}