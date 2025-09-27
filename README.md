# Synopsis

NMRLipids Databank -- A portal for visualization of molecular simulations


## System Dependencies

 * PHP >= 8.3
 * composer
 * php-mysql MySQL-client/server >= 8
 * php-pdo
 * php-xml
 * php-gd
 * This version is compatible with Laravel 12 (will be installed by composer)

## Installation

* clone this repository
* ``` cd BilayerGUI_laravel ```
* Install the PHP dependencies with ``` composer install ```
* Install more dependencies with ``` npm install && npm install node ```
* Create a .env configuration file from the example environment ``` cp .env-example .env ```
* Create the pp key: ``` php artisan key:generate ```
* Create the database (default database name and user is ```laravel```) and set privileges
* Edit the configuration file ```.env``` and add the database credentials and key
* Optionally: configure server host and port (default: localhost9000)
* Create the database: ``` php artisan migrate ```
* To create a link to display resources in ```storage/``` : ``` php artisan storage:link ```
* This will set up a system with a MySQL database with the NMRLipids schema (default db name laravel), but with empty tables (and a schema not in sync with the current DB export).
* To import the current test set of trajectories: ``` mysql -p laravel -u laravel < nmrlipid_trajectdb.sql ```
* If this fails, retry:
  
```
php artisan migrate:fresh
mysql -p laravel -u laravel < nmrlipid_trajectdb.sql
``` 

## Usage

### Using the built-in dev server

* ``` composer run dev ```

### Using Laravel's native Docker environment

* You can use Laravel Sail to set up docker integration
* Clone the repository
* You need only composer, and Docker on the target machine
* Create a customized ``` .env ``` file
* start Docker
* Follow the instructions to install and run Sail here https://laravel.com/docs/12.x/sail
  * ``` composer require laravel/sail --dev ```
  * ``` php artisan sail:install --devcontainer ``` 
* ``` ./vendor/bin/sail up -d ``` to start the container in demon-mode 
* commands to the Docker instance can be issued using sail e.g.
```
./vendor/bin/sail bash
./vendor/bin/sail mysql
```
* Update the application key: ``` ./vendor/bin/sail  artisan key:generate ```
* Create a fresh database: ``` ./vendor/bin/sail artisan migrate:fresh  ```
* Populate the database with the example data: ``` ./vendor/bin/sail mysql -p laravel < nmrlipid_trajectdb.sql ```
* Create the link to the storage-directory: ```  ./vendor/bin/sail artisan storage:link ``` 
* The web server will by default be accessible at http://localhost:80

