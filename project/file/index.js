const fs = require('fs');
const path = require('path');

function checkPath() {
    const pathToDir = './test';

    if (fs.existsSync(pathToDir)) {

        return true;
    }
    
    return false;
}