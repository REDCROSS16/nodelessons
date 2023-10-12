const { log } = require('console');
const http = require('http');
const fs = require('fs');
const PORT = 3500;

http.createServer(function(req, res) {
    const url = req.url;
    log(url);
    res.setHeader('Content-type', "text-html; charset=utf-8;");

    switch (url) {
        case '/':
            log('Main page');
            res.write('<h1>Main page</h1>');
            break;
        case '/contacts':
            log('contact page');
            let data = fs.readFileSync('./public/contact.html', {encoding: 'utf-8', flag: 'r'});
            res.write(data);
            break;
        case '/login':
            log('log into')
            break    
        default:
            log('default page');
            res.write('page 404');        
    }

    res.end();


}).listen(PORT)