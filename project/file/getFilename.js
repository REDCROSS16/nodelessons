const path = require('path');
const fs = require('fs');
const { log } = require('console');

module.exports.getFilename = function (filepath) {
    return path.basename(filepath);
}

module.exports.getExtension = function(filepath) {
    return path.extname(filepath);
}

module.exports.getDirname = function(filepath) {
    return path.dirname(filepath);
}

module.exports.parse = function(filepath) {
    return path.parse(filepath);
}

module.exports.readDir = function(filepath) {
    const allFiles = fs.readFileSync(filepath);

    let out = '';
    allFiles.forEach(item => out += item + '\n');

    return out;
}

const directoryPath = path.join(__dirname);

// write