<?php namespace Atrauzzi\LaravelDoctrine\Console;

use Doctrine\ORM\Tools\SchemaTool;


class CreateSchemaCommand extends AbstractCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'doctrine:schema:create';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Creates your database schema according to your models.';

    /**
     * Execute the console command.
     */
	public function fire() {

        $em = $this->getManager();

		$this->comment('ATTENTION: This operation should not be executed in a production environment.');
		$this->info('Obtaining metadata...');
        $metadata = $em->getMetadataFactory()->getAllMetadata();
		$this->info('Creating database schema...');
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($metadata);
		$this->info('Database schema created successfully!');
    }
}
