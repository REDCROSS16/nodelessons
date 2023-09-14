const fs = require('fs')

module.exports = function(pathToFile) {
    let data = fs.readFileSync(pathToFile, {encoding:'utf8', flag:'r'});
    let array = data.split(' ');

    return array.reduce((partialSum, a) => Number(partialSum) + Number(a), 0);
}