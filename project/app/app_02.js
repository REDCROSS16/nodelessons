const fs = require('fs');


const text = 'abcd\r 1234\r dadasd'
const pathToFile = 'new.txt';

// write to file
fs.writeFileSync(pathToFile, text, {encoding: 'utf8', flag: 'a'});