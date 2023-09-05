const fs = require('fs');
const path = require('path');

module.exports = function fileInfo(path) {
    const pathToDir = './' + path;

    return checkPath(path) ? fs.statSync(pathToDir) : null;
}