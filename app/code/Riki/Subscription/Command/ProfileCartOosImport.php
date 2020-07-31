<?php
namespace Riki\Subscription\Command;

use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Filesystem\DriverPool;

class ProfileCartOosImport extends \Symfony\Component\Console\Command\Command
{
    const INPUT_KEY_FILE = 'file';
    const INPUT_KEY_VALIDATE_ONLY = 'validate-only';

    /**
     * @var array
     */
    protected $csvHeader = [];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Filesystem\File\ReadFactory
     */
    protected $fileReadFactory;

    /**
     * @var \Riki\Subscription\Model\Migration\Profile\OutOfStock
     */
    protected $oosMigration;

    /**
     * @var \Riki\Subscription\Model\Migration\Profile\OutOfStock\ItemFactory
     */
    protected $oosItemMigrationFactory;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * ProfileCartOosImport constructor.
     *
     * @param \Magento\Framework\App\State $appState
     * @param \Riki\Subscription\Model\Migration\Profile\OutOfStock\ItemFactory $oosItemMigrationFactory
     * @param \Riki\Subscription\Model\Migration\Profile\OutOfStock $oosMigration
     * @param \Magento\Framework\Filesystem\File\ReadFactory $fileReadFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param null $name
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Riki\Subscription\Model\Migration\Profile\OutOfStock\ItemFactory $oosItemMigrationFactory,
        \Riki\Subscription\Model\Migration\Profile\OutOfStock $oosMigration,
        \Magento\Framework\Filesystem\File\ReadFactory $fileReadFactory,
        \Psr\Log\LoggerInterface $logger,
        $name = null
    ) {
        $this->appState = $appState;
        $this->oosItemMigrationFactory = $oosItemMigrationFactory;
        $this->oosMigration = $oosMigration;
        $this->fileReadFactory = $fileReadFactory;
        $this->logger = $logger;
        parent::__construct($name);
    }

    /**
     * Get list of options and arguments for the command
     *
     * @return array
     */
    public function getInputList()
    {
        return [
            new InputArgument(
                self::INPUT_KEY_FILE,
                InputArgument::REQUIRED,
                'The specific path of file to import'
            ),
            new InputOption(
                self::INPUT_KEY_VALIDATE_ONLY,
                null,
                InputOption::VALUE_OPTIONAL,
                'Run validate only'
            )
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function configure()
    {
        $this->setName('subscription:profile-cart-oos:import')
            ->setDescription('A Cli Import Subscription Profile Product Cart Out Of Stock')
            ->setDefinition($this->getInputList());
        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $time = microtime(true);
        $memory = memory_get_usage();

        try {
            $resultValidate = $this->appState->emulateAreaCode(
                \Magento\Framework\App\Area::AREA_ADMINHTML,
                [$this, 'validate'],
                [$input, $output]
            );
            if ($resultValidate
                && !$input->getOption(self::INPUT_KEY_VALIDATE_ONLY)
            ) {
                $this->appState->emulateAreaCode(
                    \Magento\Framework\App\Area::AREA_ADMINHTML,
                    [$this, 'import'],
                    [$input, $output]
                );
            }
        } catch (\Exception $e) {
            $output->writeln('Error: ' . $e->getMessage());
        }

        $output->writeln(sprintf('Time: %s', array_reduce(
            [microtime(true) - $time],
            function ($k, $v) {
                $units = ['seconds', 'minutes', 'hours'];
                $power = $v >= 1 ? floor(log($v, 60)) : 0;
                return number_format($v / pow(60, $power), 2, '.', ',') . ' ' . $units[$power];
            })
        ));
        $output->writeln(sprintf('Memory: %s', array_reduce(
            [memory_get_usage() - $memory],
            function ($k, $v) {
                $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
                $power = $v > 0 ? floor(log($v, 1024)) : 0;
                return number_format($v / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
            })
        ));
    }

    /**
     * Validate data before import
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    public function validate(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Begin validate...');
        $filePath = $input->getArgument(self::INPUT_KEY_FILE);
        $file = $this->fileReadFactory->create($filePath, DriverPool::FILE);
        $count = 0;
        $result = true;
        while ($dataRow = $file->readCsv()) {
            if (!$this->csvHeader) {
                $this->csvHeader = array_map('strtolower', $dataRow);
                $count++;
                continue;
            }

            $output->writeln(sprintf('Row: #%d', $count));
            $dataRow = array_combine($this->csvHeader, $dataRow);

            /** @var \Riki\Subscription\Model\Migration\Profile\OutOfStock\Item $oosItemMigration */
            $oosItemMigration = $this->oosItemMigrationFactory->create();
            $oosItemMigration->isValid($dataRow);
            if ($oosItemMigration->hasData('messages')) {
                foreach ((array)$oosItemMigration->getData('messages') as $type => $messages) {
                    foreach ($messages as $message) {
                        $output->writeln('→ ' . ucfirst($type) . ' :' . $message);
                    }
                }
                $result = false;
            } else {
                if (!$input->getOption(self::INPUT_KEY_VALIDATE_ONLY)) {
                    $this->oosMigration->queue($oosItemMigration->save());
                }
                $output->writeln('→ Success.');
            }
            $count++;
        }
        $output->writeln('Finish validate.');

        return $result;
    }

    /**
     * Import
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    public function import(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Begin import...');
        $this->oosMigration->migrate();
        $output->writeln('Finish import.');
        return true;
    }
}