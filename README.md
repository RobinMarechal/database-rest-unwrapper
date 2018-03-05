# Database Rest Unwrapper

> This project is deprecated and no longer maintained!

A simple way to unwrap your database as a RESTful API.
Only tested with Laravel 5.5 and MySQL. 

### Requirements
```
PHP: >= 7.0.0
illuminate/support: ~5.1
illuminate/database: 5.5.*
illuminate/http: 5.5.*
nesbot/carbon: ^1.22
```
### Installation

**Installing the package and it's dependencies:**
```
composer require robinmarechal/database-rest-unwrapper:1.0-dev
```
Or, add the line <code>"robinmarechal/database-rest-unwrapper": "^1.0.x-dev"</code> to the <code>require </code> section of your <code>composer.json</code>, then run the command <code>composer update</code>

**Registering the service provider to your app:**

Go to the file `/config/app.php`, add the line 
```
RobinMarechal\DatabaseRestUnwrapper\DatabaseRestUnwrapperServiceProvider::class,
```
in the `providers` section.

**Final step:**

Add the line `use HandleRestRequest;` to a parent class of your application's controllers, or to all of your controllers. 
In a fresh Laravel installation, the Controller class should be located at `app/Http/Controllers/Controller.php`

#### **Here you go!**

[See the doc](http://github.com/RobinMarechal/database-rest-unwrapper/blob/master/documentation.md "See the doc")
