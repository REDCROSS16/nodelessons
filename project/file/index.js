const checkPath = require('./checkPath')
const fileInfo = require('./fileInfo')
const {getFilename, getExtension} = require('./getFilename');

module.exports = {
    'checkPath': checkPath,
    'fileInfo': fileInfo,
    'getFilename': getFilename,
    'getExtension': getExtension
}