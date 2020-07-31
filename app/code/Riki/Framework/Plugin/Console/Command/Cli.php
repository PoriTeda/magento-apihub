<?php

namespace Riki\Framework\Plugin\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Riki\Cron\Helper\CronNameHelper;

class Cli
{
    /**
     * @param $command
     * @param $proceed
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return mixed
     */
    public function beforeRun(\Symfony\Component\Console\Command\Command $command, InputInterface $input, OutputInterface $output)
    {

        if (extension_loaded('newrelic')) {
            $arguments = $input->getArguments();

            //get name of application run cli
            $names[] = $command->getApplication()->getName();
            if (isset($arguments['command']) && $arguments['command'] != null) {
                $names[] = $arguments['command'];

                if ($arguments['command'] == 'queue:consumers:start'
                    && isset($arguments['consumer'])
                    && $arguments['consumer']
                ) {
                    $names[] = $arguments['consumer'];
                }
            }

            $nameCommandCli = CronNameHelper::changeCronName(implode('/', $names));
            newrelic_name_transaction($nameCommandCli);
        }

    }
}
