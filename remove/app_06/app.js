const { log } = require('console');
const http = require('http');
const fs = require('fs');
const path = require('path');
const PORT = 3500;

const mimeTypes = require('./modules/mimetypes');

http.createServer(function(req, res) {
    const url = req.url;

    switch(url) {
        case '/':
            //  main page
            res.write('<h1>Main page</h1>');
            res.end();
            break;
        case '/contact':
            // contact page
            log('contact page');
            staticFile(res, '/contact.html', '.html');
            break;
        default:
            // default routes
            const extName = String(path.extname(url)).toLocaleLowerCase();
            if (extName in mimeTypes) {
                staticFile(res, url, extName);
            } else {
                res.statusCode = 404;
                res.end();
            }
    }
}).listen(PORT);


function staticFile(res, filePath, ext) {
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