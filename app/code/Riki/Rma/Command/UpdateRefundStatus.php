<?php

namespace Riki\Rma\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Magento\Framework\App\State as AppState;
use Riki\Rma\Api\Data\Rma\RefundStatusInterface;

class UpdateRefundStatus extends Command
{
    const FILE_NAME = 'file_name';
    //list status
    const WAITING_APPROVAL = 'Waiting for approval';
    const APPROVED = 'Approved';
    const GAC_FEEDBACK_REJECTED_NEED_ADJUSTMENT = 'GAC feedback - Rejected (adjustment needed)';
    const GAC_FEEDBACK_REJECTED_NO_NEED_REFUND = 'GAC feedback - Rejected (No need refund)';
    const GAC_FEEDBACK_REVIEWED_BY_CC = 'GAC feedback - Reviewed by CC';
    const GAC_FEEDBACK_APPROVED_BY_CC = 'GAC feedback - Approved by CC';
    const CARD_COMPLETED = 'Card Completed';
    const MANUALLY_CARD_COMPLETED = 'Manually Card Completed';
    const SENT_TO_AGENT = 'Sent to Agent';
    const BT_COMPLETED = 'BT Completed';
    const CHANGE_TO_CHECK = 'Change to check';
    const CHECK_ISSUED = 'Check issued';

    /**
     * @var AppState
     */
    protected $appState;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_varDirectory;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_readerCSV;
    /**
     * @var \Magento\Rma\Model\ResourceModel\Grid\Collection
     */
    protected $_rmaGridCollection;
    /**
     * @var \Riki\Framework\Helper\Logger\LoggerBuilder
     */
    protected $_logger;

    protected $rmaList = [];
    /**
     * @var \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface
     */
    protected $historyRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteria
     */
    protected $searchCriteria;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;
    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\RefundStatus
     */
    protected $refundStatusSource;

    /**
     * UpdateRefundStatus constructor.
     * @param AppState $appState
     * @param \Magento\Framework\File\Csv $readerCSV
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Rma\Model\ResourceModel\Grid\Collection $rmaGridCollection
     * @param \Riki\Framework\Helper\Logger\LoggerBuilder $loggerBuilder
     * @param \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface $historyRepository
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Riki\Rma\Model\Config\Source\Rma\RefundStatus $refundStatusSource
     * @param null $name
     */
    public function __construct(
        AppState $appState,
        \Magento\Framework\File\Csv $readerCSV,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Rma\Model\ResourceModel\Grid\Collection $rmaGridCollection,
        \Riki\Framework\Helper\Logger\LoggerBuilder $loggerBuilder,
        \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface $historyRepository,
        \Magento\Framework\Api\SearchCriteria $searchCriteria,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\Rma\Model\Config\Source\Rma\RefundStatus $refundStatusSource,
        $name = null
    ){
        $this->appState = $appState;
        $this->_logger = $loggerBuilder;
        $this->_readerCSV = $readerCSV;
        $this->_directoryList = $directoryList;
        $this->_rmaGridCollection = $rmaGridCollection;
        $this->historyRepository = $historyRepository;
        $this->searchCriteria = $searchCriteria;
        $this->refundStatusSource = $refundStatusSource;
        $this->_resourceConnection = $resourceConnection;
        parent::__construct($name);
    }

    /**
     *
     * Set param name for CLI
     *
     */
    protected function configure()
    {
        $options = [
            new InputArgument(
                self::FILE_NAME,
                InputArgument::OPTIONAL,
                'Name of file to import'
            )
        ];

        $this->setName('riki:update-refund-status')
            ->setDescription('A cli update refund status')
            ->setDefinition($options);
        parent::configure();
    }


    /**
     * @param $fileName
     * @return array
     * @throws \Exception
     */
    public function prepareData($fileName)
    {
        $varDirectory = $this->_directoryList->getPath('var');

        $dataCsv = $this->_readerCSV->getData(
            $varDirectory.'/'.$fileName
        );
        //get all RMA entity_id
        $entityIds = [];
        foreach ($dataCsv as $vl) {
            if (!in_array($vl[0], $this->rmaList) && is_numeric($vl[0])) {
                array_push($entityIds, $vl[0]);
            }
        }

        if (empty($entityIds)) {
            echo "File content is empty.".PHP_EOL;
            $this->_logger->info("File content is empty. File name is ".$fileName);
            return;
        }

        return $entityIds;
    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = $input->getArgument(self::FILE_NAME);
        if (empty($fileName)) {
            echo "Please insert your file name".PHP_EOL;
            return;
        }
        $this->_logger = $this->_logger->setName(
            'RIM6167'
        )->setFileName(
            'RIM6167'
        )->pushHandlerByAlias(
            \Riki\Framework\Helper\Logger\LoggerBuilder::ALIAS_DATE_HANDLER
        )->create();

        $rmaEntityIds = $this->prepareData($fileName);

        //validate rma entity_id. check existing in system
        $this->rmaList = $this->_rmaGridCollection->addFieldToFilter(
            'entity_id', [
                'in' => $rmaEntityIds
            ]
        )->addFieldToFilter(
            'return_status', [
                'eq' => 7
            ]
        );

        if(!$this->rmaList->getSize()) {
            echo "All rma are not exists." . PHP_EOL;
            $this->_logger->info("All rma are not exists.");
            return;
        }

        $this->getLastRefundStatus($input, $output);
    }

