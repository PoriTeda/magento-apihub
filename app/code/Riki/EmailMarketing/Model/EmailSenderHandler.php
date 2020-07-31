<?php
namespace Riki\EmailMarketing\Model;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class EmailSenderHandler extends \Magento\Sales\Model\EmailSenderHandler
{
    /**
     * Lock file name
     */
    const LOG_ORDER_PATH = 'var/SALES_SEND_ORDER_EMAILS_LOCK';

    const LOG_INVOICE_PATH = 'var/SALES_SEND_INVOICE_EMAILS_LOCK';

    const LOG_SHIPMENT_PATH = 'var/SALES_SEND_SHIPMENT_EMAILS_LOCK';

    const LOG_CREDITMEMO_PATH = 'var/SALES_SEND_CREDITMEMO_EMAILS_LOCK';
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper
     */
    protected $fileHelper;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var Filesystem\Directory\WriteInterface
     */
    protected $directoryWrite;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $stdTimezone;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;
    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    protected $invoiceRepository;
    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditMemoRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $criterialBulder;
    /**
     * EmailSenderHandler constructor.
     * @param \Magento\Sales\Model\Order\Email\Sender $emailSender
     * @param \Magento\Sales\Model\ResourceModel\EntityAbstract $entityResource
     * @param \Magento\Sales\Model\ResourceModel\Collection\AbstractCollection $entityCollection
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileExportHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $stdTimezone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param Filesystem $filesystem
     */
    public function __construct(
        \Magento\Sales\Model\Order\Email\Sender $emailSender,
        \Magento\Sales\Model\ResourceModel\EntityAbstract $entityResource,
        \Magento\Sales\Model\ResourceModel\Collection\AbstractCollection $entityCollection,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileExportHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $stdTimezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        Filesystem $filesystem,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($emailSender,$entityResource,$entityCollection,$globalConfig);
        $this->fileHelper = $fileExportHelper;
        $this->logger = $logger;
        $this->stdTimezone = $stdTimezone;
        $this->dateTime = $dateTime;
        $this->directoryWrite = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->creditMemoRepository = $creditmemoRepository;
        $this->criterialBulder = $searchCriteriaBuilder;

    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendEmails()
    {
        $lockFile = $this->directoryWrite->getRelativePath('lock/' . $this->getLockFileName());
        if (!$this->directoryWrite->isExist($lockFile)) {
            $this->directoryWrite->create($lockFile);
            try {
                if ($this->globalConfig->getValue('sales_email/general/async_sending'))
                {
                    $searchBuilder = $this->criterialBulder->addFilter('send_email',1)
                        ->addFilter('email_sent',true,"null")
                        ->create();
                    $entityRepository = $this->getEntityCollection();
                    $entityCollection = $entityRepository->getList($searchBuilder);
                    /** @var \Magento\Sales\Model\AbstractModel $item */
                    foreach ($entityCollection->getItems() as $item) {
                        try {
                            if ($this->emailSender->send($item, true)
                                && !$item->getEmailSent()
                            ) {
                                $this->entityResource->save(
                                    $item->setEmailSent(true)
                                );
                            }
                        } catch (\Exception $ex) {
                            $this->logger->info($ex->getMessage());
                        }
                    }
                }
            } catch (\Exception $e){
                $this->logger->critical($e);
                return;
            }finally {
                $this->directoryWrite->delete($lockFile);
            }
        }else{
            throw new \Magento\Framework\Exception\LocalizedException(__('Please wait, system have a same process is running and haven’t finish yet.'));
        }
    }
    /**
     * Get lock file
     *      this lock file is used to tracking that system have same process is running
     *
     * @return string
     */
    public function getLockFile()
    {
        return $this->getLockFolder() .DS. 'locker.lock';
    }

    /**
     * @return string
     */
    public function getLockFolder(){
        $emailSender = $this->emailSender;
        $logFile = self::LOG_ORDER_PATH;
        switch(true){
            case $emailSender instanceof \Magento\Sales\Model\Order\Email\Sender\OrderSender:
                $logFile = self::LOG_ORDER_PATH;
                break;
            case $emailSender instanceof \Magento\Sales\Model\Order\Email\Sender\InvoiceSender:
                $logFile = self::LOG_INVOICE_PATH;
                break;
            case $emailSender instanceof \Magento\Sales\Model\Order\Email\Sender\ShipmentSender:
                $logFile = self::LOG_SHIPMENT_PATH;
                break;
            case $emailSender instanceof \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender:
                $logFile = self::LOG_CREDITMEMO_PATH;
                break;
        }
        return $logFile ;
    }
    /**
     * before run Capture schedule - check same process is running
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initSending()
    {
        $baseDir = $this->fileHelper->getRootDirectory();
        /*flag to check tmp folder can create new dir or is writable*/
        $lockFolder = $this->getLockFolder();
        $validateLockFolder = $this->validateLockFolder($baseDir. DS .$lockFolder);
        if ($validateLockFolder) {
            /*tmp file to ensure that system do not run same mulit process at the same time*/
            $lockFile = $this->getLockFile();
            if ($this->fileHelper->isExists($lockFile)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please wait, system have a same process is running and haven’t finish yet.'));
            } else {
                $this->fileHelper->createFile($lockFile);
            }
        }
    }
    /**
     * validate lock folder
     *
     * @param $path
     * @return bool
     */
    public function validateLockFolder($path)
    {
        $this->fileHelper->validateExportFolder($path);
        return true;
    }

    /**
     * Delete lock file
     */
    public function deleteLockFile()
    {
        $this->fileHelper->deleteFile($this->getLockFile());
    }
    /**
     * Each type of cutoff  email has a particular name.
     *
     * @return string
     */
    protected function getLockFileName()
    {
        $part = explode('\\', get_class($this));
        return strtolower(end($part)) .'.lock';
    }

    /**
     * @return CreditmemoRepositoryInterface|\Magento\Sales\Api\InvoiceRepositoryInterface|\Magento\Sales\Api\OrderRepositoryInterface|ShipmentRepositoryInterface
     */
    protected function getEntityCollection(){
        $emailSender = $this->emailSender;
        switch(true){
            case $emailSender instanceof \Magento\Sales\Model\Order\Email\Sender\OrderSender:
                $collection = $this->orderRepository;
                break;
            case $emailSender instanceof \Magento\Sales\Model\Order\Email\Sender\InvoiceSender:
                $collection = $this->invoiceRepository;
                break;
            case $emailSender instanceof \Magento\Sales\Model\Order\Email\Sender\ShipmentSender:
                $collection = $this->shipmentRepository;
                break;
            case $emailSender instanceof \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender:
                $collection = $this->creditMemoRepository;
                break;
        }
        return $collection ;
    }
}