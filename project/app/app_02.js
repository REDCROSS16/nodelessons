const fs = require('fs');

const sum = require('./module/sum')


const text = 'abcd\r 1234\r dadasd'
const pathToFile = 'new.txt';

// write to file запись в файл асинхронно
// fs.writeFileSync(pathToFile, text, {encoding: 'utf8', flag: 'a'});

// write file from array
let array = ['new', 'book', 'of', 'bobba', 'fett'];

// fs.writeFileSync(pathToFile, array.join('\r'), {encoding:'utf8', flag:'w'})

//read json