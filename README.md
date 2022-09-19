# Stackoverflow-Backend & Api platfrom

Hier werden die Fragen nach neuen Fragen gefiltert und gezeigt.

Es gibt auch popular Answers, dh. meiseten gute votes haben werden auch erst gezeigt.

Die suche funktioniert so -> answer und question

There is Vote system and Comments for Answers "there is no vote for Comments"

### symfony server
```
composer install
symfony server:start -d 
symfony server:stop
symfony server:start -d  --allow-http
php -S 127.0.0.1:8000 -t public
```

### run server
```
npm run watch
```

### for new install
```
composer install
npm install
php bin/console cache:clear
npm run build
```
