/**
 * Dynamic Content Parser
 * 
 * Parses and processes dynamic content syntax like {{my_content_table.column[index]}}
 * Used in the Web Builder to display dynamic content from content tables.
 * 
 * Syntax: {{table_name.column_name[index]}}
 * Example: {{my_content_table.title[0]}} - Gets the first entry from 'title' column in 'my_content_table'
 */

// Regex pattern to match dynamic content syntax
const DYNAMIC_CONTENT_REGEX = /\{\{([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*(\d+)\s*\]\}\}/g;

// Single match pattern (without global flag)
const DYNAMIC_CONTENT_REGEX_SINGLE = /\{\{([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*(\d+)\s*\]\}\}/;

/**
 * Parse a single dynamic content reference
 * @param {string} text - Text containing the dynamic content syntax
 * @returns {Object|null} - Parsed reference or null if not valid
 */
export function parseDynamicContentReference(text) {
  const match = text.match(DYNAMIC_CONTENT_REGEX_SINGLE);
  if (!match) return null;
  
  return {
    fullMatch: match[0],
    tableName: match[1],
    columnName: match[2],
    index: parseInt(match[3], 10),
    displayText: `${match[1]}.${match[2]}[${match[3]}]`
  };
}

/**
 * Find all dynamic content references in text
 * @param {string} text - Text to search
 * @returns {Array} - Array of parsed references
 */
export function findAllDynamicContentReferences(text) {
  const references = [];
  let match;
  
  // Reset regex lastIndex
  DYNAMIC_CONTENT_REGEX.lastIndex = 0;
  
  while ((match = DYNAMIC_CONTENT_REGEX.exec(text)) !== null) {
    references.push({
      fullMatch: match[0],
      tableName: match[1],
      columnName: match[2],
      index: parseInt(match[3], 10),
      displayText: `${match[1]}.${match[2]}[${match[3]}]`,
      startIndex: match.index,
      endIndex: match.index + match[0].length
    });
  }
  
  return references;
}

/**
 * Check if text contains any dynamic content references (syntax or badges)
 * @param {string} text - Text to check
 * @returns {boolean}
 */
export function hasDynamicContent(text) {
  if (!text || typeof text !== 'string') return false;
  DYNAMIC_CONTENT_REGEX.lastIndex = 0;
  // Check for both syntax {{...}} and badges
  return DYNAMIC_CONTENT_REGEX.test(text) || text.includes('data-cc-dynamic') || text.includes('cc-dynamic-badge');
}

/**
 * Check if text contains dynamic content SYNTAX (not badges)
 * @param {string} text - Text to check
 * @returns {boolean}
 */
export function hasDynamicContentSyntax(text) {
  if (!text || typeof text !== 'string') return false;
  DYNAMIC_CONTENT_REGEX.lastIndex = 0;
  return DYNAMIC_CONTENT_REGEX.test(text);
}

/**
 * Check if HTML contains dynamic content badges
 * @param {string} html - HTML to check
 * @returns {boolean}
 */
export function hasDynamicContentBadges(html) {
  if (!html || typeof html !== 'string') return false;
  return html.includes('data-cc-dynamic') || html.includes('cc-dynamic-badge');
}

/**
 * Convert dynamic content syntax to display badges in HTML
 * @param {string} html - HTML content with dynamic syntax
 * @param {Object} contentData - Optional: Content data to validate against
 * @returns {string} - HTML with badges instead of syntax
 */
export function convertDynamicContentToBadges(html, contentData = null) {
  if (!html || typeof html !== 'string') return html;
  
  DYNAMIC_CONTENT_REGEX.lastIndex = 0;
  
  return html.replace(DYNAMIC_CONTENT_REGEX, (match, tableName, columnName, index) => {
    const displayText = `${tableName}.${columnName}[${index}]`;
    
    // Determine badge status - green if data exists, red if not
    let isValid = false;
    if (contentData) {
      isValid = validateDynamicReference(tableName, columnName, parseInt(index, 10), contentData);
    }
    
    const badgeClass = isValid 
      ? 'cc-dynamic-badge cc-dynamic-badge-valid' 
      : 'cc-dynamic-badge cc-dynamic-badge-invalid';
    
    // Return the badge HTML with data attributes for later processing
    return `<span class="${badgeClass}" data-cc-dynamic="true" data-cc-table="${tableName}" data-cc-column="${columnName}" data-cc-index="${index}" contenteditable="false">${displayText}</span>`;
  });
}

/**
 * Convert badges back to dynamic content syntax
 * @param {string} html - HTML with badges
 * @returns {string} - HTML with original syntax
 */
export function convertBadgesToDynamicContent(html) {
  if (!html || typeof html !== 'string') return html;
  
  // Create a temporary container to parse the HTML
  const tempContainer = document.createElement('div');
  tempContainer.innerHTML = html;
  
  // Find all dynamic content badges
  const badges = tempContainer.querySelectorAll('[data-cc-dynamic="true"]');
  
  badges.forEach(badge => {
    const tableName = badge.getAttribute('data-cc-table');
    const columnName = badge.getAttribute('data-cc-column');
    const index = badge.getAttribute('data-cc-index');
    
    if (tableName && columnName && index !== null) {
      // Create the original syntax
      const syntax = `{{${tableName}.${columnName}[${index}]}}`;
      
      // Replace the badge with a text node containing the syntax
      const textNode = document.createTextNode(syntax);
      badge.parentNode.replaceChild(textNode, badge);
    }
  });
  console.log('Converted badges back to dynamic content syntax.', tempContainer.innerHTML);
  return tempContainer.innerHTML;
}

/**
 * Validate a dynamic content reference against content data
 * @param {string} tableName - Content table name
 * @param {string} columnName - Column name
 * @param {number} index - Array index
 * @param {Object} contentData - Content data object
 * @returns {boolean} - True if reference is valid
 */
export function validateDynamicReference(tableName, columnName, index, contentData) {
  if (!contentData || typeof contentData !== 'object') return false;
  
  // Check if table exists
  if (!contentData[tableName]) return false;
  
  const tableData = contentData[tableName];
  
  // Check if table has data array
  if (!Array.isArray(tableData.data)) return false;
  
  // Check if index is within bounds
  if (index < 0 || index >= tableData.data.length) return false;
  
  // Check if column exists in the first row (schema check)
  if (!tableData.columns || !tableData.columns.includes(columnName)) {
    // Alternative: Check if the column exists in the data row
    const row = tableData.data[index];
    if (!row || !(columnName in row)) return false;
  }
  
  return true;
}

/**
 * Resolve dynamic content to actual values
 * @param {string} html - HTML content with dynamic syntax
 * @param {Object} contentData - Content data object
 * @returns {string} - HTML with resolved values
 */
export function resolveDynamicContent(html, contentData) {
  if (!html || typeof html !== 'string') return html;
  if (!contentData) return html;
  
  DYNAMIC_CONTENT_REGEX.lastIndex = 0;
  
  return html.replace(DYNAMIC_CONTENT_REGEX, (match, tableName, columnName, index) => {
    const idx = parseInt(index, 10);
    
    // Try to get the value from content data
    if (contentData[tableName] && 
        Array.isArray(contentData[tableName].data) && 
        contentData[tableName].data[idx] &&
        contentData[tableName].data[idx][columnName] !== undefined) {
      return contentData[tableName].data[idx][columnName];
    }
    
    // Return original match if not found (will show as badge in editor)
    return match;
  });
}

/**
 * Create dynamic content badge HTML for display
 * @param {string} tableName - Table name
 * @param {string} columnName - Column name
 * @param {number} index - Array index
 * @param {boolean} isValid - Whether the reference is valid
 * @returns {string} - Badge HTML
 */
export function createDynamicBadgeHTML(tableName, columnName, index, isValid = false) {
  const displayText = `${tableName}.${columnName}[${index}]`;
  const badgeClass = isValid 
    ? 'cc-dynamic-badge cc-dynamic-badge-valid' 
    : 'cc-dynamic-badge cc-dynamic-badge-invalid';
  
  return `<span class="${badgeClass}" data-cc-dynamic="true" data-cc-table="${tableName}" data-cc-column="${columnName}" data-cc-index="${index}" contenteditable="false">${displayText}</span>`;
}

/**
 * Get CSS styles for dynamic content badges
 * @returns {string} - CSS styles
 */
export function getDynamicBadgeStyles() {
  return `
    .cc-dynamic-badge {
      display: inline-flex;
      align-items: center;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 500;
      font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
      line-height: 1.4;
      white-space: nowrap;
      user-select: none;
      cursor: default;
      margin: 0 2px;
      vertical-align: middle;
    }
    
    .cc-dynamic-badge-valid {
      background-color: #dcfce7;
      color: #166534;
      border: 1px solid #86efac;
    }
    
    .cc-dynamic-badge-invalid {
      background-color: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
    }
    
    .cc-dynamic-badge::before {
      content: "{{";
      margin-right: 2px;
      opacity: 0.6;
    }
    
    .cc-dynamic-badge::after {
      content: "}}";
      margin-left: 2px;
      opacity: 0.6;
    }
  `;
}

export default {
  parseDynamicContentReference,
  findAllDynamicContentReferences,
  hasDynamicContent,
  hasDynamicContentSyntax,
  hasDynamicContentBadges,
  convertDynamicContentToBadges,
  convertBadgesToDynamicContent,
  validateDynamicReference,
  resolveDynamicContent,
  createDynamicBadgeHTML,
  getDynamicBadgeStyles,
  DYNAMIC_CONTENT_REGEX
};
