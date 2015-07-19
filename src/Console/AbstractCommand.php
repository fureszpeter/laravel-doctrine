<?php
namespace Atrauzzi\LaravelDoctrine\Console;

use Doctrine\Common\Persistence\ManagerRegistry;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class AbstractCommand extends Command
{
    /**
     * @var \Doctrine\Common\Persistence\ManagerRegistry
     */
    private $registry;

    /**
     * @param \Doctrine\Common\Persistence\ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct();
        $this->registry = $registry;
    }

    /**
     * @param string|null $name Entity Manager name
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getManager($name = null)
    {
        if (!$name) {
            $name = $this->option('connection');
        }

        return $this->registry->getManager($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return [
            [
                'connection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Set the entity manager / connection',
            ]
        ];
    }
}
