const { log } = require('console');
const http = require('http');

http.createServer(function(req, res) {
    log(req.url);
    log(req.method);

    
}).listen(3500);