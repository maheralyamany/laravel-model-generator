<?php

return [
      /*
      |--------------------------------------------------------------------------
      | Model Files Location
      |--------------------------------------------------------------------------
      |
      | We need a location to store your new generated files. All files will be
      | placed within this directory. When you turn on base files, they will
      | be placed within a Base directory inside this location.
      |
       */
      'output_path' => app_path('Models'),
      /*
            |--------------------------------------------------------------------------
            | Model Namespace
            |--------------------------------------------------------------------------
            |
            | Every generated model will belong to this namespace. It is suggested
            | that this namespace should follow PSR-4 convention and be very
            | similar to the path of your models defined above.
            |
      */
      'namespace' => 'App\Models',
      /*
        |--------------------------------------------------------------------------
        | Parent Class
        |--------------------------------------------------------------------------
        |
        | All Eloquent models should inherit from Eloquent Model class. However,
        | you can define a custom Eloquent model that suits your needs.
        | As an example one custom model has been added for you which
        | will allow you to create custom database castings.
        |
        */
      'base_class_name' => \Illuminate\Database\Eloquent\Model::class,
      /*
        |--------------------------------------------------------------------------
        | Timestamps
        |--------------------------------------------------------------------------
        |
        | If your tables have CREATED_AT and UPDATED_AT timestamps you may
        | enable them and your models will fill their values as needed.
        | You can also specify which fields should be treated as timestamps
        | in case you don't follow the naming convention Eloquent uses.
        | If your table doesn't have these fields, timestamps will be
        | disabled for your model.
        |
        */
      'no_timestamps' => false,
      /*
        |--------------------------------------------------------------------------
        | Date Format
        |--------------------------------------------------------------------------
        |
        | Here you may define your models' date format. The following format
        | is the default format Eloquent uses. You won't see it in your
        | models unless you change it to a more convenient value.
        |
        */
      'date_format' => 'Y-m-d H:i:s',
      /*
        |--------------------------------------------------------------------------
        | Model Connection
        |--------------------------------------------------------------------------
        |
        | If you wish your models had appended the connection from which they
        | were generated, you should set this value to true and your
        | models will have the connection property filled.
        |
        */
      'connection' => null,
      'no_backup' => null,
      'db_types' => [],
      /*
        |--------------------------------------------------------------------------
        | Excluded Tables
        |--------------------------------------------------------------------------
        |
        | When performing the generation of models you may want to skip some of
        | them, because you don't want a model for them or any other reason.
        | You can define those tables bellow. The migrations table was
        | filled for you, since you may not want a model for it.
        |
        */

      'except' => [
            'migrations',
            'personal_access_tokens',
      ],
];
