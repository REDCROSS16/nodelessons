const fs = require('fs');

const pathToFile = 'file.json';
//read json
let json = fs.readFileSync(pathToFile);
let data = JSON.parse(json)

console.log(data.age);
