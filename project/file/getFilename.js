const path = require('path');


module.exports.getFilename = function (filepath) {
    return path.basename(filepath);
}

module.exports.getExtension = function(filepath) {
    return path.extname(filepath);
}