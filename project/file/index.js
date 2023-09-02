const fs = require('fs');
const path = require('path');

function checkPath() {
    const pathToDir = './test';

    return fs.existsSync(pathToDir) ? true : false;
}

checkPath();