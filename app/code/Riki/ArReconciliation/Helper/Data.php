<?php
namespace Riki\ArReconciliation\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DATE_FORMAT = 'd-M-y';
    const TIME_FORMAT = 'H:i';

    const PAYMENT_OBJECT = 'payment';
    const RETURN_OBJECT = 'return';

    const CHANGE_TYPE_AMOUNT = 1;
    const CHANGE_TYPE_DATE = 2;
    const CHANGE_TYPE_BOTH = 3;

    const NESTLE_PAYMENT_AMOUNT = 'nestle_payment_amount';
    const NESTLE_PAYMENT_DATE = 'nestle_payment_date';
    const NESTLE_REFUND_AMOUNT = 'nestle_refund_amount';
    const NESTLE_REFUND_DATE = 'nestle_refund_date';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;

    protected $_cvs;

    protected $_amountColumn;
    protected $_dateColumn;
    protected $_transactionColumn;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        DirectoryList $directoryList
    ) {
        $this->_timezone = $timezone;
        $this->_dateTime = $dateTime;
        $this->_jsonHelper = $jsonHelper;
        $this->_fileFactory = $fileFactory;
        $this->_directoryList = $directoryList;
        $this->_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());

        parent::__construct($context);
    }

    /**
     * @param $time
     * @return string
     */
    public function getWebDate($time)
    {
        return $this->_timezone->date(new \DateTime($time), null, false)->format(self::DATE_FORMAT);
    }

    /**
     * @param $time
     * @return string
     */
    public function getWebTime($time)
    {
        return $this->_timezone->date( $time )->format(self::TIME_FORMAT);
    }

    /**
     * @param $type
     * @param bool $transactionColumn
     */
    public function initColumnName($type, $transactionColumn = false){

        if( $transactionColumn ){
            $this->_transactionColumn = $transactionColumn;
        }

        if( $type == self::PAYMENT_OBJECT ){
            $this->_amountColumn = self::NESTLE_PAYMENT_AMOUNT;
            $this->_dateColumn = self::NESTLE_PAYMENT_DATE;
        } elseif ($type == self::RETURN_OBJECT) {
            $this->_amountColumn = self::NESTLE_REFUND_AMOUNT;
            $this->_dateColumn = self::NESTLE_REFUND_DATE;
        }
    }

    /**
     * @param $object
     * @param $type
     * @param $transactionColumn
     * @return bool
     */
    public function exportChangLog($object, $type, $transactionColumn){
        if( !empty( $object ) ){

            $this->initColumnName($type, $transactionColumn);
            $exportData = [];
            array_push($exportData, $this->fileHeader());

            $transactionId = '';
            foreach ($object as $item) {
                array_push($exportData, $this->exportItem($item));
                if( empty($transactionId) ){
                    $transactionId = $item->getData($this->_transactionColumn);
                }
            }

            $exportFile = 'history-'.$transactionId.'-'.$this->_dateTime->date('YmdHis').'.csv';

            $this->createDownloadFile($exportFile, $exportData);

            return $this->_fileFactory->create(
                $exportFile,
                [
                    'type'  => 'filename',
                    'value' => $exportFile,
                    'rm'    => true // can delete file after use
                ],
                DirectoryList::VAR_DIR
            );

        } else {
            return false;
        }
    }

    /**
     * @param $exportFile
     * @param $exportData
     * @return bool
     */
    public function createDownloadFile($exportFile, $exportData){
        $file = $this->_directoryList->getPath(DirectoryList::VAR_DIR). DIRECTORY_SEPARATOR . $exportFile;
        $this->_csv->saveData($file, $exportData);
        return true;
    }

    /**
     * @return array
     */
    public function fileHeader(){
        return [
            "User ID", "Change Date", "Time", "Transaction ID", "Changes Made", "Changed To", "Change From"
        ];
    }

    public function exportItem($item){
        return [
            $item->getData('user_name'),
            $this->getWebDate($item->getData('created')),
            $this->getWebTime($item->getData('created')),
            $item->getData($this->_transactionColumn),
            $item->getData('note'),
            $this->getChangeTo($item),
            $this->getChangeFrom($item)
        ];
    }

    /**
     * @param $item
     * @param $type
     * @return bool|int|string
     */
    public function getChangeFrom($item, $type = false){
        if($type){
            $this->initColumnName($type);
        }
        $log = $this->_jsonHelper->jsonDecode($item->getLog());
        if( !empty($log) ){
            if( $item->getChangeType() == self::CHANGE_TYPE_AMOUNT ){
                if( !empty($log[$this->_amountColumn]) ){
                    return (int)$log[$this->_amountColumn];
                }
            } elseif ( $item->getChangeType() == self::CHANGE_TYPE_DATE ){
                if( !empty($log[$this->_dateColumn]) ){
                    return $this->getWebDate($log[$this->_dateColumn]);
                }
            } elseif ( $item->getChangeType() == self::CHANGE_TYPE_BOTH ) {
                if( !empty($log[$this->_amountColumn]) && !empty( $log[$this->_dateColumn] ) ){
                    return (int)$log[$this->_amountColumn].' / '. $this->getWebDate($log[$this->_dateColumn]);
                }
            }
        }

        return false;
    }

    /**
     * @param $item
     * @param $type
     * @return bool|int|string
     */
    public function getChangeTo($item, $type = false){
        if( $type ){
            $this->initColumnName($type);
        }
        if( $item->getChangeType() == self::CHANGE_TYPE_AMOUNT ){
            return (int)$item->getData($this->_amountColumn);
        } elseif ( $item->getChangeType() == self::CHANGE_TYPE_DATE ){
            return $this->getWebDate($item->getData($this->_dateColumn));
        } elseif ( $item->getChangeType() == self::CHANGE_TYPE_BOTH ) {
            return (int)$item->getData($this->_amountColumn).' / '. $this->getWebDate($item->getData($this->_dateColumn));
        } else {
            return false;
        }
    }

    public function getChangeType($changeAmount, $changeDate){
        if($changeAmount == true && $changeDate == true)
        {
            return self::CHANGE_TYPE_BOTH;
        }
        else if($changeAmount == true)
        {
            return self::CHANGE_TYPE_AMOUNT;
        }
        else if($changeDate == true)
        {
            return self::CHANGE_TYPE_DATE;
        }
    }

    /**
     * @param $changeAmount
     * @param $changeDate
     * @param $type
     * @return mixed
     */
    public function getChangeLogMessage( $changeAmount, $changeDate, $type ){
        if( $type == self::PAYMENT_OBJECT ){
            return $this->collectedMessage($changeAmount, $changeDate);
        } elseif ($type == self::RETURN_OBJECT) {
            return $this->refundedMessage($changeAmount, $changeDate);
        }
    }

    /**
     * @param $changeAmount
     * @param $changeDate
     * @return \Magento\Framework\Phrase
     */
    public function collectedMessage($changeAmount, $changeDate){
        if($changeAmount == true && $changeDate == true)
        {
            return __('Amount and date of money Received');
        }
        else if($changeAmount == true)
        {
            return __('Amount of money Received');
        }
        else if($changeDate == true)
        {
            return __('Date Nestle Received Money');
        }
    }

    /**
     * @param $changeAmount
     * @param $changeDate
     * @return \Magento\Framework\Phrase
     */
    public function refundedMessage($changeAmount, $changeDate){
        if($changeAmount == true && $changeDate == true)
        {
            return __('Amount and date of money returned');
        }
        else if($changeAmount == true)
        {
            return __('Amount of money returned');
        }
        else if($changeDate == true)
        {
            return __('Date Nestle Returned Money');
        }
    }
}
