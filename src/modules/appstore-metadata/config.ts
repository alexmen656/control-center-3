/**
 * App Store Metadata Manager Configuration
 */

export const SUPPORTED_PLATFORMS = [
  { value: 'iOS', label: 'iOS', icon: 'phone-portrait-outline' },
  { value: 'macOS', label: 'macOS', icon: 'laptop-outline' },
  { value: 'tvOS', label: 'tvOS', icon: 'tv-outline' },
  { value: 'watchOS', label: 'watchOS', icon: 'watch-outline' },
  { value: 'visionOS', label: 'visionOS', icon: 'glasses-outline' }
];

export const RELEASE_TYPES = [
  { value: 'manual', label: 'Manuell freigeben' },
  { value: 'afterApproval', label: 'Automatisch nach Genehmigung' },
  { value: 'scheduled', label: 'Geplante Veröffentlichung' }
];

export const APP_STATUSES = [
  { value: 'draft', label: 'Entwurf', color: 'secondary' },
  { value: 'ready_for_submission', label: 'Bereit zur Einreichung', color: 'warning' },
  { value: 'in_review', label: 'In Prüfung', color: 'info' },
  { value: 'approved', label: 'Genehmigt', color: 'success' },
  { value: 'rejected', label: 'Abgelehnt', color: 'danger' },
  { value: 'live', label: 'Live', color: 'success' }
];

export const SCREENSHOT_DISPLAY_TYPES = [
  { value: 'APP_IPHONE_67', label: 'iPhone 6.7"', width: 1290, height: 2796 },
  { value: 'APP_IPHONE_65', label: 'iPhone 6.5"', width: 1284, height: 2778 },
  { value: 'APP_IPHONE_61', label: 'iPhone 6.1"', width: 1179, height: 2556 },
  { value: 'APP_IPHONE_58', label: 'iPhone 5.8"', width: 1170, height: 2532 },
  { value: 'APP_IPHONE_55', label: 'iPhone 5.5"', width: 1242, height: 2208 },
  { value: 'APP_IPHONE_47', label: 'iPhone 4.7"', width: 750, height: 1334 },
  { value: 'APP_IPAD_PRO_3GEN_129', label: 'iPad Pro 12.9"', width: 2048, height: 2732 },
  { value: 'APP_IPAD_PRO_3GEN_11', label: 'iPad Pro 11"', width: 1668, height: 2388 },
  { value: 'APP_DESKTOP', label: 'Mac', width: 2880, height: 1800 },
  { value: 'APP_APPLE_TV', label: 'Apple TV', width: 1920, height: 1080 }
];

export const AGE_RATING_OPTIONS = [
  { value: 'NONE', label: 'Keine' },
  { value: 'INFREQUENT_OR_MILD', label: 'Selten/Mild' },
  { value: 'FREQUENT_OR_INTENSE', label: 'Häufig/Intensiv' }
];

export const KIDS_BAND_OPTIONS = [
  { value: 'NOT_MADE_FOR_KIDS', label: 'Nicht für Kinder' },
  { value: 'FIVE_AND_UNDER', label: '5 Jahre und jünger' },
  { value: 'SIX_TO_EIGHT', label: '6-8 Jahre' },
  { value: 'NINE_TO_ELEVEN', label: '9-11 Jahre' }
];

export const MAX_LENGTHS = {
  subtitle: 30,
  keywords: 100,
  promotional_text: 170,
  description: 4000,
  whats_new: 4000
};

/**
 * Locale to Flag Emoji Mapping
 * All App Store Connect supported locales with their correct flag emojis
 */
