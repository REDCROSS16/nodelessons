const { log } = require('console');
const http = require('http');
const PORT = 3500;

http.createServer(function(req, res) {
    const url = req.url;
    log(url);

    switch (url) {
        case '/':
            log('Main page');
            res.write('<h1>Main page</h1>');
            break;
        case '/contacts':
            log('contact page');
            res.write('<h1>Contacts</h1>');
            break;
        default:
            log('default page');
            res.write('page 404');        
    }

    res.end();


}).listen(PORT)