<?php
namespace Codeception\Command;

use Codeception\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Codeception\Exception\Module as ModuleException;

/**
 * Backup database to "dump" file
 *
 * * `codecept backup`
 * * `codecept backup -c path/to/project`
 *
 */
class Backup extends Command
{
    use Shared\Config;

    public function getDescription() {
        return 'Backup database to "dump" file';
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

        if (!isset($config['modules']['config']['Db']['dsn'])) {
            throw new ModuleException(__CLASS__, '"dsn" parameter is not specified');
        }

        $dsn    = explode(';', $config['modules']['config']['Db']['dsn']);
        $dbName = '';

        foreach ($dsn as $record) {
            if (stripos($record, 'dbname') !== false) {
                $temp   = explode('=', $record);
                $dbName = $temp[1];
            }
        }

        if (!$dbName) {
            throw new ModuleException(__CLASS__, 'Can\'t retrive dbname from "dsn" parameter');
        }

        $output->write('<info>backuping database...</info>');
        exec('mysqldump -u' . $config['modules']['config']['Db']['user'] . ' -p' . $config['modules']['config']['Db']['password'] . ' ' . $dbName . ' > ' . Configuration::projectDir() . $config['modules']['config']['Db']['dump']);
        $output->writeln('done');
    }
}