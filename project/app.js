const { log } = require('console');
const http = require('http');

http.createServer(function(req, res) {
    log('server work');

    res.end('1');
}).listen(3500);