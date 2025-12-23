/**
 * Utility functions for the customizable header component
 * These functions handle dropdown menus and mobile responsiveness
 */

/**
 * Initialize the header component interactivity
 * @param {HTMLElement} headerElement - The header element container
 */
export function initializeHeader(headerElement) {
  if (!headerElement) return;
  
  setupMobileMenu(headerElement);
  setupDropdowns(headerElement);
}

/**
 * Set up mobile menu toggle functionality
 * @param {HTMLElement} headerElement - The header element container
 */
function setupMobileMenu(headerElement) {
  const mobileMenuButton = headerElement.querySelector('.mobile-menu-button');
  const mobileMenu = headerElement.querySelector('.mobile-menu');
  
  if (!mobileMenuButton || !mobileMenu) return;
  
  mobileMenuButton.addEventListener('click', () => {
    const isHidden = mobileMenu.classList.contains('hidden');
    if (isHidden) {
      mobileMenu.classList.remove('hidden');
    } else {
      mobileMenu.classList.add('hidden');
    }
  });
}

/**
 * Set up dropdown menu toggle functionality
 * @param {HTMLElement} headerElement - The header element container
 */
function setupDropdowns(headerElement) {
  const dropdownContainers = headerElement.querySelectorAll('.dropdown-container');
  
  dropdownContainers.forEach(container => {
    const trigger = container.querySelector('.dropdown-trigger');
    const menu = container.querySelector('.dropdown-menu');
    
    if (!trigger || !menu) return;
    
    // Toggle dropdown on click
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const isHidden = menu.classList.contains('hidden');
      
      // Close all other open dropdowns
      headerElement.querySelectorAll('.dropdown-menu').forEach(m => {
        if (m !== menu) m.classList.add('hidden');
      });
      
      // Toggle this dropdown
      if (isHidden) {
        menu.classList.remove('hidden');
      } else {
        menu.classList.add('hidden');
      }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!container.contains(e.target)) {
        menu.classList.add('hidden');
      }
    });
  });
}

/**
 * Add a new navigation link to the header
 * @param {HTMLElement} headerElement - The header element container 
 * @param {Object} linkData - Data for the new link
 * @param {string} linkData.text - The link text
 * @param {string} linkData.url - The link URL
 * @param {boolean} linkData.active - Whether the link is active
 */
export function addNavigationLink(headerElement, linkData) {
  const navContainer = headerElement.querySelector('[data-header-links]');
  if (!navContainer) return;
  
  const linkClasses = linkData.active 
    ? 'inline-flex items-center border-b-2 border-indigo-500 px-1 pt-1 text-sm font-medium text-gray-900'
    : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700';
  
  const newLink = document.createElement('a');
  newLink.href = linkData.url || '#';
  newLink.className = linkClasses;
  newLink.textContent = linkData.text;
  
  navContainer.appendChild(newLink);
  
  // Also add to mobile menu
  const mobileMenu = headerElement.querySelector('[data-header-mobile] .space-y-1');
  if (mobileMenu) {
    const mobileLinkClasses = linkData.active
      ? 'block border-l-4 border-indigo-500 bg-indigo-50 py-2 pl-3 pr-4 text-base font-medium text-indigo-700'
      : 'block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700';
      
    const newMobileLink = document.createElement('a');
    newMobileLink.href = linkData.url || '#';
    newMobileLink.className = mobileLinkClasses;
    newMobileLink.textContent = linkData.text;
    
    mobileMenu.appendChild(newMobileLink);
  }
}

/**
 * Add a dropdown menu to the header navigation
 * @param {HTMLElement} headerElement - The header element container
 * @param {Object} dropdownData - Data for the new dropdown
 * @param {string} dropdownData.text - The dropdown trigger text
 * @param {Array} dropdownData.items - The dropdown items
 */
export function addDropdownMenu(headerElement, dropdownData) {
  const navContainer = headerElement.querySelector('[data-header-links]');
  if (!navContainer || !dropdownData.items || !dropdownData.items.length) return;
  
  // Create dropdown container
  const dropdownContainer = document.createElement('div');
  dropdownContainer.className = 'relative inline-flex items-center dropdown-container';
  
  // Create dropdown trigger button
  const trigger = document.createElement('button');
  trigger.type = 'button';
  trigger.className = 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 dropdown-trigger';
  trigger.innerHTML = `
    ${dropdownData.text}
    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
    </svg>
  `;
  
  // Create dropdown menu
  const menu = document.createElement('div');
  menu.className = 'dropdown-menu hidden absolute top-full left-0 z-10 mt-3 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none';
  
  // Add dropdown items
  dropdownData.items.forEach(item => {
    const link = document.createElement('a');
    link.href = item.url || '#';
    link.className = 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100';
    link.textContent = item.text;
    menu.appendChild(link);
  });
  
  // Assemble the dropdown
  dropdownContainer.appendChild(trigger);
  dropdownContainer.appendChild(menu);
  navContainer.appendChild(dropdownContainer);
  
  // Add a simple entry to mobile menu (without dropdown functionality)
  const mobileMenu = headerElement.querySelector('[data-header-mobile] .space-y-1');
  if (mobileMenu) {
    const mobileLink = document.createElement('a');
    mobileLink.href = '#';
    mobileLink.className = 'block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700';
    mobileLink.textContent = dropdownData.text;
    mobileMenu.appendChild(mobileLink);
  }
  
  // Setup event listeners for this new dropdown
  setupDropdowns(headerElement);
}

/**
 * Change the logo in the header
 * @param {HTMLElement} headerElement - The header element container
 * @param {Object} logoData - Data for the logo
 * @param {string} logoData.src - The logo image src
 * @param {string} logoData.alt - The logo alt text
 */
export function changeLogo(headerElement, logoData) {
  const logoContainer = headerElement.querySelector('[data-header-logo]');
  if (!logoContainer) return;
  
  const logoImg = logoContainer.querySelector('img');
  if (logoImg) {
    if (logoData.src) logoImg.src = logoData.src;
    if (logoData.alt) logoImg.alt = logoData.alt;
  }
}

/**
 * Add action button to the header
 * @param {HTMLElement} headerElement - The header element container
 * @param {Object} buttonData - Data for the button
 * @param {string} buttonData.text - The button text
 * @param {string} buttonData.url - The button URL (optional)
 * @param {string} buttonData.type - The button type (primary, secondary)
 */
export function addActionButton(headerElement, buttonData) {
  const actionsContainer = headerElement.querySelector('[data-header-actions]');
  if (!actionsContainer) return;
  
  // Determine button style based on type
  let buttonClass = 'rounded-md px-3 py-2 text-sm font-semibold shadow-sm';
  
  if (buttonData.type === 'primary') {
    buttonClass += ' bg-indigo-600 text-white hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600';
  } else if (buttonData.type === 'secondary') {
    buttonClass += ' bg-white text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50';
  }
  
  // Create button element (either <a> or <button>)
  let button;
  if (buttonData.url) {
    button = document.createElement('a');
    button.href = buttonData.url;
  } else {
    button = document.createElement('button');
    button.type = 'button';
  }
  
  button.className = buttonClass;
  button.textContent = buttonData.text;
  
  actionsContainer.appendChild(button);
}