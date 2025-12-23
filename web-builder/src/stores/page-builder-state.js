import { defineStore } from 'pinia';
import { useFetch } from '@/composables/vueFetch';
import PageBuilder from '@/composables/PageBuilder';

// Create a dedicated fetch instance for components
const componentsFetch = useFetch();

// API client für Backend-Kommunikation
const api = useFetch();

// Konstanten für localStorage-Keys
const CURRENT_PAGE_ID_KEY = 'webbuilder_current_page_id';

// Initialisiere PageBuilder mit null (wird später aktualisiert)
const pageBuilder = new PageBuilder(null);

export const usePageBuilderStateStore = defineStore('pageBuilderState', {
  state: () => ({
    // Multi-Page Support
    pages: [
      {
        id: '2', // Standard-ID für die erste Seite
        name: 'Startseite',
        slug: 'home',
        components: []
      }
    ],
    currentPageId: '2', // Default page_id auf 2 gesetzt
    projectId: 1, // Default project_id

    componentArrayAddMethod: null,
    localStorageItemName: null,
    localStorageItemNameUpdate: null,
    showModalTipTap: false,
    menuRight: true,
    borderStyle: null,
    borderWidth: null,
    borderColor: null,
    borderRadiusGlobal: null,
    borderRadiusTopLeft: null,
    borderRadiusTopRight: null,
    borderRadiusBottomleft: null,
    borderRadiusBottomRight: null,
    elementContainsHyperlink: null,
    hyperlinkAbility: null,
    hyperlinkInput: null,
    hyperlinkMessage: null,
    hyperlinkError: null,
    hyberlinkEnable: false,
    openHyperlinkinkInNewTab: null,
    opacity: null,
    backgroundOpacity: null,
    textAreaVueModel: null,
    nextSibling: null,
    parentElement: null,
    restoredElement: null,
    currentClasses: [],
    fontVerticalPadding: null,
    fontHorizontalPadding: null,
    fontVerticalMargin: null,
    fontHorizontalMargin: null,
    fontStyle: null,
    fontFamily: null,
    fontWeight: null,
    fontBase: null,
    fontDesktop: null,
    fontTablet: null,
    fontMobile: null,
    backgroundColor: null,
    textColor: null,
    element: null,
    component: null,
    components: [],
    basePrimaryImage: null,
    fetchedComponents: [],
    selectedTextElement: null,
    selectedElementOriginalTag: null,
  }),
  getters: {
    getPages(state) {
      return state.pages;
    },
    getCurrentPageId(state) {
      return state.currentPageId;
    },
    getComponentArrayAddMethod(state) {
      return state.componentArrayAddMethod;
    },
    getLocalStorageItemName(state) {
      return state.localStorageItemName;
    },
    getLocalStorageItemNameUpdate(state) {
      return state.localStorageItemNameUpdate;
    },
    getShowModalTipTap(state) {
      return state.showModalTipTap;
    },
    getMenuRight(state) {
      return state.menuRight;
    },
    getBorderStyle(state) {
      return state.borderStyle;
    },
    getBorderWidth(state) {
      return state.borderWidth;
    },
    getBorderColor(state) {
      return state.borderColor;
    },
    getBorderRadiusGlobal(state) {
      return state.borderRadiusGlobal;
    },
    getBorderRadiusTopLeft(state) {
      return state.borderRadiusTopLeft;
    },
    getBorderRadiusTopRight(state) {
      return state.borderRadiusTopRight;
    },
    getBorderRadiusBottomleft(state) {
      return state.borderRadiusBottomleft;
    },
    getBorderRadiusBottomRight(state) {
      return state.borderRadiusBottomRight;
    },
    getElementContainsHyperlink(state) {
      return state.elementContainsHyperlink;
    },
    getHyperlinkAbility(state) {
      return state.hyperlinkAbility;
    },
    getHyperlinkInput(state) {
      return state.hyperlinkInput;
    },
    getHyperlinkMessage(state) {
      return state.hyperlinkMessage;
    },
    getHyperlinkError(state) {
      return state.hyperlinkError;
    },
    getHyberlinkEnable(state) {
      return state.hyberlinkEnable;
    },
    getOpenHyperlinkInNewTab(state) {
      return state.openHyperlinkinkInNewTab;
    },
    getOpacity(state) {
      return state.opacity;
    },
    getBackgroundOpacity(state) {
      return state.backgroundOpacity;
    },
    getTextAreaVueModel(state) {
      return state.textAreaVueModel;
    },
    getNextSibling(state) {
      return state.nextSibling;
    },
    getParentElement(state) {
      return state.parentElement;
    },
    getRestoredElement(state) {
      return state.restoredElement;
    },
    getCurrentClasses(state) {
      return state.currentClasses;
    },
    getFontStyle(state) {
      return state.fontStyle;
    },
    getFontVerticalPadding(state) {
      return state.fontVerticalPadding;
    },
    getFontHorizontalPadding(state) {
      return state.fontHorizontalPadding;
    },
    getFontVerticalMargin(state) {
      return state.fontVerticalMargin;
    },
    getFontHorizontalMargin(state) {
      return state.fontHorizontalMargin;
    },
    getFontFamily(state) {
      return state.fontFamily;
    },
    getFontWeight(state) {
      return state.fontWeight;
    },
    getFontBase(state) {
      return state.fontBase;
    },
    getFontDesktop(state) {
      return state.fontDesktop;
    },
    getFontTablet(state) {
      return state.fontTablet;
    },
    getFontMobile(state) {
      return state.fontMobile;
    },
    getBackgroundColor(state) {
      return state.backgroundColor;
    },
    getTextColor(state) {
      return state.textColor;
    },
    getElement(state) {
      return state.element;
    },
    getComponent(state) {
      return state.component;
    },
    getComponents(state) {
      return state.components;
    },
    getBasePrimaryImage(state) {
      return state.basePrimaryImage;
    },
    getFetchedComponents(state) {
      return state.fetchedComponents;
    },
    getSelectedTextElement(state) {
      return state.selectedTextElement;
    },
    getSelectedElementOriginalTag(state) {
      return state.selectedElementOriginalTag;
    },
    getProjectId(state) {
      return state.projectId;
    },
  },
  actions: {
    setPages(payload) {
      this.pages = payload;
    },
    /**
     * Speichert die aktuelle Seiten-ID im localStorage
     */
    saveCurrentPageIdToStorage() {
      localStorage.setItem(CURRENT_PAGE_ID_KEY, this.currentPageId);
      console.log('Aktuelle Seiten-ID im Storage gespeichert:', this.currentPageId);
    },
    
    /**
     * Lädt die aktuelle Seiten-ID aus dem localStorage
     * @returns {string|null} Die gespeicherte Seiten-ID oder null, wenn keine existiert
     */
    loadCurrentPageIdFromStorage() {
      const savedPageId = localStorage.getItem(CURRENT_PAGE_ID_KEY);
      console.log('Seiten-ID aus Storage geladen:', savedPageId);
      return savedPageId;
    },
    
    /**
     * Setzt die aktuelle Seiten-ID und speichert sie im localStorage
     * @param {string|number} payload - Die neue Seiten-ID
     */
    setCurrentPageId(payload) {
      this.currentPageId = payload.toString();
      this.saveCurrentPageIdToStorage();
      
      // Aktualisiere auch die PageBuilder-Instanz
      if (pageBuilder) {
        pageBuilder.setCurrentPageId(parseInt(payload));
      }
    },
    setComponentArrayAddMethod(payload) {
      this.componentArrayAddMethod = payload;
    },
    setLocalStorageItemName(payload) {
      this.localStorageItemName = payload;
    },
    setLocalStorageItemNameUpdate(payload) {
      this.localStorageItemNameUpdate = payload;
    },
    setShowModalTipTap(payload) {
      this.showModalTipTap = payload;
    },
    setMenuRight(payload) {
      this.menuRight = payload;
    },
    setBorderStyle(payload) {
      this.borderStyle = payload;
    },
    setBorderWidth(payload) {
      this.borderWidth = payload;
    },
    setBorderColor(payload) {
      this.borderColor = payload;
    },
    setBorderRadiusGlobal(payload) {
      this.borderRadiusGlobal = payload;
    },
    setBorderRadiusTopLeft(payload) {
      this.borderRadiusTopLeft = payload;
    },
    setBorderRadiusTopRight(payload) {
      this.borderRadiusTopRight = payload;
    },
    setBorderRadiusBottomleft(payload) {
      this.borderRadiusBottomleft = payload;
    },
    setBorderRadiusBottomRight(payload) {
      this.borderRadiusBottomRight = payload;
    },
    setElementContainsHyperlink(payload) {
      this.elementContainsHyperlink = payload;
    },
    setHyperlinkAbility(payload) {
      this.hyperlinkAbility = payload;
    },
    setHyperlinkInput(payload) {
      this.hyperlinkInput = payload;
    },
    setHyperlinkMessage(payload) {
      this.hyperlinkMessage = payload;
    },
    setHyperlinkError(payload) {
      this.hyperlinkError = payload;
    },
    setHyberlinkEnable(payload) {
      this.hyberlinkEnable = payload;
    },
    setOpenHyperlinkInNewTab(payload) {
      this.openHyperlinkinkInNewTab = payload;
    },
    setOpacity(payload) {
      this.opacity = payload;
    },
    setBackgroundOpacity(payload) {
      this.backgroundOpacity = payload;
    },
    setTextAreaVueModel(payload) {
      this.textAreaVueModel = payload;
    },
    setNextSibling(payload) {
      this.nextSibling = payload;
    },
    setParentElement(payload) {
      this.parentElement = payload;
    },
    setRestoredElement(payload) {
      this.restoredElement = payload;
    },
    setClass(payload) {
      this.currentClasses = [...this.currentClasses, payload];
    },
    removeClass(payload) {
      this.currentClasses = this.currentClasses.filter(
        (cls) => cls !== payload
      );
    },
    setCurrentClasses(payload) {
      this.currentClasses = Array.from(payload);
    },
    setFontVerticalPadding(payload) {
      this.fontVerticalPadding = payload;
    },
    setFontHorizontalPadding(payload) {
      this.fontHorizontalPadding = payload;
    },
    setFontVerticalMargin(payload) {
      this.fontVerticalMargin = payload;
    },
    setFontHorizontalMargin(payload) {
      this.fontHorizontalMargin = payload;
    },
    setFontStyle(payload) {
      this.fontStyle = payload;
    },
    setFontFamily(payload) {
      this.fontFamily = payload;
    },
    setFontWeight(payload) {
      this.fontWeight = payload;
    },
    setFontBase(payload) {
      this.fontBase = payload;
    },
    setFontDesktop(payload) {
      this.fontDesktop = payload;
    },
    setFontTablet(payload) {
      this.fontTablet = payload;
    },
    setFontMobile(payload) {
      this.fontMobile = payload;
    },
    setBackgroundColor(payload) {
      this.backgroundColor = payload;
    },
    setTextColor(payload) {
      this.textColor = payload;
    },
    setElement(payload) {
      this.element = payload || {};
    },
    setComponent(payload) {
      if (!payload) {
        this.element = null;
        this.component = null;
        pageBuilder.removeHoveredAndSelected(null);
        return;
      }
      this.component = payload || {};
    },
    setComponents(payload) {
      this.components = payload || [];
    },
    setPushComponents(payload) {
      const method = payload.componentArrayAddMethod || 'push';
      this.components[method](payload.component);
    },
    setBasePrimaryImage(payload) {
      if (this.element) {
        this.element.src = payload;
      }
      this.basePrimaryImage = payload;
    },
    setCurrentLayoutPreview(payload) {
      localStorage.setItem('preview', payload);
    },
    setFetchedComponents(payload) {
      this.fetchedComponents = payload;
    },

    async setLoadComponents(data) {
      this.setFetchedComponents({
        fetchedData: null,
        isError: null,
        error: null,
        errors: null,
        isLoading: true,
        isSuccess: null,
      });

      data.search_query = data.search_query || '';
      data.page = data.page || '';

      try {
        // Use the get method from the componentsFetch instance
        await componentsFetch.get('/compis.php', {});
        
        // Now we can safely access the reactive data and state from the fetch result
        const isSuccess = !componentsFetch.error.value;
        
        this.setFetchedComponents({
          fetchedData: componentsFetch.data.value,
          isError: !!componentsFetch.error.value,
          error: componentsFetch.error.value,
          errors: componentsFetch.error.value ? [componentsFetch.error.value] : [],
          isLoading: componentsFetch.loading.value,
          isSuccess: isSuccess,
        });
      } catch (err) {
        console.log(`Error: ${err}`);
        
        this.setFetchedComponents({
          fetchedData: null,
          isError: true,
          error: err.message || 'Unknown error',
          errors: [err.message || 'Unknown error'],
          isLoading: false,
          isSuccess: false,
        });
      }
    },

    setSelectedTextElement(payload) {
      this.selectedTextElement = payload;
    },
    setSelectedElementOriginalTag(payload) {
      this.selectedElementOriginalTag = payload;
    },
    setProjectId(payload) {
      this.projectId = payload;
    },

    // Überarbeitete Page Management-Methoden, die mit dem Backend kommunizieren
    async fetchPagesFromBackend() {
      try {
        const pagesFromBackend = await api.get('pages.php', { project_id: this.projectId });
        
        if (pagesFromBackend && Array.isArray(pagesFromBackend)) {
          // Konvertieren der Backend-Daten in das Format, das das Frontend erwartet
          const formattedPages = pagesFromBackend.map(page => ({
            id: page.id.toString(),
            name: page.name,
            slug: page.slug,
            title: page.title || page.name,
            meta_description: page.meta_description || '',
            is_home: page.is_home === '1' || page.is_home === 1,
            components: [] // Komponenten werden separat geladen
          }));
          
          // Aktualisiere den Pages-State
          this.pages = formattedPages;
          
          // Versuche die gespeicherte Seiten-ID zu laden
          const savedPageId = this.loadCurrentPageIdFromStorage();
          
          // Überprüfe, ob die gespeicherte ID existiert und zu einer der geladenen Seiten gehört
          let usePageId = savedPageId;
          if (!savedPageId || !this.pages.some(p => p.id === savedPageId)) {
            // Falls keine gültige ID gespeichert ist, nimm die Home-Seite oder die erste Seite
            const homePage = formattedPages.find(p => p.is_home) || formattedPages[0];
            usePageId = homePage ? homePage.id : '2'; // Fallback auf ID 2
          }
          
          // Setze die aktuelle Seiten-ID
          this.setCurrentPageId(usePageId);
          
          // Lade Komponenten für diese Seite
          await pageBuilder.loadComponentsFromBackend();
          this.components = pageBuilder.getComponents.value || [];
          
          return true;
        }
        return false;
      } catch (error) {
        console.error('Fehler beim Laden der Seiten vom Backend:', error);
        return false;
      }
    },
    
    async addPage(name, slug = null) {
      try {
        const newSlug = slug || name.toLowerCase().replace(/\s+/g, '-');
        
        // Erstelle Seite im Backend
        const newPage = await api.post('pages.php', {
          name: name,
          slug: newSlug,
          project_id: this.projectId,
          title: name,
          is_home: 0 // Neue Seiten sind standardmäßig keine Home-Seiten
        });
        
        if (newPage && newPage.id) {
          // Konvertiere die neue Seite ins Frontend-Format und füge sie hinzu
          const formattedPage = {
            id: newPage.id.toString(),
            name: newPage.name,
            slug: newPage.slug,
            title: newPage.title || newPage.name,
            meta_description: newPage.meta_description || '',
            is_home: newPage.is_home === '1' || newPage.is_home === 1,
            components: []
          };
          
          this.pages.push(formattedPage);
          return formattedPage.id;
        }
        return null;
      } catch (error) {
        console.error('Fehler beim Erstellen einer neuen Seite:', error);
        return null;
      }
    },
    
    async updatePageName(id, name) {
      const page = this.pages.find(p => p.id === id);
      if (!page) return false;
      
      try {
        // Update page in backend
        const result = await api.put('pages.php', { name }, { id: id });
        
        if (result) {
          // Update local state if backend update successful
          page.name = name;
          return true;
        }
        return false;
      } catch (error) {
        console.error('Fehler beim Aktualisieren des Seitennamens:', error);
        return false;
      }
    },
    
    async deletePage(id) {
      // Verhindere Löschen der letzten Seite
      if (this.pages.length <= 1) return false;
      
      try {
        // Delete page in backend
        const result = await api.del('pages.php', { id: id });
        
        if (result && result.success) {
          // If successful, update local state
          const index = this.pages.findIndex(p => p.id === id);
          if (index !== -1) {
            this.pages.splice(index, 1);
            
            // Falls die aktuelle Seite gelöscht wird, wechsle zu einer anderen
            if (this.currentPageId === id) {
              this.currentPageId = this.pages[0].id;
              await this.switchToPage(this.currentPageId);
            }
            return true;
          }
        }
        return false;
      } catch (error) {
        console.error('Fehler beim Löschen der Seite:', error);
        return false;
      }
    },
    
    async switchToPage(id) {
      console.log('Wechsle zu Seite:', id);
      
      // Setze die neue Seiten-ID
      this.setCurrentPageId(id);
      
      try {
        // Lade Komponenten der neuen Seite vom Backend
        await pageBuilder.loadComponentsFromBackend();
        
        // Aktualisiere die Komponenten im Store
        const loadedComponents = pageBuilder.getComponents.value || [];
        console.log('Komponenten für Seite', id, 'geladen:', loadedComponents.length);
        
        // Explizit die Komponenten im Store aktualisieren
        this.components = [...loadedComponents];
        
        // Reset Auswahlen
        this.element = null;
        this.component = null;
        pageBuilder.removeHoveredAndSelected(null);
        
        // Event-Listener für die neuen Komponenten setzen (nach dem DOM-Update)
        setTimeout(() => {
          pageBuilder.setEventListenersForElements();
        }, 100);
        
        return true;
      } catch (error) {
        console.error('Fehler beim Wechseln der Seite:', error);
        return false;
      }
    },

    // Ersetze die alten localStorage-Methoden durch Backend-kompatible Methoden
    async saveToBackend() {
      // Speichere aktuelle Komponenten im Backend
      await pageBuilder.saveComponentsToBackend();
      return true;
    },
    
    async loadFromBackend() {
      // Lade die Seiten vom Backend
      const pagesLoaded = await this.fetchPagesFromBackend();
      return pagesLoaded;
    },

    // Add missing methods to bridge localStorage and backend API
    async areComponentsStoredInLocalStorage() {
      // Delegate to PageBuilder's method
      return pageBuilder.areComponentsStoredInLocalStorage();
    },

    async areComponentsStoredInLocalStorageUpdate() {
      // Delegate to PageBuilder's method
      return pageBuilder.areComponentsStoredInLocalStorageUpdate();
    },
    
    // Alte Methoden beibehalten für Abwärtskompatibilität, aber auf Backend umleiten
    saveToLocalStorage() {
      return this.saveToBackend();
    },
    
    loadFromLocalStorage() {
      return this.loadFromBackend();
    },
    
    saveCurrentPageComponents() {
      return this.saveToBackend();
    },
    
    loadCurrentPageComponents() {
      return this.switchToPage(this.currentPageId);
    }
  },
});
