const { log } = require('console');
const http = require('http');
const PORT = 3500;

http.createServer(function(req, res) {
    const url = req.url;
    log(url);


}).listen(PORT)