# Enuygun Case study

Execute following commands in order to run project
````
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console create:developer --default
php bin/console create:provider --default
````
And finally run `symfony serve` command
````
symfony serve
````
Go to `http://127.0.0.1:8000/page/` for displaying all Provider task in single sequence

Go to `http://127.0.0.1:8000/page/true` for separated Provider tasks sequence