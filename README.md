# Stackoverflow-Backend & Api platfrom

Hier werden die Fragen nach neuen Fragen gefiltert und gezeigt.

Es gibt auch popular Answers, dh. meiseten gute votes haben werden auch erst gezeigt.

Die suche funktioniert so -> answer und question

There is Vote system and Comments for Answers "there is no vote for Comments"

There is User Authenticators.

Next Goal is API platfrom Security -> for admin roles and user roles

### for new install

```
composer upgrade
composer install
npm install
php bin/console cache:clear
npm run build
```

### symfony server

```
symfony server:start -d
symfony server:stop
symfony server:start -d  --allow-http
php -S 127.0.0.1:8000 -t public
```

### run server

```
npm run watch
```

### after install to run and database

```
docker-compose up -d
docker-compose ps

symfony console doctrine:make:migration

symfony console doctrine:database:drop --force
symfony console doctrine:database:create

symfony console doctrine:migrations:migrate
symfony console doctrine:fixtures:load

```
