<?php namespace Atrauzzi\LaravelDoctrine;

use Doctrine\Common\Persistence\AbstractManagerRegistry;

/**
 * Implementation of the Doctrine ManagerRegistry interface.
 * Provides easier integration with third party libraries such as
 * DoctrineBridge (https://github.com/symfony/DoctrineBridge).
 */
class DoctrineRegistry extends AbstractManagerRegistry
{

    /**
     * {@inheritdoc}
     */
    public function resetService($name)
    {
        app()->forgetInstance("doctrine.em.$name");
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasNamespace($namespaceAlias)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerForClass($class)
    {
        return $this->getService('doctrine');
    }

    /**
     * Returns the service name, for our purposes this will
     * almost always return the Doctrine facade, our access to the
     * entity manager.
     *
     * {@inheritdoc}
     */
    public function getService($name)
    {
        return app("doctrine.em.$name");
    }
}
