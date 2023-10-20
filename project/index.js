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

        case '/contacts':
            log('Contacts');
            let data = fs.readFile('./public/contacts/contacts.html', {encoding:'utf-8', flag: 'r'});
            res.write(data);
            break;
        default:
            res.statusCode = 404;
            res.write('<h2> 404</h2>');
    }

}).listen(PORT, HOSTNAME);