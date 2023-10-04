const { log } = require('console');
const http = require('http');

http.createServer(function(req, res) {
    res.setHeader('Content-type', "text-html; charset=utf-8;");
    res.write('<h2> hello world</h2>');
    res.end();

    

    
}).listen(3500);    