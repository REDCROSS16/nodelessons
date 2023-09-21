const fs = require('fs');

// const pathToFile = 'file.json';
//read json
// let json = fs.readFileSync(pathToFile);
// let data = JSON.parse(json)

// console.log(data.age);


const object = {
    "name" : "Alexander",
    "surname" : "Podolnitsky",
    "skill": ["php", "js"]
}

fs.writeFileSync('test.json', JSON.stringify(object), {encoding: 'utf8', flag: 'w'});