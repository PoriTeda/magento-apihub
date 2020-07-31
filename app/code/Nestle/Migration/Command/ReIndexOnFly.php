<?php


namespace Nestle\Migration\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Indexer\Model\IndexerFactory;

class ReIndexOnFly extends \Symfony\Component\Console\Command\Command
{
    const ENTITY_ID = "entity_id";
    const INDEXER_ID = "indexer_id";
    /**
     * @var IndexerFactory
     */
    private $indexerFactory;

    public function __construct(
        IndexerFactory $indexerFactory,
        string $name = null)
    {
        $this->indexerFactory = $indexerFactory;
        parent::__construct($name);
    }

    protected function configure()
    {
        $options = [
            new InputOption(
                self::INDEXER_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'Index name',
                false
            ),
            new InputOption(
                self::ENTITY_ID,
                null,
                InputOption::VALUE_OPTIONAL,
                'entity id',
                false
            )
        ];
        $this->setName('nestle:reindex-on-fly')->setDescription('Reindex')
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
        $indexId = $input->getOption(self::INDEXER_ID);
        $entityId = $input->getOption(self::ENTITY_ID);
        $output->writeln("reindex indexer id: " . $entityId);
        /** @var \Magento\Indexer\Model\Indexer $indexer */
        $indexer = $this->indexerFactory->create()->load($indexId);
        if ($entityId) {
            $output->writeln("reindex row: " . $entityId);
            $indexer->reindexRow($entityId);
        } else {
            $output->writeln("reindex all");
            $indexer->reindexAll();
        }
    }
}
