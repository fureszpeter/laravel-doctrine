<?php
namespace Atrauzzi\LaravelDoctrine;

use Atrauzzi\LaravelDoctrine\Listener\Metadata\TablePrefix;
use Auth;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration as DoctrineConfig;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as Base;
use RuntimeException;

/**
 * Class ServiceProvider.
 *
 * @package Atrauzzi\LaravelDoctrine
 */
class ServiceProvider extends Base
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {

        // register enumerations as a doctrine type
        $this->app->make('doctrine.connection')->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $this->registerCustomTypes();

        $this->publishes([__DIR__ . '/../config/doctrine.php' => config_path('doctrine.php')], 'config');

        $this->commands([
            'Atrauzzi\LaravelDoctrine\Console\CreateSchemaCommand',
            'Atrauzzi\LaravelDoctrine\Console\UpdateSchemaCommand',
            'Atrauzzi\LaravelDoctrine\Console\DropSchemaCommand',
        ]);

        $this->extendsAuth();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('\Doctrine\ORM\EntityManager', function () {

            return EntityManager::create(
                $this->getDoctrineConnection(),
                $this->createDoctrineConfig($this->createCache()),
                $this->createEventManager()
            );
        });

        $this->app->singleton('\Doctrine\ORM\Tools\SchemaTool', function (Application $app) {
            return new SchemaTool($app['\Doctrine\ORM\EntityManager']);
        });

        $this->app->singleton('\Doctrine\ORM\Mapping\ClassMetadataFactory', function (Application $app) {
            return $app->make('\Doctrine\ORM\EntityManager')->getMetadataFactory();
        });

        $this->app->singleton('doctrine.connection', function (Application $app) {
            return $app->make('\Doctrine\ORM\EntityManager')->getConnection();
        });

        $this->registerEntityManagers();

        $this->app->singleton('\Doctrine\Common\Persistence\ManagerRegistry', function () {
            return new DoctrineRegistry(
                'doctrine',
                array_combine(
                    array_keys(config('doctrine.connections')),
                    array_keys(config('doctrine.connections'))
                ),
                array_combine(
                    array_keys(config('doctrine.entity_managers')),
                    array_keys(config('doctrine.entity_managers'))
                ),
                config('doctrine.default_connection'),
                config('doctrine.default_entity_manager'),
                'Doctrine\Common\Persistence\Proxy'
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            '\Doctrine\ORM\EntityManager',
            '\Doctrine\ORM\Mapping\ClassMetadataFactory',
            '\Doctrine\ORM\Tools\SchemaTool',
        ];
    }

    /**
     * Takes care of building any drivers we wish to support.
     *
     * Note: Chain is handled above, it's special.
     *
     * @param DoctrineConfig $config
     * @param array $driverConfig
     *
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    protected function createMetadataDriver(DoctrineConfig $config, $driverConfig)
    {
        switch ($driver = array_get($driverConfig, 'driver')) {
            case 'config':
                return new ConfigMappingDriver();
                break;

            case 'annotation':
                return $config->newDefaultAnnotationDriver(
                    array_get($driverConfig, 'paths', app_path()),
                    array_get($driverConfig, 'simple', false)
                );
                break;

            case 'yaml':
                return new YamlDriver(array_get($driverConfig, 'paths', app_path()));
                break;

            case 'xml':
                return new XmlDriver(array_get($driverConfig, 'paths', app_path()));
                break;

            case 'static':
                return new StaticPHPDriver(array_get($driverConfig, 'paths', app_path()));
                break;

            case null:
                throw new RuntimeException('Metadata driver has unspecified type.');
                break;

            default:
                throw new RuntimeException(sprintf('Unsupported driver: %s', $driver));
                break;

        }
    }

    /**
     * Initializes cache. Defaults to Array cache.
     *
     * @throws \Exception
     * @throws \Symfony\Component\Debug\Exception\ClassNotFoundException
     *
     * @return \Doctrine\Common\Cache\CacheProvider|null
     */
    protected function createCache()
    {
        $cacheProvider = config('doctrine.cache.provider');

        if (is_null($cacheProvider)) {
            return null;
        }

        $supportedProviders = config('doctrine.cache.providers', []);

        $cacheConfiguration = config('doctrine.cache.' . $cacheProvider);

        $namespace = config('doctrine.cache.namespace', config('cache.prefix'));

        CacheFactory::setProviders($supportedProviders);

        return CacheFactory::getCacheProvider($cacheProvider, $cacheConfiguration, $namespace);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function registerCustomTypes()
    {
        foreach (config('doctrine.custom_types', []) as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            } else {
                Type::overrideType($name, $class);
            }
        }
    }

    /**
     * @param \Doctrine\Common\Cache\Cache|null $cache
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return \Doctrine\ORM\Configuration
     */
    protected function createDoctrineConfig(Cache $cache = null)
    {
        $debug = config('doctrine.debug', config('app.debug', false));
        $metadataConfig = config('doctrine.metadata', ['driver' => 'config']);
        $proxyDir = config('doctrine.proxy_classes.directory', storage_path('doctrine/proxies'));

        // Note: Don't worry, caches are configured below.
        $doctrineConfig = Setup::createConfiguration($debug, $proxyDir, null);
        if (!empty($metadataConfig) && isset($metadataConfig[0]) && is_array($metadataConfig[0])) {
            $metadataDriver = new MappingDriverChain();
            foreach ($metadataConfig as $subDriverConfig) {
                if (!is_array($subDriverConfig)) {
                    continue;
                }

                $metadataDriver->addDriver(
                    $this->createMetadataDriver($doctrineConfig, $subDriverConfig),
                    array_get($subDriverConfig, 'namespace', 'App')
                );

                if (isset($subDriverConfig['namespace']) && isset($subDriverConfig['alias'])) {
                    $doctrineConfig->addEntityNamespace(
                        $subDriverConfig['alias'],
                        $subDriverConfig['namespace']
                    );
                }
            }
        } else {
            $metadataDriver = $this->createMetadataDriver($doctrineConfig, $metadataConfig);
        }

        $doctrineConfig->setMetadataDriverImpl($metadataDriver);
        //add in trig functions to doctrine for mysql
        $doctrineConfig->setCustomNumericFunctions([
            'ACOS' => 'DoctrineExtensions\Query\Mysql\Acos',
            'ASIN' => 'DoctrineExtensions\Query\Mysql\Asin',
            'ATAN' => 'DoctrineExtensions\Query\Mysql\Atan',
            'ATAN2' => 'DoctrineExtensions\Query\Mysql\Atan2',
            'COS' => 'DoctrineExtensions\Query\Mysql\Cos',
            'COT' => 'DoctrineExtensions\Query\Mysql\Cot',
            'DEGREES' => 'DoctrineExtensions\Query\Mysql\Degrees',
            'RADIANS' => 'DoctrineExtensions\Query\Mysql\Radians',
            'SIN' => 'DoctrineExtensions\Query\Mysql\Sin',
            'TAN' => 'DoctrineExtensions\Query\Mysql\Tan',
        ]);
        // Note: These must occur after Setup::createAnnotationMetadataConfiguration()
        // in order to set custom namespaces properly
        if ($cache) {
            $doctrineConfig->setMetadataCacheImpl($cache);
            $doctrineConfig->setQueryCacheImpl($cache);
            $doctrineConfig->setResultCacheImpl($cache);
        }

        $doctrineConfig->setAutoGenerateProxyClasses(
            config('doctrine.proxy_classes.auto_generate', !$debug)
        );
        $doctrineConfig->setDefaultRepositoryClassName(
            config('doctrine.default_repository', '\Doctrine\ORM\EntityRepository')
        );
        $doctrineConfig->setSQLLogger(config('doctrine.sql_logger'));
        if ($proxyClassNamespace = config('doctrine.proxy_classes.namespace')) {
            $doctrineConfig->setProxyNamespace($proxyClassNamespace);
        }

        return $doctrineConfig;
    }

    /**
     * @return EventManager
     */
    protected function createEventManager()
    {
        $eventManager = new EventManager();

        // Trap doctrine events, to support entity table prefix
        if ($prefix = config('doctrine.connection.prefix')) {
            $eventManager->addEventListener(Events::loadClassMetadata, new TablePrefix($prefix));
        }

        return $eventManager;
    }

    protected function extendsAuth()
    {
        $authenticator = config('doctrine.auth.authenticator', null);

        if (!is_null($authenticator) && class_exists($authenticator)) {
            Auth::extend('doctrine.auth', function (Application $app) use ($authenticator) {
                return $app->make($authenticator, [config('doctrine.auth.model')]);
            });
        }
    }

    /**
     * @param string $name The name of the connection. Default value is the value of `doctrine.connections.default
     *
     * @throws \Exception If not connection found
     *
     * @return mixed
     */
    protected function getDoctrineConnection($name = null)
    {
        if (!$name) {
            $name = config('doctrine.default_connection', config('database.default'));
        }

        $database = $name;

        if (empty($database)) {
            throw new \Exception("Connection name not set: " . $name);
        }

        $laravel_database_configuration = config('database.connections.' . $database, []);
        $doctrine_database_overrides = config('doctrine.connections.' . $database, []);

        $configurations = $this->mapToDoctrineConfigs(
            array_merge($laravel_database_configuration, $doctrine_database_overrides)
        );

        return $configurations;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    private function mapToDoctrineConfigs(array $config)
    {
        $mappings = [
            'database' => 'dbname',
            'username' => 'user',
        ];

        if (array_key_exists('mappings', $config)) {
            $mappings = array_merge($mappings, $config['mappings']);
            unset($config['mappings']);
        }

        foreach ($mappings as $laravel => $doctrine) {
            // If both are already set, use the doctrine setting and remove the laravel one
            if (array_key_exists($doctrine, $config)) {
                unset($config[$laravel]);
            } // Otherwise replace the laravel setting with properly named doctrine setting
            elseif (array_key_exists($laravel, $config)) {
                $config[$doctrine] = $config[$laravel];
                unset($config[$laravel]);
            }
        }

        return $config;
    }

    /**
     * @return void
     */
    private function registerEntityManagers()
    {
        $managers = config('doctrine.entity_managers', []);

        foreach ($managers as $manager => $settings) {
            $this->app->singleton("doctrine.em.{$manager}", function () use ($settings) {

                return EntityManager::create(
                    $this->getDoctrineConnection($settings['connection']),
                    $this->createDoctrineConfig($this->createCache()),
                    $this->createEventManager()
                );

            });
        }
    }
}
