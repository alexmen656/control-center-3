// Alias index for apis directory
// This allows both 'require("apis")' and 'require("apis/index")'

/*module.exports = require('../.monaco_apis/index.js');

// For ES6 imports
const monacoExports = require('../.monaco_apis/index.js');
module.exports.default = monacoExports.default || monacoExports;

// Re-export everything
if (monacoExports && typeof monacoExports === 'object') {
  Object.keys(monacoExports).forEach(key => {
    if (key !== 'default') {
      module.exports[key] = monacoExports[key];
    }
  });
}
*/

module.exports = require('../.monaco_apis/index.js');
