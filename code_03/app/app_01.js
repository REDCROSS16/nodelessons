const { log } = require('console');
const fs = require('fs')

const pathToFile = '1.txt';

// const data = fs.readFileSync(pathToFile, {encoding:'utf8', flag:'r'}) 


let arrayOfData = data.split('\n');

arrayOfData = arrayOfData.filter(line => line.trim() !== '');

console.log(arrayOfData)