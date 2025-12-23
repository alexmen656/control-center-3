import tailwindColors from '@/utils/builder/tailwaind-colors';
import tailwindOpacities from '@/utils/builder/tailwind-opacities';
import tailwindFontSizes from '@/utils/builder/tailwind-font-sizes';
import tailwindFontStyles from '@/utils/builder/tailwind-font-styles';
import tailwindPaddingAndMargin from '@/utils/builder/tailwind-padding-margin';
import tailwindBorderRadius from '@/utils/builder/tailwind-border-radius';
import tailwindBorderStyleWidthPlusColor from '@/utils/builder/tailwind-border-style-width-color';
import { computed, ref, nextTick } from 'vue';
import { v4 as uuidv4 } from 'uuid';
import { delay } from '@/composables/delay';
import { useFetch } from '@/composables/vueFetch';
import { 
  markEditableTextElements, 
  selectTextElementForEditing, 
  updateTextElementContent, 
  clearTextElementSelection 
} from '@/utils/builder/text-element-selector.js';

class PageBuilder {
  constructor(pageBuilderStateStore, mediaLibraryStore) {
    /**
     * Initialize an instance variable 'elementsWithListeners' as a WeakSet.
     *
     * A WeakSet is a special type of Set that holds weak references to its elements,
     * meaning that an element could be garbage collected if there is no other reference to it.
     * This is especially useful in the context of managing DOM elements and their associated events,
     * as it allows for efficient and automated cleanup of references to DOM elements that have been removed.
     *
     * By checking if an element is in this WeakSet before attaching an event listener,
     * we can prevent redundant addition of the same event listener to an element.
     * This helps in managing the memory usage and performance of the application.
     */
    this.elementsWithListeners = new WeakSet();

    this.nextTick = nextTick();

    this.containsPagebuilder = document.querySelector('#contains-pagebuilder');

    this.timer = null;
    this.pageBuilderStateStore = pageBuilderStateStore;
    this.mediaLibraryStore = mediaLibraryStore;
    
    // API client for backend communication
    this.api = useFetch();
    
    // Current page ID for API calls (default to page ID 1 for now)
    this.currentPageId = ref(1);

    // Import header utilities dynamically to avoid initialization issues
    import('@/utils/builder/header-component-utils.js').then(module => {
      this.headerUtils = module;
    }).catch(err => {
      console.error('Failed to load header utilities:', err);
    });

    this.getTextAreaVueModel = computed(
      () => this.pageBuilderStateStore.getTextAreaVueModel
    );
    this.getLocalStorageItemName = computed(
      () => this.pageBuilderStateStore.getLocalStorageItemName
    );
    this.getLocalStorageItemNameUpdate = computed(
      () => this.pageBuilderStateStore.getLocalStorageItemNameUpdate
    );

    this.getCurrentImage = computed(
      () => this.mediaLibraryStore.getCurrentImage
    );
    this.getHyberlinkEnable = computed(
      () => this.pageBuilderStateStore.getHyberlinkEnable
    );
    this.getComponents = computed(
      () => this.pageBuilderStateStore.getComponents
    );

    this.getComponent = computed(() => this.pageBuilderStateStore.getComponent);

    this.getElement = computed(() => this.pageBuilderStateStore.getElement);
    this.getNextSibling = computed(
      () => this.pageBuilderStateStore.getNextSibling
    );
    this.getParentElement = computed(
      () => this.pageBuilderStateStore.getParentElement
    );
    this.getRestoredElement = computed(
      () => this.pageBuilderStateStore.getRestoredElement
    );

    this.getComponentArrayAddMethod = computed(
      () => this.pageBuilderStateStore.getComponentArrayAddMethod
    );

    this.headerTags = ['P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'IFRAME'];

    this.additionalTagsNoneListernes = [
      'UL',
      'OL',
      'LI',
      'EM',
      'STRONG',
      'B',
      'A',
      'SPAN',
      'BLOCKQUOTE',
      'BR',
    ];

    this.structuringTags = [
      'DIV',
      'IFRAME',
      'HEADER',
      'NAV',
      'MAIN',
      'ARTICLE',
      'SECTION',
      'ASIDE',
      'FOOTER',
    ];

    this.showRunningMethodLogs = false;

    this.delay = delay();
  }

  shouldRunMethods() {
    if (!this.getComponents.value) {
      return false;
    }

    if (!this.getComponent.value) {
      return false;
    }

    if (!this.getElement.value) {
      return false;
    }

    return true;
  }

  #applyElementClassChanges(selectedCSS, CSSArray, mutationName) {
    if (this.showRunningMethodLogs) {
      console.log('#applyElementClassChanges');
    }
    if (!this.shouldRunMethods()) return;

    const currentCSS = CSSArray.find((CSS) => {
      return this.getElement.value.classList.contains(CSS);
    });

    // set to 'none' if undefined
    let elementClass = currentCSS || 'none';

    this.pageBuilderStateStore[mutationName](elementClass);

    if (typeof selectedCSS === 'string' && selectedCSS !== 'none') {
      if (
        elementClass &&
        this.getElement.value.classList.contains(elementClass)
      ) {
        this.getElement.value.classList.remove(elementClass);
      }

      this.getElement.value.classList.add(selectedCSS);
      elementClass = selectedCSS;
    } else if (
      typeof selectedCSS === 'string' &&
      selectedCSS === 'none' &&
      elementClass
    ) {
      this.getElement.value.classList.remove(elementClass);
      elementClass = selectedCSS;
    }

    this.pageBuilderStateStore[mutationName](elementClass);
    this.pageBuilderStateStore.setElement(this.getElement.value);

    return currentCSS;
  }

  #applyHelperCSSToElements(element) {
    this.#wrapElementInDivIfExcluded(element);

    if (this.showRunningMethodLogs) {
      console.log('#applyHelperCSSToElements');
    }

    if (element.tagName === 'IMG') {
      element.classList.add('smooth-transition');
    }

