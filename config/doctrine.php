<?php return [

    /*
     * Metadata Driver Configuration
     */

    'metadata'               => [
        'driver' => 'annotation',
//        'driver' => 'config'
//        'driver' => 'yaml',
//        'driver' => 'xml'
//        'driver' => 'static'
//        'namespace' => 'App',
//        'alias'     => 'DoctrineModel',
    ],
//    'mappings'           => [
//        'App\MyModel' => [
//            'table'      => 'my_model',
//            'abstract'   => false,
//            'repository' => 'App\Repository\MyModel',
//            'fields'     => [
//                'id'   => [
//                    'type'     => 'integer',
//                    'strategy' => 'identity'
//                ],
//                'name' => [
//                    'type'     => 'string',
//                    'nullable' => false,
//                ]
//            ],
//            'indexes'    => [
//                'name'
//            ],
//        ],
//    ],
    /*
     * By default, this package mimics the database configuration from Laravel.
     *
     * You can override it in whole or in part here. The 'database' and 'username'
     * laravel settings will be automatically converted to the proper doctrine 'dbname'
     * and 'user' settings. Other custom laravel to doctrine mappings can be added on
     * a per configuration basis by including a 'mappings' entry with 'laravel'=>'doctrine'
     * mappings (see the sqlite configuration for an example).
     *
     * This array passes right through to the EntityManager factory. For
     * example, here you can set additional connection details like "charset".
     *
     * http://doctrine-dbal.readthedocs.org/en/latest/reference/configuration.html#connection-details
     */

    /*
     * Override your laravel environment database selection here if desired
     * 'default_connection' => 'mysql',
     */
    'default_connection'     => 'connection1',
    // Override your laravel values here if desired.
    'connections'            => [
        'connection1' => [
            'driver'   => 'mysqli',
            'host'     => env('DB_HOST', 'localhost'),
            'dbname'   => env('DB_DATABASE', 'forge'),
            'user'     => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'prefix'   => ''
        ],
//        Some preset configurations to map laravel sqlite configs to doctrine
        'connection2' => [
            'driver'   => 'pdo_sqlite',
            'mappings' => [
                'database' => 'path'
            ]
        ]
    ],
    'default_entity_manager' => 'entity_manager1',
    'entity_managers'        => [
        'entity_manager1' => [
            'connection' => 'connection1',
            'mappings'   => [
                'AppBundle'       => null,
                'AcmeStoreBundle' => null,
            ],
        ],
        'entity_manager2' => [
            'connection' => 'connection2',
            'mappings'   => [
                'AcmeCustomerBundle' => null,
            ],
        ],
    ],
    /*
    | ---------------------------------
    | By default, this package mimics the cache configuration from Laravel.
    |
    | You can create your own cache provider by extending the
    | Atrauzzi\LaravelDoctrine\CacheProvider\CacheProvider class.
    |
    | Each provider requires a like named section with an array of configuration options.
    | ----------------------------------
     */
    'cache'                  => [
        // Remove or set to null for no cache
        'provider'  => env('CACHE_DRIVER', 'array'),
        'file'      => [
            'directory' => storage_path('framework/cache'),
            'extension' => '.doctrinecache.data'
        ],
        'redis'     => [
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'database' => 1
        ],
        'memcache'  => [
            'host' => '127.0.0.1',
            'port' => 11211
        ],
        'providers' => [
            'memcache'  => 'Atrauzzi\LaravelDoctrine\CacheProvider\MemcacheProvider',
            'memcached' => 'Atrauzzi\LaravelDoctrine\CacheProvider\MemcachedProvider',
            'couchbase' => 'Atrauzzi\LaravelDoctrine\CacheProvider\CouchebaseProvider',
            'redis'     => 'Atrauzzi\LaravelDoctrine\CacheProvider\RedisProvider',
            'apc'       => 'Atrauzzi\LaravelDoctrine\CacheProvider\ApcProvider',
            'xcache'    => 'Atrauzzi\LaravelDoctrine\CacheProvider\XcacheProvider',
            'array'     => 'Atrauzzi\LaravelDoctrine\CacheProvider\ArrayCacheProvider',
            'custom'    => 'Path\To\Your\Class'
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Sets the directory where Doctrine generates any proxy classes, including
    | with which namespace.
    |--------------------------------------------------------------------------
    |
    | http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/configuration.html
    |
    */
    /*
    'proxy_classes' => [
        'auto_generate' => false,
        'directory' => null,
        'namespace' => null,
    ],
    */

    'migrations'             => [
        'directory'  => '/database/doctrine-migrations',
        'namespace'  => 'DoctrineMigrations',
        'table_name' => 'doctrine_migration_versions'
    ],
    /*
    |--------------------------------------------------------------------------
    | Use to specify the default repository
    | http://doctrine-orm.readthedocs.org/en/latest/reference/working-with-objects.html#custom-repositories
    |--------------------------------------------------------------------------
    */
    /*
    'default_repository' => '\Doctrine\ORM\EntityRepository',
    */

    /*
    |--------------------------------------------------------------------------
    | Use to specify the SQL Logger
    | To use with \Doctrine\DBAL\Logging\EchoSQLLogger, do:
    | 'sqlLogger' => new \Doctrine\DBAL\Logging\EchoSQLLogger();
    |
    | http://doctrine-orm.readthedocs.org/en/latest/reference/advanced-configuration.html#sql-logger-optional
    |--------------------------------------------------------------------------
    */
    /*
    'sql_logger' => null,
    */

    /*
     * In some circumstances, you may wish to diverge from what's configured in Laravel.
     */
    //'debug' => false,

    /*
    | ---------------------------------
    | Add custom Doctrine types here
    | For more information: http://doctrine-dbal.readthedocs.org/en/latest/reference/types.html#custom-mapping-types
    | ---------------------------------
    */
    'custom_types'           => [
        'json' => 'Atrauzzi\LaravelDoctrine\Type\Json'
    ],
    'auth'                   => [
        //'authenticator' => 'Atrauzzi\LaravelDoctrine\DoctrineAuthenticator',
        //'model' => 'App\Models\User',
    ]

];
