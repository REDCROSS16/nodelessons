const http = require('http');
const fs = require('fs');
const { log } = require('console');

const PORT = process.env.PORT;
const HOSTNAME = process.env.HOSTNAME;


http.createServer(function(req, res) {
    const url = req.url;
    log(url);

    switch(url) {
        case '/':
            log('Main')
            res.write('<h1>Main</h1>')
            break;
    }

}).listen(PORT, HOSTNAME);