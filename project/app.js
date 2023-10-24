const http = require('http');
const fs = require('fs');
const { log } = require('console');
const path = require('path');
const url = require('url');
const users = require('./users');
const {staticFile} = require('./javascript/utils');
const mimeTypes = require('./javascript/mimeTypes');

log('Server works');


http.createServer(function(req, res) {
    let url = req.url;
    log(url);
    switch (url) {
        case '/':
            staticFile(res, '/main.html', '.html');
            break;
        case '/about':
            staticFile(res, '/about.html', '.html');
            break;
        default:
            const extname = String(path.extname(url)).toLocaleLowerCase();
            if (extname in mimeTypes)  {
                staticFile(res, url, extname);
            } else {
                res.statusCode = 404;
                res.end;
            }

    }
}).listen(3500);

