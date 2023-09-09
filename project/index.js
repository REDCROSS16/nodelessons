const {randomInt, randomArray} = require('./random');
const file = require('./file');


console.log(file.getFilename('index.js'));
console.log(file.fileInfo('index.js'))