const fs = require('fs');
const mimeTypes = require('./mimeTypes');

module.exports.staticFile = function (res, filePath, ext) {
    res.setHeader("Content-Type", mimeTypes[ext]);
    fs.readFile('./public' + filePath, (error, data) => {
        if (error) { 
            endWith404(res);
        }

        res.end(data)
    });
}

function endWith404(res) {
    res.statusCode = 404;
    res.end();
}