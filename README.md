[![Laravel CI](https://github.com/NMRLipids/BilayerUI_laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/NMRLipids/BilayerUI_laravel/actions/workflows/ci.yml)
[![Laravel CD](https://github.com/NMRLipids/BilayerUI_laravel/actions/workflows/cd_test.yml/badge.svg)](https://github.com/NMRLipids/BilayerUI_laravel/actions/workflows/cd_test.yml)
[![Laravel CD](https://github.com/NMRLipids/BilayerUI_laravel/actions/workflows/cd_prod.yml/badge.svg)](https://github.com/NMRLipids/BilayerUI_laravel/actions/workflows/cd_prod.yml)
[![Laravel CD](https://github.com/NMRLipids/BilayerUI_laravel/actions/workflows/update_db.yml/badge.svg)](https://github.com/NMRLipids/BilayerUI_laravel/actions/workflows/update_db.yml)
# Synopsis

FAIRMDLipids Databank -- A portal for visualization of molecular simulations

FAIRMD Lipids supports MCP — see the [MCP server description](app/Mcp/README.md) for AI-assistant access to the databank.


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
* ``` cd BilayerUI_laravel ```
* Install the PHP dependencies with ``` composer install ```
* Install more dependencies with ``` npm install && npm install node ```
* Create a .env configuration file from the example environment ``` cp .env-example .env ```
* Create the pp key: ``` php artisan key:generate ```
* Create the database (default database name and user is ```laravel```) and set privileges
* Edit the configuration file ```.env``` and add the database credentials and key
* Optionally: configure server host and port (default: localhost9000)
* Create the database: ``` php artisan migrate ```
* To create a link to display resources in ```storage/``` : ``` php artisan storage:link ```
* This will set up a system with a MySQL database with the NMRLipids schema (default db name laravel), and empty tables.
* ``` npm run build ``` to set up resources managed by Vite


### Using the built-in dev server:

* ``` composer run dev ```

### Using Laravel's native Docker environment

* You can use Laravel Sail to set up Docker integration
* Clone the repository
* You need only composer, and Docker on the hosting computer
* Create a customized ``` .env ``` file
* start Docker
* Follow the instructions to install and run Sail here https://laravel.com/docs/12.x/sail
  * ``` composer require laravel/sail --dev ``` --dev is optional
  * ``` php artisan sail:install --devcontainer ``` (The dev-container can be used by your IDE)
* ``` ./vendor/bin/sail up ``` to start the container
* commands to the Docker instance can be issued using sail e.g.
```
./vendor/bin/sail bash
./vendor/bin/sail mysql
```
* Update the application key: ``` ./vendor/bin/sail  artisan key:generate ```
* Create a fresh empty database: ``` ./vendor/bin/sail artisan migrate:fresh  ```
* Create the link to the storage-directory: ```  ./vendor/bin/sail artisan storage:link ```
*  ``` /vendor/bin/sail npm run dev ``` set up the Vite server
* The web server will by default be accessible at http://localhost:80

### Populating the database

* The database can be populated using the [FAIRMD Python Modules](https://github.com/NMRLipids/FAIRMD_lipids.git)
* Install and set up the Python module and the [BilayerData](https://github.com/NMRLipids/BilayerData)
* Set up the environment pointing to a folder containing the BilayerData to display
* ``` cd BilayerUI_laravel/Python ```
* Set up a config.json file with your DB connection details:

  ```
   {
      "host": "127.0.0.1",
      "port": 3306,
      "user": "laravel",
      "password": "your_db_password",
      "database": "laravel"  
   }
  ```
  
* Import Data:

  ```
  artisan migrate:fresh # prefix ../vendor/bin/sail if running via sail 
  ./UI_DB_Update.py -c config.json
  ```
 * Check the output for any error messages.
 * All data required for display are now stored in the DB and you can delete the BilayerData if you do not need them

### Running function tests

Tests are run automatically as a CI workflow. You can run tests manually by:

```
artisan test
```
If running via sail:
```
./vendor/bin/sail artisan test
```

In order to be allowed to merge a PR into main, all tests must pass and at least one reviewer needs to accept these changes.
