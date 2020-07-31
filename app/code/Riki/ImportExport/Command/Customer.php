<?php
/**
 * Customer.
 *
 * PHP version 7
 *
 * @category  RIKI
 *
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ImportExport\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Magento\ImportExport\Model\Import\Adapter as ImportAdapter;

/**
 * Class Customer.
 *
 * @category  RIKI
 *
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Customer extends Command
{
    const FILE_NAME = 'import_file';
    /**
     * Filesystem.
     *
     * @var \Magento\Framework\Filesystem Filesystem
     */
    protected $fileSystem;

    /**
     * ObjectManagerInterface.
     *
     * @var \Magento\Framework\ObjectManagerInterface ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * App State.
     *
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * Customer constructor.
     *
     * @param \Magento\Framework\Filesystem             $fileSystem             Filesystem
     * @param \Magento\Framework\ObjectManagerInterface $objectManagerInterface ObjectManagerInterface
     * @param \Magento\Framework\App\State              $state
     */
    public function __construct(
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct();
        $this->fileSystem = $fileSystem;
        $this->objectManager = $objectManagerInterface;
        $this->appState = $state;
    }

    /**
     * Set param name for CLI.
     */
    protected function configure()
    {
        $options = [
            new InputArgument(
                self::FILE_NAME,
                InputArgument::REQUIRED,
                'Name of file to import'
            ), new InputArgument(
                'entity',
                InputArgument::OPTIONAL,
                'Type of import',
                'customer'
            ), new InputArgument(
                '_import_field_separator',
                InputArgument::OPTIONAL,
                '_import_field_separator',
                ','
            ),
            new InputArgument(
                '_import_multiple_value_separator',
                InputArgument::OPTIONAL,
                '_import_multiple_value_separator',
                ','
            ), new InputArgument(
                'allowed_error_count',
                InputArgument::OPTIONAL,
                'allowed_error_count',
                '10'
            ), new InputArgument(
                'behavior',
                InputArgument::OPTIONAL,
                'behavior',
                'add_update'
            ), new InputArgument(
                'validation_strategy',
                InputArgument::OPTIONAL,
                'validation_strategy',
                'validation-stop-on-errors'
            ), new InputArgument(
                'import_images_file_dir',
                InputArgument::OPTIONAL,
                'Images base directory',
                ''
            ),
        ];
        $this->setName('riki:import:customer')
            ->setDescription('Import Customer')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * Validate + import customer.
     *
     * @param InputInterface  $input  InputInterface
     * @param OutputInterface $output OutputInterface
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = $input->getArgument('entity');
        if ($entity == '') {
            $entity = 'customer';
        }
        $entity = str_replace('_', ' ', strtoupper($entity));

        $timeStart = microtime(true);
        $output->write("RIKI IMPORT $entity CLI\n");
        $output->write("************************************************************\n");
        $output->write("Validating data. It may validate ~500records/minute \r\n");
        $data = $input->getArguments();
        $fileName = $input->getArgument(self::FILE_NAME);

        $directoryWrite = $this->fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);

        if ($fileName && $directoryWrite->isFile($fileName)) {
            try {
                $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

                // Remove BOM in header
                $fileContent = $directoryWrite->readFile($fileName);
                if ($fileContent !== false && substr($fileContent, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
                    $fileContent = substr($fileContent, 3);
                    $directoryWrite->writeFile($directoryWrite->getRelativePath($fileName), $fileContent);
                }

                $this->objectManager
                    ->get('Magento\Framework\App\ResourceConnection')
                    ->getConnection()
                    ->query('SET SESSION wait_timeout = 28800');

                $import = $this->objectManager->create('Magento\ImportExport\Model\Import')->setData($data);

                $source = ImportAdapter::findAdapterFor(
                    $fileName,
                    $directoryWrite,
                    $data[\Magento\ImportExport\Model\Import::FIELD_FIELD_SEPARATOR]
                );

                $validationResult = $import->validateSource($source);

                $validaEnd = microtime(true);
                $validateTime = $validaEnd - $timeStart;
                $output->writeln("Time take: $validateTime second(s) to finish \n");

                $output->write("Validation result is:\r\n");
                if (!$import->getProcessedRowsCount()) {
                    if (!$import->getErrorAggregator()->getErrorsCount()) {
                        $output->writeln("This file is empty. Please try another one.\r\n");
                    } else {
                        foreach ($import->getErrorAggregator()->getAllErrors() as $error) {
                            $output->writeln($error);
                        }
                    }

                    return 1;
                } else {
                    $errorAggregator = $import->getErrorAggregator();
                    if (!$validationResult) {
                        $error = $this->addErrorMessages($errorAggregator, $fileName);
                        $output->writeln("Data validation is failed. Please fix errors and try again..\r\n");
                        $output->write($error);

                        return 1;
                    } else {
                        if ($import->isImportAllowed()) {
                            // start import
                            $output->writeln("File is valid! \r\n");
                            $helper = $this->getHelper('question');
                            $question = new ConfirmationQuestion("Do you want to process import? \n Type 'y' to import, any thing else to cancel: ", false, '/^yes$/i');
                            if (!$helper->ask($input, $output, $question)) {
                                $output->writeln('Exit without import');

                                return 1;
                            } else {
                                $output->writeln("Start import ... \n");
                                $import->importSource();
                            }
                            $errorAggregatorImport = $import->getErrorAggregator();
                            if ($import->getErrorAggregator()->hasToBeTerminated()) {
                                $output->writeln("Maximum error count has been reached or system error is occurred!\n");
                                $error = $this->addErrorMessages($errorAggregatorImport);
                                $output->writeln($error."\n");

                                return 1;
                            } else {
                                $import->invalidateIndex();
                                $error = $this->addErrorMessages($errorAggregatorImport);
                                $output->writeln($error."\r\n");
                                $output->writeln(" Import successfully done \r\n");
                            }
                        } else {
                            $output->writeln("The file is valid, but we can't import it for some reason. \r\n");

                            return 1;
                        }
                    }

                    $rowCount = $import->getProcessedRowsCount();
                    $entityCount = $import->getProcessedEntitiesCount();
                    $invalidRowCount = $errorAggregator->getInvalidRowsCount();
                    $errorCount = $errorAggregator->getErrorsCount();
                    $output->writeln("Checked rows: $rowCount, checked entities: $entityCount, invalid rows: $invalidRowCount, total errors: $errorCount");

                    return 0;
                }
            } catch (\Exception $e) {
                $output->writeln(
                    $e->getMessage()
                );

                return 1;
            }
        } else {
            $output->writeln('Import file does not exist.');
            return 1;
        }
    }

    /**
     * AddErrorMessages.
     *
     * @param \Magento\ImportExport\Model\Import $errorAggregator Import
     * @param string                             $fileName        filename
     *
     * @return string
     */
    public function addErrorMessages($errorAggregator, $fileName = '')
    {
        $message = '';
        if ($errorAggregator->getErrorsCount()) {
            $counter = 0;
            foreach ($this->getErrorMessages($errorAggregator) as $error) {
                ++$counter;
                $message .= $counter.'. '.$error."\n";
            }
        }

        return $message;
    }

    /**
     * Extract error from validator model.
     *
     * @param \Magento\ImportExport\Model\Import $errorAggregator param
     *
     * @return array
     */
    protected function getErrorMessages($errorAggregator)
    {
        $messages = [];
        $rowMessages = $errorAggregator->getRowsGroupedByErrorCode([], [\Magento\ImportExport\Model\Import\Entity\AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION]);
        foreach ($rowMessages as $errorCode => $rows) {
            $messages[] = $errorCode.' '.__('in rows:').' '.implode(', ', $rows);
        }

        return $messages;
    }

    /**
     * CreateErrorReport.
     *
     * @param \Magento\ImportExport\Model\Import $errorAggregator param
     * @param string                             $file            file
     *
     * @return mixed
     */
    protected function createErrorReport($errorAggregator, $file)
    {
        $result = $this->objectManager->create('\Magento\ImportExport\Controller\Adminhtml\ImportResult');
        $result->historyModel->loadLastInsertItem();
        $sourceFile = $file;
        $writeOnlyErrorItems = true;
        if ($result->historyModel->getData('execution_time') == \Magento\ImportExport\Model\History::IMPORT_VALIDATION) {
            $writeOnlyErrorItems = false;
        }
        $fileName = $result->reportProcessor->createReport($sourceFile, $errorAggregator, $writeOnlyErrorItems);
        $result->historyModel->addErrorReportFile($fileName);

        return $fileName;
    }
}
