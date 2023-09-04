const fs = require('fs');
const path = require('path');

function checkPath(path) {
    const pathToDir = './' + path;

    return fs.existsSync(pathToDir) ? true : false;
}


function fileInfo(path) {
    const pathToDir = './' + path;

    return checkPath(path) ? fs.statSync(pathToDir) : null;
}