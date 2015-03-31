<?php
namespace Codeception\Command;

use Codeception\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Codeception\Lib\Driver\Db as Driver;
use Codeception\Exception\Module as ModuleException;

/**
 * Restore database to initial state
 *
 * * `codecept restore`
 * * `codecept restore -c path/to/project`
 *
 */
class Restore extends Command
{
    use Shared\Config;

    public function getDescription() {
        return 'Restore database to initial state';
    }

    protected function configure()
    {
        $this->setDefinition(array(
            new InputOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Use custom path for config'),
        ));
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getGlobalConfig($input->getOption('config'));

        if (!isset($config['modules']['config']['Db']['dump'])) {
            throw new ModuleException(__CLASS__, '"dump" parameter is not specified');
        }

        if (!file_exists(Configuration::projectDir() . $config['modules']['config']['Db']['dump'])) {
            throw new ModuleException(__CLASS__, 'dump file does not exists');
        }

        try {
            $driver = Driver::create($config['modules']['config']['Db']['dsn'], $config['modules']['config']['Db']['user'], $config['modules']['config']['Db']['password']);
        } catch (\PDOException $e) {
            throw new ModuleException(__CLASS__, $e->getMessage() . ' while creating PDO connection');
        }

        $output->write('<info>Cleaning up database...</info> ');
        $driver->cleanup();
        $output->writeln('done');

        $output->write('<info>Restoring database...</info> ');
        $sql = file_get_contents(Configuration::projectDir() . $config['modules']['config']['Db']['dump']);
        $sql = preg_replace('%/\*(?!!\d+)(?:(?!\*/).)*\*/%s', '', $sql);
        $sql = explode("\n", $sql);
        $driver->load($sql);
        $output->writeln('done');
    }
}