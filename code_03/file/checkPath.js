const fs = require('fs');
const path = require('path');

module.exports = function checkPath(path) {
    const pathToDir = './' + path;

    return fs.existsSync(pathToDir) ? true : false;
}