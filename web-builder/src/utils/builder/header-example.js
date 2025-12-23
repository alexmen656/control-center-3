/**
 * Beispiele für die Anpassung des Headers
 * 
 * Dieses File zeigt, wie der Header programmgesteuert angepasst werden kann
 */

import { 
  initializeHeader, 
  addNavigationLink, 
  addDropdownMenu, 
  changeLogo, 
  addActionButton 
} from './header-component-utils';

/**
 * Beispiel: Header vollständig anpassen
 * @param {HTMLElement} headerElement - Das Header-Element aus dem DOM
 */
export function customizeHeaderExample(headerElement) {
  if (!headerElement) return;

  // Logo ändern
  changeLogo(headerElement, {
    src: '/logo/logo.png',
    alt: 'Mein neues Logo'
  });

  // Alle bestehenden Links entfernen (optional)
  const navLinks = headerElement.querySelector('[data-header-links]');
  if (navLinks) {
    navLinks.innerHTML = '';
  }

  // Neue Links hinzufügen
  addNavigationLink(headerElement, {
    text: 'Startseite',
    url: '#home',
    active: true  // Aktiver Link wird hervorgehoben
  });

  addNavigationLink(headerElement, {
    text: 'Über uns',
    url: '#about'
  });

  addNavigationLink(headerElement, {
    text: 'Leistungen',
    url: '#services'
  });

  // Dropdown-Menü hinzufügen
  addDropdownMenu(headerElement, {
    text: 'Produkte',
    items: [
      { text: 'Produkt A', url: '#productA' },
      { text: 'Produkt B', url: '#productB' },
      { text: 'Produkt C', url: '#productC' }
    ]
  });

  // Weiteres Dropdown-Menü hinzufügen
  addDropdownMenu(headerElement, {
    text: 'Ressourcen',
    items: [
      { text: 'Blog', url: '#blog' },
      { text: 'Downloads', url: '#downloads' },
      { text: 'Dokumentation', url: '#docs' }
    ]
  });

  // Action-Button hinzufügen
  addActionButton(headerElement, {
    text: 'Kontakt',
    type: 'primary'
  });

  addActionButton(headerElement, {
    text: 'Anmelden',
    url: '#login',
    type: 'secondary'
  });
}

/**
 * Beispiel: Nur einen neuen Link zum Header hinzufügen
 * @param {HTMLElement} headerElement - Das Header-Element aus dem DOM
 */
export function addSingleNavLink(headerElement) {
  if (!headerElement) return;

  addNavigationLink(headerElement, {
    text: 'Neuer Link',
    url: '#new-link'
  });
}

/**
 * Beispiel: Ein Dropdown-Menü hinzufügen
 * @param {HTMLElement} headerElement - Das Header-Element aus dem DOM
 */
export function addNewDropdown(headerElement) {
  if (!headerElement) return;

  addDropdownMenu(headerElement, {
    text: 'Neues Dropdown',
    items: [
      { text: 'Option 1', url: '#option1' },
      { text: 'Option 2', url: '#option2' },
      { text: 'Option 3', url: '#option3' }
    ]
  });
}