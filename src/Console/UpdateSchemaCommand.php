<?php namespace Atrauzzi\LaravelDoctrine\Console;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Input\InputOption;

class UpdateSchemaCommand extends AbstractCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'doctrine:schema:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates your database schema to match your models.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $em = $this->getManager();

        $complete = $this->option('complete');
        $sqlOnly = $this->option('sql');

        $this->comment('ATTENTION: This operation should not be executed in a production environment.');

        $this->info('Obtaining metadata from your models...');
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);

        $sqlToRun = $schemaTool->getUpdateSchemaSql($metadata, $complete);

        if (! count($sqlToRun)) {
            $this->info('Your database is already in sync with your model.');

            return;
        }

        if ($sqlOnly) {
            $this->info('Here\'s the SQL that currently needs to run:');
            $this->info(implode(';' . PHP_EOL, $sqlToRun));
        } else {
            $this->info('Updating database schema...');
            $schemaTool->updateSchema($metadata, $complete);
            $this->info('Database schema updated successfully!');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return array_merge(
            parent::getOptions(),
            array(
                array(
                    'complete',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'If defined, all assets of the database which are not relevant to the current metadata will be dropped.',
                    false
                ),
                array(
                    'sql',
                    null,
                    InputOption::VALUE_NONE,
                    'Dumps the generated SQL statements to the screen (does not execute them).'
                )
            )
        );
    }
}
