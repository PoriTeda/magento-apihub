<?php


namespace Riki\MessageQueue\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Indexer\Model\IndexerFactory;

/**
 * Class StopQueueCommand
 * @package Riki\MessageQueue\Command
 */
class FlagQueueCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     *
     */
    const ENABLE = "enable";
    /**
     *
     */
    const DISABLE = "disable";
    /**
     * @var \Riki\MessageQueue\Helper\QueueDataHelper
     */
    private $queueDataHelper;

    /**
     * StopQueueCommand constructor.
     * @param \Riki\MessageQueue\Helper\QueueDataHelper $queueHelperData
     * @param string|null $name
     */
    public function __construct(
        \Riki\MessageQueue\Helper\QueueDataHelper $queueHelperData,
        string $name = null)
    {
        $this->queueDataHelper = $queueHelperData;
        parent::__construct($name);
    }

    /**
     *
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::ENABLE,
                null,
                InputOption::VALUE_OPTIONAL,
                'enable flag',
                false
            ),
            new InputOption(
                self::DISABLE,
                null,
                InputOption::VALUE_OPTIONAL,
                'disable flag',
                false
            )
        ];
        $this->setName('riki:queue:stop:flag')->setDescription('Enable or disable flag queue')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isEnable = $input->getOption(self::ENABLE);
        $isDisable = $input->getOption(self::DISABLE);
        if ($isEnable !== false) {
            $this->queueDataHelper->setDisable(true);
            $output->writeln('<info>Enable flag stop queue consumers</info>');
        }

        if ($isDisable !== false) {
            $this->queueDataHelper->setDisable(false);
            $output->writeln('<info>Disable flag stop queue consumers</info>');
        }

        if ($isEnable === false && $isDisable === false) {
            $output->writeln('<error>You need to define isDisable or isEnable</error>');
        }
    }
}