    // Add padding to DIV
    if (element.tagName === 'DIV') {
      if (element.classList.length === 0) {
        // element.classList.add("p-2");
      }
    }
  }

  #wrapElementInDivIfExcluded(element) {
    if (this.showRunningMethodLogs) {
      console.log('#wrapElementInDivIfExcluded');
    }

    if (
      this.headerTags.includes(element.tagName) &&
      ((element.previousElementSibling &&
        element.previousElementSibling.tagName === 'IMG') ||
        (element.nextElementSibling &&
          element.nextElementSibling.tagName === 'IMG'))
    ) {
      const divWrapper = document.createElement('div');
      element.parentNode.insertBefore(divWrapper, element);
      divWrapper.appendChild(element);
    }
  }

  #handleElementClick = (e, element) => {
    e.stopPropagation();

    const pagebuilder = document.querySelector('#pagebuilder');

    if (!pagebuilder) return;

    this.pageBuilderStateStore.setMenuRight(true);

    if (pagebuilder.querySelector('[selected]') !== null) {
      pagebuilder.querySelector('[selected]').removeAttribute('selected');
    }

    element.removeAttribute('hovered');

    element.setAttribute('selected', '');

    this.pageBuilderStateStore.setElement(element);
  };

  #handleMouseOver = (e, element) => {
    if (this.showRunningMethodLogs) {
      console.log('#handleMouseOver');
    }
    // console.log("YOU MOUSE OVER ME!");

    e.preventDefault();
    e.stopPropagation();

    const pagebuilder = document.querySelector('#pagebuilder');

    if (!pagebuilder) return;

    if (pagebuilder.querySelector('[hovered]') !== null) {
      pagebuilder.querySelector('[hovered]').removeAttribute('hovered');
    }

    if (!element.hasAttribute('selected')) {
      element.setAttribute('hovered', '');
    }
  };

  #handleMouseLeave = (e, element) => {
    if (this.showRunningMethodLogs) {
      console.log('#handleMouseLeave');
    }

    e.preventDefault();
    e.stopPropagation();

    const pagebuilder = document.querySelector('#pagebuilder');
    if (!pagebuilder) return;

    if (pagebuilder.querySelector('[hovered]') !== null) {
      pagebuilder.querySelector('[hovered]').removeAttribute('hovered');
    }
  };

  /**
   * The function is used to
   * attach event listeners to each element within a 'section'
   */
  setEventListenersForElements = () => {
    if (this.showRunningMethodLogs) {
      console.log('setEventListenersForElements');
    }

    const pagebuilder = document.querySelector('#pagebuilder');

    if (!pagebuilder) return;

    // Markiere bearbeitbare Text-Elemente in allen Komponenten
    document.querySelectorAll('#page-builder-editor-editable-area').forEach(container => {
      markEditableTextElements(container);
    });

    // Links im Editor modifizieren, anstatt Events zu blockieren
    pagebuilder.querySelectorAll('a').forEach(link => {
      // Speichere das ursprüngliche href-Attribut, falls noch nicht geschehen
      if (!link.hasAttribute('data-original-href') && link.hasAttribute('href')) {
        // Original-URL speichern
        link.setAttribute('data-original-href', link.getAttribute('href'));
        
        // Speichere auch das target-Attribut, falls vorhanden
        if (link.hasAttribute('target')) {
          link.setAttribute('data-original-target', link.getAttribute('target'));
        }
        
        // Temporäres href für den Editor
        link.setAttribute('href', 'javascript:void(0)');
        // Entferne target temporär im Editor, um Bildschirmwechsel zu verhindern
        if (link.hasAttribute('target')) {
          link.removeAttribute('target');
        }
      }
    });

    // Initialize header components if they exist
    this.initializeHeaderElements();

    pagebuilder.querySelectorAll('section *').forEach(async (element) => {
      // exclude headerTags && additional Tags for not listening
      if (
        !this.headerTags.includes(element.tagName) &&
        !this.additionalTagsNoneListernes.includes(element.tagName)
      ) {
        if (
          this.elementsWithListeners &&
          !this.elementsWithListeners.has(element)
        ) {
          this.elementsWithListeners.add(element);
          // Attach event listeners directly to individual elements
          element.addEventListener('click', (e) =>
            this.#handleElementClick(e, element)
          );
          element.addEventListener('mouseover', (e) =>
            this.#handleMouseOver(e, element)
          );
          element.addEventListener('mouseleave', (e) =>
            this.#handleMouseLeave(e, element)
          );
        }
      }

      // end for each iterating over elements
    });

    // Füge Event-Listener für bearbeitbare Text-Elemente hinzu
    pagebuilder.querySelectorAll('[data-editable-text]').forEach(element => {
      if (!this.elementsWithListeners.has(element)) {
        this.elementsWithListeners.add(element);
        element.addEventListener('dblclick', (e) => 
          this.handleTextElementClick(e, element)
        );
      }
    });
  };

  /**
   * Initialize any header components in the page builder
   */
  initializeHeaderElements = () => {
    if (!this.headerUtils) return;
    
    const pagebuilder = document.querySelector('#pagebuilder');
    if (!pagebuilder) return;
    
    // Find all header components
    const headerElements = pagebuilder.querySelectorAll('section header');
    
    // Initialize each header with interactive features
    headerElements.forEach(header => {
      if (!this.elementsWithListeners.has(header)) {
        this.elementsWithListeners.add(header);
        this.headerUtils.initializeHeader(header);
      }
    });
  };

  /**
   * Customize a header component with navigation links, dropdowns, logo, etc.
   * @param {HTMLElement} headerElement - The header element to customize
   * @param {Object} options - Customization options
   */
  customizeHeaderComponent = (headerElement, options = {}) => {
    if (!this.headerUtils || !headerElement) return;
    
    // Add navigation links
    if (options.links && Array.isArray(options.links)) {
      options.links.forEach(link => {
        this.headerUtils.addNavigationLink(headerElement, link);
      });
    }
    
    // Add dropdown menus
    if (options.dropdowns && Array.isArray(options.dropdowns)) {
      options.dropdowns.forEach(dropdown => {
        this.headerUtils.addDropdownMenu(headerElement, dropdown);
      });
    }
    
    // Change logo
    if (options.logo) {
      this.headerUtils.changeLogo(headerElement, options.logo);
    }
    
    // Add action buttons
    if (options.buttons && Array.isArray(options.buttons)) {
      options.buttons.forEach(button => {
        this.headerUtils.addActionButton(headerElement, button);
      });
    }
  };

  /**
   * The Intersection Observer API provides a way to asynchronously observe changes in the
   * intersection of a target element with an ancestor element or with a top-level document's viewport.
   */
  async synchronizeDOMAndComponents() {
    if (this.showRunningMethodLogs) {
      console.log('synchronizeDOMAndComponents');
    }
    
    if (!this.getComponents.value) {
      this.pageBuilderStateStore.setComponents([]);
    }

    if (document.querySelector('[hovered]') !== null) {
      document.querySelector('[hovered]').removeAttribute('hovered');
    }

    this.getComponents.value.forEach((component) => {
      const section = document.querySelector(
        `section[data-componentid="${component.id}"]`
      );

      if (section) {
        component.html_code = section.outerHTML;
      }
    });

    // Initialize the MutationObserver
    this.observer = new MutationObserver((mutationsList, observer) => {
      // Once we have seen a mutation, stop observing and resolve the promise
      observer.disconnect();
    });

    // Start observing the document with the configured parameters
    this.observer.observe(document, {
      attributes: true,
      childList: true,
      subtree: true,
    });

    // Use the MutationObserver to wait for the next DOM change
    await new Promise((resolve) => {
      resolve();
    });
    
    // After DOM sync, update components in database
    await this.saveComponentsToBackend();
  }

  cloneCompObjForDOMInsertion(componentObject) {
    if (this.showRunningMethodLogs) {
      console.log('cloneCompObjForDOMInsertion');
    }

    // Deep clone clone component
    const clonedComponent = { ...componentObject };

    //  scoll to top or bottom # end
    if (this.containsPagebuilder) {
      if (
        this.getComponentArrayAddMethod.value === 'unshift' ||
        this.getComponentArrayAddMethod.value === 'push'
      ) {
        // push to top
        if (this.getComponentArrayAddMethod.value === 'unshift') {
          this.containsPagebuilder.scrollTo({
            top: 0,
            behavior: 'smooth',
          });
        }

        // push to bottom
        if (this.getComponentArrayAddMethod.value === 'push') {
          const maxHeight = this.containsPagebuilder.scrollHeight;
          this.containsPagebuilder.scrollTo({
            top: maxHeight,
            behavior: 'smooth',
          });
        }
      }
    }

    // Create a DOMParser instance
    const parser = new DOMParser();

    // Parse the HTML content of the clonedComponent using the DOMParser
    const doc = parser.parseFromString(clonedComponent.html_code, 'text/html');

    // Selects all elements within the HTML document, including elements like:
    const elements = doc.querySelectorAll('*');

    elements.forEach((element) => {
      this.#applyHelperCSSToElements(element);
    });

    // Add the component id to the section element
    const section = doc.querySelector('section');
    if (section) {
      // Generate a unique ID using uuidv4() and assign it to the section
      section.dataset.componentid = uuidv4();

      // Markiere alle Text-Elemente in dieser Komponente
      markEditableTextElements(section);

      // Find all images within elements with "flex" or "grid" classes inside the section
      const images = section.querySelectorAll('img');

      // Add a unique ID as a data attribute to each image element
      images.forEach((image) => {
        image.setAttribute('data-image', uuidv4());
      });
    }

    // Update the clonedComponent id with the newly generated unique ID
    clonedComponent.id = section.dataset.componentid;

    // Update the HTML content of the clonedComponent with the modified HTML
    clonedComponent.html_code = doc.querySelector('section').outerHTML;

    // return to the cloned element to be dropped
    return clonedComponent;
  }

  removeHoveredAndSelected() {
    if (this.showRunningMethodLogs) {
      console.log('removeHoveredAndSelected');
    }

    if (document.querySelector('[hovered]') !== null) {
      document.querySelector('[hovered]').removeAttribute('hovered');
    }

    if (document.querySelector('[selected]') !== null) {
      document.querySelector('[selected]').removeAttribute('selected');
    }
  }

  currentClasses() {
    if (this.showRunningMethodLogs) {
      console.log('handleAddClasses');
    }

    if (!this.shouldRunMethods()) return;

    // convert classList to array
    let classListArray = Array.from(this.getElement.value.classList);

    // commit array to store
    this.pageBuilderStateStore.setCurrentClasses(classListArray);
  }

  handleAddClasses(userSelectedClass) {
    if (this.showRunningMethodLogs) {
      console.log('handleAddClasses');
    }

    if (!this.shouldRunMethods()) return;

    if (
      typeof userSelectedClass === 'string' &&
      userSelectedClass !== '' &&
      !userSelectedClass.includes(' ') &&
      // Check if class already exists
      !this.getElement.value.classList.contains(userSelectedClass)
    ) {
      // Remove any leading/trailing spaces
      const cleanedClass = userSelectedClass.trim();

      this.getElement.value.classList.add(cleanedClass);

      this.pageBuilderStateStore.setElement(this.getElement.value);

      this.pageBuilderStateStore.setClass(userSelectedClass);
    }
  }
  handleRemoveClasses(userSelectedClass) {
    if (this.showRunningMethodLogs) {
      console.log('handleRemoveClasses');
    }

    if (!this.shouldRunMethods()) return;

    // remove selected class from element
    if (this.getElement.value.classList.contains(userSelectedClass)) {
      this.getElement.value.classList.remove(userSelectedClass);

      this.pageBuilderStateStore.setElement(this.getElement.value);
      this.pageBuilderStateStore.removeClass(userSelectedClass);
    }
  }

  handleDeleteElement() {
    if (this.showRunningMethodLogs) {
      console.log('handleDeleteElement');
    }

    // Get the element to be deleted
    const element = this.getElement.value;

    if (!element) return;

    if (!element?.parentNode) {
      this.pageBuilderStateStore.setComponent(null);
      this.pageBuilderStateStore.setElement(null);
      return;
    }

    // Store the parent of the deleted element
    // if parent element tag is section remove the hole component
    if (element.parentElement?.tagName !== 'SECTION') {
      this.pageBuilderStateStore.setParentElement(element.parentNode);

      // Store the outerHTML of the deleted element
      this.pageBuilderStateStore.setRestoredElement(element.outerHTML);

      // Store the next sibling of the deleted element
      this.pageBuilderStateStore.setNextSibling(element.nextSibling);
    }

    // if parent element tag is section remove the hole component
    if (element.parentElement?.tagName === 'SECTION') {
      this.deleteComponent();
    }

    // Remove the element from the DOM
    element.remove();
    this.pageBuilderStateStore.setComponent(null);
    this.pageBuilderStateStore.setElement(null);
  }

  handleRestoreElement() {
    if (this.showRunningMethodLogs) {
      console.log('handleRestoreElement');
    }

    // Get the stored deleted element and its parent
    if (this.getRestoredElement.value && this.getParentElement.value) {
      // Create a new element from the stored outerHTML
      const newElement = document.createElement('div');
      newElement.innerHTML = this.getRestoredElement.value;

      // Append the restored element to its parent
      // Insert the restored element before its next sibling in its parent
      this.getParentElement.value.insertBefore(
        newElement.firstChild,
        this.getNextSibling.value
      );
    }

    // Clear

    this.pageBuilderStateStore.setParentElement(null);
    this.pageBuilderStateStore.setRestoredElement(null);
    this.pageBuilderStateStore.setComponent(null);
    this.pageBuilderStateStore.setElement(null);

    this.setEventListenersForElements();
  }

  handleFontWeight(userSelectedFontWeight) {
    if (this.showRunningMethodLogs) {
      console.log('handleFontWeight');
    }

    this.#applyElementClassChanges(
      userSelectedFontWeight,
      tailwindFontStyles.fontWeight,
      'setFontWeight'
    );
  }
  handleFontFamily(userSelectedFontFamily) {
    if (this.showRunningMethodLogs) {
      console.log('handleFontFamily');
    }

    this.#applyElementClassChanges(
      userSelectedFontFamily,
      tailwindFontStyles.fontFamily,
      'setFontFamily'
    );
  }
  handleFontStyle(userSelectedFontStyle) {
    if (this.showRunningMethodLogs) {
      console.log('handleFontStyle');
    }

    this.#applyElementClassChanges(
      userSelectedFontStyle,
      tailwindFontStyles.fontStyle,
      'setFontStyle'
    );
  }
  handleVerticalPadding(userSelectedVerticalPadding) {
    if (this.showRunningMethodLogs) {
      console.log('handleVerticalPadding');
    }

    this.#applyElementClassChanges(
      userSelectedVerticalPadding,
      tailwindPaddingAndMargin.verticalPadding,
      'setFontVerticalPadding'
    );
  }
  handleHorizontalPadding(userSelectedHorizontalPadding) {
    if (this.showRunningMethodLogs) {
      console.log('handleHorizontalPadding');
    }

    this.#applyElementClassChanges(
      userSelectedHorizontalPadding,
      tailwindPaddingAndMargin.horizontalPadding,
      'setFontHorizontalPadding'
    );
  }

  handleVerticalMargin(userSelectedVerticalMargin) {
    if (this.showRunningMethodLogs) {
      console.log('handleVerticalMargin');
    }

    this.#applyElementClassChanges(
      userSelectedVerticalMargin,
      tailwindPaddingAndMargin.verticalMargin,
      'setFontVerticalMargin'
    );
  }
  handleHorizontalMargin(userSelectedHorizontalMargin) {
    if (this.showRunningMethodLogs) {
      console.log('handleHorizontalMargin');
    }

    this.#applyElementClassChanges(
      userSelectedHorizontalMargin,
      tailwindPaddingAndMargin.horizontalMargin,
      'setFontHorizontalMargin'
    );
  }

  // border color, style & width / start
  handleBorderStyle(borderStyle) {
    if (this.showRunningMethodLogs) {
      console.log('handleBorderStyle');
    }

    this.#applyElementClassChanges(
      borderStyle,
      tailwindBorderStyleWidthPlusColor.borderStyle,
      'setBorderStyle'
    );
  }
  handleBorderWidth(borderWidth) {
    if (this.showRunningMethodLogs) {
      console.log('handleBorderWidth');
    }

    this.#applyElementClassChanges(
      borderWidth,
      tailwindBorderStyleWidthPlusColor.borderWidth,
      'setBorderWidth'
    );
  }
  handleBorderColor(borderColor) {
    if (this.showRunningMethodLogs) {
      console.log('handleBorderColor');
    }

    this.#applyElementClassChanges(
      borderColor,
      tailwindBorderStyleWidthPlusColor.borderColor,
      'setBorderColor'
    );
  }
  // border color, style & width / end

  handleBackgroundColor(color) {
    this.#applyElementClassChanges(
      color,
      tailwindColors.backgroundColorVariables,
      'setBackgroundColor'
    );
  }

  handleTextColor(color) {
    if (this.showRunningMethodLogs) {
      console.log('handleTextColor');
    }

    if (!this.shouldRunMethods()) return;

    // Das ausgewählte Element
    const currentElement = this.getElement.value;
    if (!currentElement) return;

    // Bestimme das korrekte Zielelement für die Textfarbe
    let targetElement = currentElement;
    
    // Wenn das aktuelle Element ein div oder anderes Container-Element ist,
    // suche nach dem ersten Text-Element innerhalb dieses Containers
    if (this.structuringTags.includes(currentElement.tagName)) {
      // Liste der Heading- und Text-Elemente, auf die wir die Farbe anwenden möchten
      const textTags = ['P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'SPAN', 'A'];
      
      // Suche nach dem ersten direkten Kind, das ein Text-Element ist
      for (const child of currentElement.children) {
        if (textTags.includes(child.tagName)) {
          targetElement = child;
          break;
        }
      }
    }

    // Wenn wir ein Zielelement haben, wende die Textfarbe darauf an
    if (targetElement) {
      // Entferne bestehende Textfarbklassen
      const currentCSS = tailwindColors.textColorVariables.find((CSS) => {
        return targetElement.classList.contains(CSS);
      });

      // Set the current CSS in the store
      let elementClass = currentCSS || 'none';
      this.pageBuilderStateStore.setTextColor(elementClass);

      if (typeof color === 'string' && color !== 'none') {
        // Entferne alte Farbe falls vorhanden
        if (elementClass && targetElement.classList.contains(elementClass)) {
          targetElement.classList.remove(elementClass);
        }

        // Füge neue Farbe hinzu
        targetElement.classList.add(color);
        elementClass = color;
      } else if (typeof color === 'string' && color === 'none' && elementClass) {
        // Entferne die Farbklasse wenn 'none' ausgewählt wurde
        targetElement.classList.remove(elementClass);
        elementClass = color;
      }

      // Aktualisiere den Store mit der neuen Farbklasse
      this.pageBuilderStateStore.setTextColor(elementClass);
      
      // Aktualisiere das Element im Store, falls wir ein anderes Zielelement verwendet haben
      if (targetElement !== currentElement) {
        this.pageBuilderStateStore.setElement(targetElement);
      }
    } else {
      // Fallback auf die alte Methode, wenn wir kein geeignetes Zielelement gefunden haben
      this.#applyElementClassChanges(
        color,
        tailwindColors.textColorVariables,
        'setTextColor'
      );
    }
  }

  // border radius / start
  handleBorderRadiusGlobal(borderRadiusGlobal) {
    if (this.showRunningMethodLogs) {
      console.log('handleBorderRadiusGlobal');
    }

    this.#applyElementClassChanges(
      borderRadiusGlobal,
      tailwindBorderRadius.roundedGlobal,
      'setBorderRadiusGlobal'
    );
  }
  handleBorderRadiusTopLeft(borderRadiusTopLeft) {
    if (this.showRunningMethodLogs) {
      console.log('handleBorderRadiusTopLeft');
    }

    this.#applyElementClassChanges(
      borderRadiusTopLeft,
      tailwindBorderRadius.roundedTopLeft,
      'setBorderRadiusTopLeft'
    );
  }
  handleBorderRadiusTopRight(borderRadiusTopRight) {
    if (this.showRunningMethodLogs) {
      console.log('handleBorderRadiusTopRight');
    }

    this.#applyElementClassChanges(
      borderRadiusTopRight,
      tailwindBorderRadius.roundedTopRight,
      'setBorderRadiusTopRight'
    );
  }
  handleBorderRadiusBottomleft(borderRadiusBottomleft) {
    if (this.showRunningMethodLogs) {
      console.log('handleBorderRadiusBottomleft');
    }

    this.#applyElementClassChanges(
      borderRadiusBottomleft,
      tailwindBorderRadius.roundedBottomLeft,
      'setBorderRadiusBottomleft'
    );
  }
  handleBorderRadiusBottomRight(borderRadiusBottomRight) {
    if (this.showRunningMethodLogs) {
      console.log('handleBorderRadiusBottomRight');
    }

    this.#applyElementClassChanges(
      borderRadiusBottomRight,
      tailwindBorderRadius.roundedBottomRight,
      'setBorderRadiusBottomRight'
    );
  }
  // border radius / end

  handleFontSize(userSelectedFontSize) {
    if (this.showRunningMethodLogs) {
      console.log('handleFontSize');
    }

    if (!this.shouldRunMethods()) return;

    let fontBase = tailwindFontSizes.fontBase.find((size) => {
      return this.getElement.value.classList.contains(size);
    });
    if (fontBase === undefined) {
      fontBase = 'base:none';
    }

    let fontDesktop = tailwindFontSizes.fontDesktop.find((size) => {
      return this.getElement.value.classList.contains(size);
    });
    if (fontDesktop === undefined) {
      fontDesktop = 'lg:none';
    }

    let fontTablet = tailwindFontSizes.fontTablet.find((size) => {
      return this.getElement.value.classList.contains(size);
    });
    if (fontTablet === undefined) {
      fontTablet = 'md:none';
    }

    let fontMobile = tailwindFontSizes.fontMobile.find((size) => {
      return this.getElement.value.classList.contains(size);
    });
    if (fontMobile === undefined) {
      fontMobile = 'sm:none';
    }

    // set fonts
    this.pageBuilderStateStore.setFontBase(fontBase);
    this.pageBuilderStateStore.setFontDesktop(fontDesktop);
    this.pageBuilderStateStore.setFontTablet(fontTablet);
    this.pageBuilderStateStore.setFontMobile(fontMobile);

    const getFontBase = computed(() => {
      return this.pageBuilderStateStore.getFontBase;
    });
    const getFontDesktop = computed(() => {
      return this.pageBuilderStateStore.getFontDesktop;
    });
    const getFontTablet = computed(() => {
      return this.pageBuilderStateStore.getFontTablet;
    });
    const getFontMobile = computed(() => {
      return this.pageBuilderStateStore.getFontMobile;
    });

    if (userSelectedFontSize !== undefined) {
      if (
        !userSelectedFontSize.includes('sm:') &&
        !userSelectedFontSize.includes('md:') &&
        !userSelectedFontSize.includes('lg:')
      ) {
        this.getElement.value.classList.remove(getFontBase.value);
        if (!userSelectedFontSize.includes('base:none')) {
          this.getElement.value.classList.add(userSelectedFontSize);
        }

        this.pageBuilderStateStore.setFontBase(userSelectedFontSize);
      }
      if (userSelectedFontSize.includes('lg:')) {
        this.getElement.value.classList.remove(getFontDesktop.value);
        if (!userSelectedFontSize.includes('lg:none')) {
          this.getElement.value.classList.add(userSelectedFontSize);
        }

        this.pageBuilderStateStore.setFontDesktop(userSelectedFontSize);
      }
      if (userSelectedFontSize.includes('md:')) {
        this.getElement.value.classList.remove(getFontTablet.value);
        if (!userSelectedFontSize.includes('md:none')) {
          this.getElement.value.classList.add(userSelectedFontSize);
        }

        this.pageBuilderStateStore.setFontTablet(userSelectedFontSize);
      }
      if (userSelectedFontSize.includes('sm:')) {
        this.getElement.value.classList.remove(getFontMobile.value);
        if (!userSelectedFontSize.includes('sm:none')) {
          this.getElement.value.classList.add(userSelectedFontSize);
        }

        this.pageBuilderStateStore.setFontMobile(userSelectedFontSize);
      }
      this.pageBuilderStateStore.setElement(this.getElement.value);
    }
  }

  handleBackgroundOpacity(opacity) {
    if (this.showRunningMethodLogs) {
      console.log('handleBackgroundOpacity');
    }

    this.#applyElementClassChanges(
      opacity,
      tailwindOpacities.backgroundOpacities,
      'setBackgroundOpacity'
    );
  }
  handleOpacity(opacity) {
    if (this.showRunningMethodLogs) {
      console.log('handleOpacity');
    }

    this.#applyElementClassChanges(
      opacity,
      tailwindOpacities.opacities,
      'setOpacity'
    );
  }

  deleteAllComponents() {
    if (this.showRunningMethodLogs) {
      console.log('deleteAllComponents');
    }

    this.pageBuilderStateStore.setComponents([]);
    
    // Delete all components from backend
    this.api.post('components.php', [], { page_id: this.getPageIdFromStore() })
      .catch(error => console.error('Failed to delete all components:', error));
  }

  deleteComponent() {
    if (this.showRunningMethodLogs) {
      console.log('deleteComponent');
    }

    if (!this.shouldRunMethods()) return;

    // Find the index of the component to delete
    const indexToDelete = this.getComponents.value.findIndex(
      (component) => component.id === this.getComponent.value.id
    );

    if (indexToDelete === -1) {
      // Component not found in the array, handle this case as needed.
      return;
    }

    // Get the component ID before removing it
    const componentId = this.getComponents.value[indexToDelete].id;

    // Remove the component from the array
    this.getComponents.value.splice(indexToDelete, 1);
    this.pageBuilderStateStore.setComponents(this.getComponents.value);

    this.pageBuilderStateStore.setComponent(null);
    this.pageBuilderStateStore.setElement(null);
    
    // Delete the component from backend
    this.deleteComponentFromBackend(componentId)
      .catch(error => console.error('Failed to delete component:', error));
  }

  // move component
  // runs when html components are rearranged
  moveComponent(direction) {
    if (this.showRunningMethodLogs) {
      console.log('moveComponent');
    }

    if (!this.shouldRunMethods()) return;

    if (this.getComponents.value.length <= 1) return;

    // Get the component you want to move (replace this with your actual logic)
    const componentToMove = this.getComponent.value;

    // Determine the new index where you want to move the component
    const currentIndex = this.getComponents.value.findIndex(
      (component) => component.id === componentToMove.id
    );

    if (currentIndex === -1) {
      // Component not found in the array, handle this case as needed.
      return;
    }

    const newIndex = currentIndex + direction;

    // Ensure the newIndex is within bounds
    if (newIndex < 0 || newIndex >= this.getComponents.value.length) {
      return;
    }

    // Move the component to the new position
    this.getComponents.value.splice(currentIndex, 1);
    this.getComponents.value.splice(newIndex, 0, componentToMove);
  }

  ensureTextAreaHasContent = () => {
    if (this.showRunningMethodLogs) {
      console.log('ensureTextAreaHasContent');
    }

    if (!this.shouldRunMethods()) return;

    // text content
    if (typeof this.getElement.value.innerHTML !== 'string') {
      return;
    }
    const element = this.getElement.value;
    const elementTag = element.tagName;

    if (
      ['DIV'].includes(elementTag) &&
      element.tagName.toLowerCase() !== 'img' &&
      Number(element.textContent.length) === 0
    ) {
      element.classList.add('h-6');
      element.classList.add('bg-red-50');
    } else {
      element.classList.remove('h-6');
      element.classList.remove('bg-red-50');
    }
  };

  //
  handleTextInput = async (textContentVueModel) => {
    if (this.showRunningMethodLogs) {
      console.log('handleTextInput');
    }

    if (!this.shouldRunMethods()) return;

    // // text content
    if (typeof this.getElement.value?.innerHTML !== 'string') {
      return;
    }

    if (typeof this.getElement.value.innerHTML === 'string') {
      await this.nextTick;

      // Update text content
      this.getElement.value.textContent = textContentVueModel;

      this.pageBuilderStateStore.setTextAreaVueModel(
        this.getElement.value.innerHTML
      );

      this.getElement.value.innerHTML = textContentVueModel;
    }

    this.ensureTextAreaHasContent();
  };

  //
  //
  ElOrFirstChildIsIframe() {
    if (
      this.getElement.value?.tagName === 'IFRAME' ||
      this.getElement.value?.firstElementChild?.tagName === 'IFRAME'
    ) {
      return true;
    } else {
      return false;
    }
  }
  //
  //
  //
  selectedElementIsValidText() {
    let reachedElseStatement = false;

    // Get all child elements of the parentDiv
    const childElements = this.getElement.value?.children;
    if (
      this.getElement.value?.tagName === 'IMG' ||
      this.getElement.value?.firstElementChild?.tagName === 'IFRAME'
    ) {
      return;
    }
    if (!childElements) {
      return;
    }

    Array.from(childElements).forEach((element) => {
      if (element?.tagName === 'IMG' || element?.tagName === 'DIV') {
        reachedElseStatement = false;
      } else {
        reachedElseStatement = true;
      }
    });

    return reachedElseStatement;
  }

  previewCurrentDesign() {
    if (this.showRunningMethodLogs) {
      console.log('previewCurrentDesign');
    }

    this.pageBuilderStateStore.setElement(null);

    const addedHtmlComponents = ref([]);
    // preview current design in external browser tab
    // iterate over each top-level section component
    document
      .querySelectorAll('section:not(section section)')
      .forEach((section) => {
        // Zuerst eine Kopie des Abschnitts erstellen, um die Original-URLs zu bewahren
        const sectionClone = section.cloneNode(true);
        
        // Links im geklonten Abschnitt wiederherstellen
        sectionClone.querySelectorAll('a').forEach(link => {
          // Original-URL wiederherstellen, falls gespeichert
          if (link.hasAttribute('data-original-href')) {
            link.href = link.getAttribute('data-original-href');
          }
          
          // Original-Target wiederherstellen, falls gespeichert
          if (link.hasAttribute('data-original-target')) {
            link.target = link.getAttribute('data-original-target');
          }
        });

        // remove hovered and selected
        if (sectionClone.querySelector('[hovered]') !== null) {
          sectionClone.querySelector('[hovered]').removeAttribute('hovered');
        }

        // remove selected
        if (sectionClone.querySelector('[selected]') !== null) {
          sectionClone.querySelector('[selected]').removeAttribute('selected');
        }

        // push outer html into the array
        addedHtmlComponents.value.push(sectionClone.outerHTML);
      });

    // stringify added html components
    addedHtmlComponents.value = JSON.stringify(addedHtmlComponents.value);

    // commit
    this.pageBuilderStateStore.setCurrentLayoutPreview(
      addedHtmlComponents.value
    );

    // set added html components back to empty array
    addedHtmlComponents.value = [];
  }

  async saveComponentsLocalStorage() {
    await this.nextTick;
    this.synchronizeDOMAndComponents();

    if (this.showRunningMethodLogs) {
      console.log('saveComponentsLocalStorage -> saveComponentsToBackend');
    }

    await this.nextTick;
    return this.saveComponentsToBackend();
  }

  async saveComponentsLocalStorageUpdate() {
    if (this.showRunningMethodLogs) {
      console.log('saveComponentsLocalStorageUpdate -> saveComponentsToBackend');
    }

    await this.nextTick;
    return this.saveComponentsToBackend();
  }
  
  async removeItemComponentsLocalStorageUpdate() {
    if (this.showRunningMethodLogs) {
      console.log('removeItemComponentsLocalStorageUpdate');
    }

    // We don't need to remove anything from backend since we'll just overwrite it
    // when saving new components
    return true;
  }

  async areComponentsStoredInLocalStorage() {
    if (this.showRunningMethodLogs) {
      console.log('areComponentsStoredInLocalStorage -> loadComponentsFromBackend');
    }

    return this.loadComponentsFromBackend();
  }
  
  async areComponentsStoredInLocalStorageUpdate() {
    if (this.showRunningMethodLogs) {
      console.log('areComponentsStoredInLocalStorageUpdate -> loadComponentsFromBackend');
    }

    return this.loadComponentsFromBackend();
  }

  async loadComponentsFromBackend() {
    if (this.showRunningMethodLogs) {
      console.log('loadComponentsFromBackend');
    }
    
    try {
      // Aktualisiere die currentPageId mit dem Wert aus dem Store
      const pageId = this.getPageIdFromStore();
      console.log('Loading components for page ID:', pageId);
      
      // Stelle sicher, dass eine gültige Seiten-ID verwendet wird (keine 0 oder leere ID)
      if (!pageId || pageId === 0) {
        console.error('Ungültige Seiten-ID beim Laden von Komponenten:', pageId);
        return false;
      }
      
      const components = await this.api.get('components.php', { page_id: pageId });
      console.log('Geladene Komponenten für Seite', pageId, ':', components);
      
      if (components && Array.isArray(components)) {
        this.pageBuilderStateStore.setComponents(components);
        return true;
      }
      
      return false;
    } catch (error) {
      console.error('Failed to load components from backend:', error);
      return false;
    }
  }
  
  async saveComponentsToBackend() {
    if (this.showRunningMethodLogs) {
      console.log('saveComponentsToBackend');
    }
    
    await this.nextTick;
    
    try {
      // Aktualisiere die currentPageId mit dem Wert aus dem Store
      const pageId = this.getPageIdFromStore();
      console.log('Saving components for page ID:', pageId);
      
      // Stelle sicher, dass eine gültige Seiten-ID verwendet wird (keine 0 oder leere ID)
      if (!pageId || pageId === 0) {
        console.error('Ungültige Seiten-ID beim Speichern von Komponenten:', pageId);
        return false;
      }
      
      // Hole die Komponenten aus dem Store
      const components = this.getComponents.value || [];
      console.log('Speichere', components.length, 'Komponenten für Seite', pageId);
      
      // Gib eine Warnung aus, wenn keine Komponenten zu speichern sind
      if (components.length === 0) {
        console.warn('Keine Komponenten zum Speichern für Seite', pageId);
      }
      
      // Save all components
      await this.api.post('components.php', components, { page_id: pageId });
      console.log('Komponenten erfolgreich für Seite', pageId, 'gespeichert');
      return true;
    } catch (error) {
      console.error('Failed to save components to backend:', error);
      return false;
    }
  }
  
  // Verbesserte Methode zum Abrufen der Page-ID aus dem Store
  getPageIdFromStore() {
    // Versuche zuerst die pageBuilderStateStore.currentPageId direkt zu holen
    if (this.pageBuilderStateStore && this.pageBuilderStateStore.currentPageId) {
      const pageId = parseInt(this.pageBuilderStateStore.currentPageId, 10);
      if (pageId && !isNaN(pageId)) {
        return pageId;
      }
    }
    
    // Falle zurück auf die dynamischen Getter wenn nötig
    if (this.pageBuilderStateStore && this.pageBuilderStateStore.getCurrentPageId) {
      const storePageId = this.pageBuilderStateStore.getCurrentPageId;
      // Wenn es sich um eine Funktion handelt, rufen wir sie auf
      if (typeof storePageId === 'function') {
        return parseInt(storePageId(), 10) || this.currentPageId.value;
      }
      // Wenn es sich um ein berechnetes Ref handelt
      else if (storePageId && storePageId.value !== undefined) {
        return parseInt(storePageId.value, 10) || this.currentPageId.value;
      }
    }
    
    // Fallback auf die interne Variable
    return this.currentPageId.value;
  }

  async saveComponentToBackend(component) {
    if (this.showRunningMethodLogs) {
      console.log('saveComponentToBackend');
    }
    
    try {
      // Aktualisiere die currentPageId mit dem Wert aus dem Store
      const pageId = this.getPageIdFromStore();
      
      await this.api.put('components.php', component, { page_id: pageId });
      return true;
    } catch (error) {
      console.error('Failed to save component to backend:', error);
      return false;
    }
  }
  
  async deleteComponentFromBackend(componentId) {
    if (this.showRunningMethodLogs) {
      console.log('deleteComponentFromBackend');
    }
    
    try {
      // Aktualisiere die currentPageId mit dem Wert aus dem Store
      const pageId = this.getPageIdFromStore();
      
      await this.api.del('components.php', { 
        page_id: pageId,
        component_id: componentId 
      });
      return true;
    } catch (error) {
      console.error('Failed to delete component from backend:', error);
      return false;
    }
  }

  setCurrentPageId(pageId) {
    if (this.showRunningMethodLogs) {
      console.log('setCurrentPageId', pageId);
    }
    // Konvertiere zu einer Nummer, falls es ein String ist
    const numericPageId = parseInt(pageId, 10);
    this.currentPageId.value = numericPageId || 1;
  }

  async updateBasePrimaryImage(data) {
    if (this.showRunningMethodLogs) {
      console.log('updateBasePrimaryImage');
    }

    if (!this.getElement.value) return;

    if (data.type === 'unsplash' && this.getCurrentImage.value) {
      if (this.getCurrentImage.value.file) {
        await this.nextTick;

        this.pageBuilderStateStore.setBasePrimaryImage(
          `${this.getCurrentImage.value.file}`
        );
      }
    }
  }

  showBasePrimaryImage() {
    if (this.showRunningMethodLogs) {
      console.log('showBasePrimaryImage');
    }

    const currentImageContainer = document.createElement('div');

    currentImageContainer.innerHTML = this.getElement.value.outerHTML;

    // Get all img and div within the current image container
    const imgElements = currentImageContainer.getElementsByTagName('img');
    const divElements = currentImageContainer.getElementsByTagName('div');

    // Check if there is exactly one img and no div
    if (imgElements.length === 1 && divElements.length === 0) {
      // Return the source of the only img

      this.pageBuilderStateStore.setBasePrimaryImage(imgElements[0].src);

      return;
    }

    this.pageBuilderStateStore.setBasePrimaryImage(null);
  }

  #addHyperlinkToElement(hyperlinkEnable, urlInput, openHyperlinkInNewTab) {
    if (this.showRunningMethodLogs) {
      console.log('#addHyperlinkToElement');
    }

    if (!this.shouldRunMethods()) return;

    const parentHyperlink = this.getElement.value.closest('a');
    const hyperlink = this.getElement.value.querySelector('a');

    this.pageBuilderStateStore.setHyperlinkError(null);

    // url validation
    const urlRegex = /^https?:\/\//;

    const isValidURL = ref(true);

    if (hyperlinkEnable === true && urlInput !== null) {
      isValidURL.value = urlRegex.test(urlInput);
    }

    if (isValidURL.value === false) {
      this.pageBuilderStateStore.setHyperlinkMessage(null);

      this.pageBuilderStateStore.setHyperlinkError('URL is not valid');
      return;
    }

    if (hyperlinkEnable === true && typeof urlInput === 'string') {
      // check if element contains child hyperlink tag
      // updated existing url
      if (hyperlink !== null && urlInput.length !== 0) {
        hyperlink.href = urlInput;

        // Conditionally set the target attribute if openHyperlinkInNewTab is true
        if (openHyperlinkInNewTab === true) {
          hyperlink.target = '_blank';
        }
        if (openHyperlinkInNewTab === false) {
          hyperlink.removeAttribute('target');
        }

        hyperlink.textContent = this.getElement.value.textContent;

        this.pageBuilderStateStore.setHyperlinkMessage(
          'Succesfully updated element hyperlink'
        );

        this.pageBuilderStateStore.setElementContainsHyperlink(true);

        return;
      }

      // check if element contains child a tag
      if (hyperlink === null && urlInput.length !== 0) {
        // add a href
        if (parentHyperlink === null) {
          const link = document.createElement('a');
          link.href = urlInput;

          // Conditionally set the target attribute if openHyperlinkInNewTab is true
          if (openHyperlinkInNewTab === true) {
            link.target = '_blank';
          }

          link.textContent = this.getElement.value.textContent;
          this.getElement.value.textContent = '';
          this.getElement.value.appendChild(link);

          this.pageBuilderStateStore.setHyperlinkMessage(
            'Successfully added hyperlink to element'
          );

          this.pageBuilderStateStore.setElementContainsHyperlink(true);

          return;
        }
      }
      //
    }

    if (hyperlinkEnable === false && urlInput === 'removeHyperlink') {
      // To remove the added hyperlink tag
      const originalText = this.getElement.value.textContent;
      const textNode = document.createTextNode(originalText);
      this.getElement.value.textContent = '';
      this.getElement.value.appendChild(textNode);

      this.pageBuilderStateStore.setHyberlinkEnable(false);
      this.pageBuilderStateStore.setElementContainsHyperlink(false);
    }
  }

  #checkForHyperlink(hyperlinkEnable, urlInput, openHyperlinkInNewTab) {
    if (this.showRunningMethodLogs) {
      console.log('#checkForHyperlink');
    }

    if (!this.shouldRunMethods()) return;

    const hyperlink = this.getElement.value.querySelector('a');
    if (hyperlink !== null) {
      this.pageBuilderStateStore.setHyberlinkEnable(true);
      this.pageBuilderStateStore.setElementContainsHyperlink(true);
      this.pageBuilderStateStore.setHyperlinkInput(hyperlink.href);
      this.pageBuilderStateStore.setHyperlinkMessage(null);
      this.pageBuilderStateStore.setHyperlinkError(null);

      if (hyperlink.target === '_blank') {
        this.pageBuilderStateStore.setOpenHyperlinkInNewTab(true);
      }
      if (hyperlink.target !== '_blank') {
        this.pageBuilderStateStore.setOpenHyperlinkInNewTab(false);
      }

      return;
    }

    this.pageBuilderStateStore.setElementContainsHyperlink(false);
    this.pageBuilderStateStore.setHyperlinkInput('');
    this.pageBuilderStateStore.setHyperlinkError(null);
    this.pageBuilderStateStore.setHyperlinkMessage(null);
    this.pageBuilderStateStore.setHyberlinkEnable(false);
  }

  handleHyperlink(hyperlinkEnable, urlInput, openHyperlinkInNewTab) {
    if (this.showRunningMethodLogs) {
      console.log('handleHyperlink');
    }

    if (!this.shouldRunMethods()) return;

    this.pageBuilderStateStore.setHyperlinkAbility(true);

    const parentHyperlink = this.getElement.value.closest('a');
    const hyperlink = this.getElement.value.querySelector('a');

    // handle case where parent element already has an a href tag
    // when clicking directly on a hyperlink
    if (parentHyperlink !== null) {
      this.pageBuilderStateStore.setHyperlinkAbility(false);
    }
    //
    const elementTag = this.getElement.value.tagName.toUpperCase();

    if (
      elementTag !== 'P' &&
      elementTag !== 'H1' &&
      elementTag !== 'H2' &&
      elementTag !== 'H3' &&
      elementTag !== 'H4' &&
      elementTag !== 'H5' &&
      elementTag !== 'H6'
    ) {
      this.pageBuilderStateStore.setHyperlinkAbility(false);
    }

    if (hyperlinkEnable === undefined) {
      this.#checkForHyperlink(hyperlinkEnable, urlInput, openHyperlinkInNewTab);
      return;
    }

    this.#addHyperlinkToElement(
      hyperlinkEnable,
      urlInput,
      openHyperlinkInNewTab
    );
  }

  handlePageBuilderMethods() {
    if (!this.shouldRunMethods()) return;

    this.pageBuilderStateStore.setParentElement(null);
    this.pageBuilderStateStore.setRestoredElement(null);

    // handle custom URL
    this.handleHyperlink();
    // handle opacity
    this.handleOpacity();
    // handle BG opacity
    this.handleBackgroundOpacity();
    // displayed image
    this.showBasePrimaryImage();
    // border style
    this.handleBorderStyle();
    // border width
    this.handleBorderWidth();
    // border color
    this.handleBorderColor();
    // border radius
    this.handleBorderRadiusGlobal();
    // border radius
    this.handleBorderRadiusTopLeft();
    // border radius
    this.handleBorderRadiusTopRight();
    // border radius
    this.handleBorderRadiusBottomleft();
    // border radius
    this.handleBorderRadiusBottomRight();
    // handle font size
    this.handleFontSize();
    // handle font weight
    this.handleFontWeight();
    // handle font family
    this.handleFontFamily();
    // handle font style
    this.handleFontStyle();
    // handle vertical padding
    this.handleVerticalPadding();
    // handle horizontal padding
    this.handleHorizontalPadding();
    // handle vertical margin
    this.handleVerticalMargin();
    // handle horizontal margin
    this.handleHorizontalMargin();
    // handle color
    this.handleBackgroundColor();
    // handle text color
    this.handleTextColor();
    // handle classes
    this.currentClasses();
  }

  /**
   * Markiert alle einzeln bearbeitbaren Text-Elemente in einer Komponente
   * @param {HTMLElement} component - Die zu bearbeitende Komponente
   */
  markTextElementsInComponent(component) {
    if (!component) return;
    
    // Finde das eigentliche Container-Element in der Komponente
    const container = component.querySelector('[id="page-builder-editor-editable-area"]');
    if (!container) return;
    
    markEditableTextElements(container);
  }

  /**
   * Wählt ein einzelnes Text-Element zur Bearbeitung aus
   * @param {Event} event - Das Event, das die Textauswahl auslöst
   * @param {HTMLElement} element - Das zu bearbeitende Text-Element
   */
  handleTextElementClick = (event, element) => {
    if (this.showRunningMethodLogs) {
      console.log('handleTextElementClick');
    }

    event.stopPropagation();
    
    // Sicherstellen, dass es sich um ein bearbeitbares Text-Element handelt
    if (!element.hasAttribute('data-editable-text')) return;

    selectTextElementForEditing(element, this.pageBuilderStateStore);
    
    // Öffne den Text-Editor für dieses Element
    this.pageBuilderStateStore.setShowModalTipTap(true);
  };

  /**
   * Hebt die Auswahl eines Text-Elements auf und entfernt die Markierung
   */
  clearTextElementSelection() {
    if (this.showRunningMethodLogs) {
      console.log('clearTextElementSelection');
    }
    
    clearTextElementSelection(this.pageBuilderStateStore);
  }

  /**
   * Aktualisiert den Inhalt eines bestimmten Text-Elements und hebt die Auswahl auf
   * @param {string} content - Der neue Inhalt
   * @param {HTMLElement} element - Optional: Das zu aktualisierende Element, falls nicht im Store
   */
  updateTextElementContent(content, element = null) {
    if (this.showRunningMethodLogs) {
      console.log('updateTextElementContent');
    }

    const targetElement = element || this.pageBuilderStateStore.getSelectedTextElement;
    
    if (!targetElement) return;
    
    // Hole die ursprünglichen Tag-Informationen aus dem Store
    const originalTag = this.pageBuilderStateStore.getSelectedElementOriginalTag;
    
    // Aktualisiere den Inhalt und behalte die Struktur bei
    const updatedElement = updateTextElementContent(targetElement, content, originalTag);
    
    // Hebe die Auswahl auf
    this.clearTextElementSelection();
    
    return updatedElement;
  }
}

const pageBuilder = new PageBuilder();

export default PageBuilder;