    public function getLastRefundStatus($input, $output)
    {
        $btCompleted = [];
        $checkIssued = [];
        $cardCompleted = [];
        $connection = $this->_resourceConnection->getConnection();
        $table = $connection->getTableName('magento_rma_status_history');
        foreach($this->rmaList->getItems() as $rmaItem){
            $rmaEntityId = $rmaItem->getEntityId();
            $sql = "SELECT comment FROM $table WHERE rma_entity_id = $rmaEntityId ORDER BY created_at desc LIMIT 1";
            $data = $connection->fetchRow($sql);
            if ($data) {
                $refundStatus = $this->getRefundStatus($data['comment']);
                $refundStatusText = $this->refundStatusSource->getLabel($refundStatus);
                $this->_logger->info('The refund #'.$rmaEntityId.' has last status is '.$refundStatusText);
                $output->writeln('The refund #'.$rmaEntityId.' has last status is '.$refundStatusText);
                switch ($refundStatus) {
                    case RefundStatusInterface::CARD_COMPLETED:
                        array_push($cardCompleted, $rmaEntityId);
                        break;
                    case RefundStatusInterface::BT_COMPLETED:
                        array_push($btCompleted, $rmaEntityId);
                        break;
                    case RefundStatusInterface::CHECK_ISSUED:
                        array_push($checkIssued, $rmaEntityId);
                        break;
                }
                $rmaItem->setRefundStatus($refundStatus);
                $rmaItem->save();
            }
        }
        $this->_logger->info('List refund has been card completed is: ('.implode(", ", $cardCompleted).')');
        $output->writeln('List refund has been card completed is: ('.implode(", ", $cardCompleted).')');
        $this->_logger->info('List refund has been BT completed is: ('.implode(", ", $btCompleted).')');
        $output->writeln('List refund has been BT completed is: ('.implode(", ", $btCompleted).')');
        $this->_logger->info('List refund has been check issued is: ('.implode(", ", $checkIssued).')');
        $output->writeln('List refund has been Check Issued is: ('.implode(", ", $checkIssued).')');
    }

    public function getRefundStatus($comment)
    {
        if( strpos($comment, sprintf(__(self::WAITING_APPROVAL))) ){
            return RefundStatusInterface::WAITING_APPROVAL;
        }elseif (strpos($comment, sprintf(__(self::APPROVED)))){
            return RefundStatusInterface::APPROVED;
        }elseif (strpos($comment, sprintf(__(self::GAC_FEEDBACK_REJECTED_NEED_ADJUSTMENT)))){
            return RefundStatusInterface::GAC_FEEDBACK_REJECTED_NEED_ADJUSTMENT;
        }elseif (strpos($comment, sprintf(__(self::GAC_FEEDBACK_REJECTED_NO_NEED_REFUND)))){
            return RefundStatusInterface::GAC_FEEDBACK_REJECTED_NO_NEED_REFUND;
        }elseif (strpos($comment, sprintf(__(self::GAC_FEEDBACK_REVIEWED_BY_CC)))){
            return RefundStatusInterface::GAC_FEEDBACK_REVIEWED_BY_CC;
        }elseif (strpos($comment, sprintf(__(self::GAC_FEEDBACK_APPROVED_BY_CC)))){
            return RefundStatusInterface::GAC_FEEDBACK_APPROVED_BY_CC;
        }elseif (strpos($comment, sprintf(__(self::CARD_COMPLETED)))){
            return RefundStatusInterface::CARD_COMPLETED;
        } elseif (strpos($comment, sprintf(__(self::MANUALLY_CARD_COMPLETED))) !== false) {
            return RefundStatusInterface::MANUALLY_CARD_COMPLETED;
        } elseif (strpos($comment, sprintf(__(self::SENT_TO_AGENT)))){
            return RefundStatusInterface::SENT_TO_AGENT;
        }elseif (strpos($comment, sprintf(__(self::BT_COMPLETED)))){
            return RefundStatusInterface::BT_COMPLETED;
        }elseif (strpos($comment, sprintf(__(self::CHANGE_TO_CHECK)))){
            return RefundStatusInterface::CHANGE_TO_CHECK;
        }else{
            return RefundStatusInterface::CHECK_ISSUED;
        }
    }
}