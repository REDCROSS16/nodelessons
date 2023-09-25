const fs = require('fs');
const path = require('path');
const checkPath = require('./checkPath');

module.exports = function(path) {
    const pathToDir = './' + path;

    return checkPath(path) ? fs.statSync(pathToDir) : null;
}