# Laravel Model Generator

<p align="center"> <a href="https://how-to-help-ukraine-now.super.site" target="_blank"> <img src="https://emojipedia-us.s3.dualstack.us-west-1.amazonaws.com/thumbs/120/google/313/flag-ukraine_1f1fa-1f1e6.png" alt="Ukraine" width="50" height="50"/> </a>

Laravel Model Generator generates Eloquent models using database schema as a source.


## Installation
Step 1. Add Laravel Model Generator to your project:
``` shell
composer require maheralyamany/laravel-model-generator --dev
```

Add the `model-generator.php` configuration file to your `config` directory and clear the config cache:

```shell
php artisan vendor:publish --tag=model-generator

# Let's refresh our config cache just in case
php artisan config:clear
```
Step 2. Register `ModelGeneratorServiceProvider`:
Insert ModelGenerator\ModelGeneratorServiceProvider::class into "providers" section of /config/app.php

or paste into `AppServiceProvider::register()`
```php
<?php

public function register()
    {
        if ($this->app->environment() === 'local' && class_exists(\ModelGenerator\ModelGeneratorServiceProvider::class)) {
            $this->app->register(\ModelGenerator\ModelGeneratorServiceProvider::class);
        }
    }
```


Step 3. Configure your database connection.

## Usage

Use

```shell
php artisan maheralyamany:generate:model User
```

to generate a model class. Generator will look for table named `users` and generate a model for it.

### table-name

Use `table-name` option to specify another table name:

```shell
php artisan maheralyamany:generate:model Cache --table-name=cache
```

In this case generated model will contain `protected $table = 'user'` property.

### output-path

Generated file will be saved into `app/Models` directory of your application and have `App\Models` namespace by default. If you want to change the destination and namespace, supply the `output-path` and `namespace` options respectively:

```shell
php artisan maheralyamany:generate:model User --output-path=/full/path/to/output/directory --namespace=Your\\Custom\\Models\\Place
```
```shell
php artisan maheralyamany:generate:models  --allow-tables='table_name'  --has-create='true' 

php artisan maheralyamany:generate:models  --allow-tables='table_name'  --has-create='true' 

php artisan maheralyamany:generate:models   --has-create='false' 

```
`output-path` can be absolute path or relative to project's `app` directory. Absolute path must start with `/`:

- `/var/www/html/app/Models` - absolute path
- `Custom/Models` - relative path, will be transformed to `/var/www/html/app/Custom/Models` (assuming your project app directory is `/var/www/html/app`)

### base-class-name

By default, generated class will be extended from `Illuminate\Database\Eloquent\Model`. To change the base class specify `base-class-name` option:

```shell
php artisan maheralyamany:generate:model User --base-class-name=Custom\\Base\\Model
```

### no-backup

If `User.php` file already exist, it will be renamed into `User.php~` first and saved at the same directory. Unless `no-backup` option is specified:

```shell
php artisan maheralyamany:generate:model User --no-backup
```

### Other options

There are several useful options for defining several model's properties:

- `no-timestamps` - adds `public $timestamps = false;` property to the model
- `date-format` - specifies `dateFormat` property of the model
- `connection` - specifies connection name property of the model

### Overriding default options

Instead of specifying options each time when executing the command you can create a config file named `model-generator.php` at project's `config` directory with your own default values:

```php
<?php

return [
    'output_path' => app_path('Models'),
    'namespace' => 'App\Models',
    'base_class_name' => \Illuminate\Database\Eloquent\Model::class,
    'no_timestamps' => false,
    'date_format' => 'Y-m-d H:i:s',
    'connection' => null,
    'no_backup' => null,
    'db_types' => [],
    'except' => [
      'migrations',
      'personal_access_tokens',
      ],
];
```

### Registering custom database types

If running a command leads to an error

```
[Doctrine\DBAL\DBALException]
Unknown database type <TYPE> requested, Doctrine\DBAL\Platforms\MySqlPlatform may not support it.
```

it means that you must register your type `<TYPE>` at your `config/model-generator.php`:

```php
return [
    // ...
    'db_types' => [
        '<TYPE>' => 'string',
    ],
];
```

### Usage example

Table `user`:

```mysql
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
```

Command:

```shell
php artisan maheralyamany:generate:model User
```

Result:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $role_id
 * @property mixed $username
 * @property mixed $email
 * @property Role $role
 * @property Article[] $articles
 * @property Comment[] $comments
 */
class User extends Model
{
  /**
   * @var array
   */
  protected $fillable = ['role_id', 'username', 'email'];

  /**
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function role()
  {
    return $this->belongsTo('Role');
  }

  /**
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function articles()
  {
    return $this->hasMany('Article');
  }

  /**
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function comments()
  {
    return $this->hasMany('Comment');
  }
}
```

## Generating models for all tables

Command `maheralyamany:generate:models` will generate models for all tables in the database. It accepts all options available for `maheralyamany:generate:model` along with `skip-tables` option.

### skip-tables

Specify one or multiple table names to skip:



Note that table names must be specified without prefix if you have one configured.

## Customization

You can hook into the process of model generation by adding your own instances of `ModelGenerator\Processor\ProcessorInterface` and tagging it with `ModelGeneratorServiceProvider::PROCESSOR_TAG`.

Imagine you want to override Eloquent's `perPage` property value.


```php
class PerPageProcessor implements ProcessorInterface
{
  public function process(EloquentModel $model, Config $config): void
  {
    $propertyModel = new PropertyModel('perPage', 'protected', 20);
    $dockBlockModel = new DocBlockModel(
      'The number of models to return for pagination.',
      '',
      '@var int'
    );
    $propertyModel->setDocBlock($dockBlockModel);
    $model->addProperty($propertyModel);
  }

  public function getPriority(): int
  {
    return 8;
  }
}
```

`getPriority` determines the order of when the processor is called relative to other processors.

In your service provider:

```php
public function register()
{
    $this->app->tag([InflectorRulesProcessor::class], [ModelGeneratorServiceProvider::PROCESSOR_TAG]);
}
```

After that, generated models will contain the following code:

```php
/**
 * The number of models to return for pagination.
 *
 * @var int
 */
protected $perPage = 20;
```
