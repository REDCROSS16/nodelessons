const checkPath = require('./checkPath')
const fileInfo = require('./fileInfo')

const {getFilename, getExtension, parse, getDirname, readDir} = require('./getFilename');

module.exports = {
    'checkPath': checkPath,
    'fileInfo': fileInfo,
    'getFilename': getFilename,
    'getExtension': getExtension,
    'parse': parse,
    'getDirname': getDirname,
    'readDir': readDir
}