export const LOCALE_FLAGS: Record<string, string> = {
  // Arabic
  'ar-SA': '🇸🇦', // Saudi Arabia
  'ar': '🇸🇦',
  
  // Chinese
  'zh-CN': '🇨🇳', // China Simplified
  'zh-TW': '🇹🇼', // Taiwan Traditional
  'zh-HK': '🇭🇰', // Hong Kong Traditional
  
  // Danish
  'da': '🇩🇰',
  'da-DK': '🇩🇰',
  
  // Dutch
  'nl-NL': '🇳🇱', // Netherlands
  'nl-BE': '🇧🇪', // Belgium
  'nl': '🇳🇱',
  
  // English
  'en-US': '🇺🇸', // United States
  'en-GB': '🇬🇧', // United Kingdom
  'en-AU': '🇦🇺', // Australia
  'en-CA': '🇨🇦', // Canada
  'en': '🇺🇸',
  
  // Finnish
  'fi': '🇫🇮',
  'fi-FI': '🇫🇮',
  
  // French
  'fr-FR': '🇫🇷', // France
  'fr-CA': '🇨🇦', // Canada
  'fr': '🇫🇷',
  
  // German
  'de-DE': '🇩🇪', // Germany
  'de-AT': '🇦🇹', // Austria
  'de-CH': '🇨🇭', // Switzerland
  'de': '🇩🇪',
  
  // Greek
  'el': '🇬🇷',
  'el-GR': '🇬🇷',
  
  // Hebrew
  'he': '🇮🇱',
  'he-IL': '🇮🇱',
  
  // Hindi
  'hi': '🇮🇳',
  'hi-IN': '🇮🇳',
  
  // Hungarian
  'hu': '🇭🇺',
  'hu-HU': '🇭🇺',
  
  // Indonesian
  'id': '🇮🇩',
  'id-ID': '🇮🇩',
  
  // Italian
  'it': '🇮🇹',
  'it-IT': '🇮🇹',
  
  // Japanese
  'ja': '🇯🇵',
  'ja-JP': '🇯🇵',
  
  // Korean
  'ko': '🇰🇷',
  'ko-KR': '🇰🇷',
  
  // Malay
  'ms': '🇲🇾',
  'ms-MY': '🇲🇾',
  
  // Norwegian
  'no': '🇳🇴',
  'nb': '🇳🇴',
  'no-NO': '🇳🇴',
  
  // Polish
  'pl': '🇵🇱',
  'pl-PL': '🇵🇱',
  
  // Portuguese
  'pt-PT': '🇵🇹', // Portugal
  'pt-BR': '🇧🇷', // Brazil
  'pt': '🇵🇹',
  
  // Romanian
  'ro': '🇷🇴',
  'ro-RO': '🇷🇴',
  
  // Russian
  'ru': '🇷🇺',
  'ru-RU': '🇷🇺',
  
  // Slovak
  'sk': '🇸🇰',
  'sk-SK': '🇸🇰',
  
  // Spanish
  'es-ES': '🇪🇸', // Spain
  'es-MX': '🇲🇽', // Mexico
  'es-419': '🇲🇽', // Latin America
  'es': '🇪🇸',
  
  // Swedish
  'sv': '🇸🇪',
  'sv-SE': '🇸🇪',
  
  // Thai
  'th': '🇹🇭',
  'th-TH': '🇹🇭',
  
  // Turkish
  'tr': '🇹🇷',
  'tr-TR': '🇹🇷',
  
  // Ukrainian
  'uk': '🇺🇦',
  'uk-UA': '🇺🇦',
  
  // Vietnamese
  'vi': '🇻🇳',
  'vi-VN': '🇻🇳',
  
  // Czech
  'cs': '🇨🇿',
  'cs-CZ': '🇨🇿',
  
  // Croatian
  'hr': '🇭🇷',
  'hr-HR': '🇭🇷',
  
  // Catalan
  'ca': '🇪🇸',
  'ca-ES': '🇪🇸',

  'fil': '🇵🇭',
  'ta': '🇮🇳',
  'zh-Hans': '🇨🇳',
  'zh-Hant': '🇨🇳',
  'bg': '🇧🇬',

};

/**
 * Get flag emoji for a locale
 */
export function getLocaleFlag(locale: string): string {
  return LOCALE_FLAGS[locale] || '🌍';
}
