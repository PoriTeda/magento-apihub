<?php
/**
 * Email Marketing
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Cron;

/**
 * Class EmailQueue
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class EmailQueue
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslation;
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Riki\EmailMarketing\Model\ResourceModel\Queue\CollectionFactory
     */
    protected $_queueEmail;

    /**
     * EmailQueue constructor.
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\EmailMarketing\Model\ResourceModel\Queue\CollectionFactory $queueEmail
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Psr\Log\LoggerInterface $logger,
        \Riki\EmailMarketing\Model\ResourceModel\Queue\CollectionFactory $queueEmail,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_dateTime = $dateTime;
        $this->_jsonHelper = $jsonHelper;
        $this->_storeManager = $storeManager;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_logger = $logger;
        $this->_queueEmail =  $queueEmail;
        $this->_resource = $resource;
    }

    public function execute()
    {
        /** @var \Riki\EmailMarketing\Model\ResourceModel\Queue\Collection $collection */
        $collection = $this->_queueEmail->create();
        $collection->addFieldToFilter('is_sent',0);
        $listDeleteMailQueue = [];
        $batch = 0;
        $batchListIds = '';
        $countItems = 0;
        if( $collection->getSize() ){
            foreach ( $collection as $item ) {
                if
                (
                    $item->getTemplateId() &&
                    $item->getFromName() &&
                    $item->getFromEmail() &&
                    $item->getSendTo()
                )
                {
                    $sendEmail = $this->sendEmail($item);
                    $this->_logger->info('NED-8949 Email queue #'.$item->getQueueId().' sent mail result: '.$sendEmail);
                    if( $sendEmail ){
                        $batchListIds .= $item->getQueueId().',';
                        if ($countItems % 100 == 0 && $countItems != 0)
                        {
                            $listDeleteMailQueue[$batch] = substr($batchListIds,0,-1);
                            $batch++;
                            $batchListIds = '';
                        }
                        $countItems++;
                    }

                }
                else
                {
                    try{
                        $item->delete();
                    }catch(\Exception $e)
                    {
                        $this->_logger->error($e->getMessage());
                    }
                }
            }
            if ($batchListIds != '')
            {
                $listDeleteMailQueue[$batch++] = substr($batchListIds,0,-1);
            }
            $this->updateEmailData($listDeleteMailQueue);
        }
    }

    /**
     * @param $item
     * @return bool
     */
    public function sendEmail($item)
    {
        try {
            $this->_inlineTranslation->suspend();
            $this->generateTemplate($item);
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslation->resume();
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * @param $item
     * @return $this
     */
    protected function generateTemplate( $item )
    {
        $variables = $this->_jsonHelper->jsonDecode($item->getVariables());
        $variables['is_sent'] = 1;
        $senderInfo = [
            'name' => $item->getFromName() , 'email' => $item->getFromEmail()
        ];
        $this->_transportBuilder->setTemplateIdentifier( $item->getTemplateId() )
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars( $variables )
            ->setFrom($senderInfo)
            ->addTo( $item->getSendTo());
        return $this;
    }

    /**
     * @param $item
     * @return bool
     */
    public function updateEmailData($listEmailQueue)
    {
        if (empty($listEmailQueue))
        {
            $this->_logger->info('NED-8949 not exist email queue to delete.');
            return true;
        }
        try {
            $connection = $this->_resource->getConnection('default');
            $tableName = $this->_resource->getTableName('riki_email_queue');
            foreach ($listEmailQueue as $emailQueue)
            {
                $sql = "DELETE FROM `" .$tableName. "` WHERE `queue_id` IN (" .$emailQueue. ")";
                $connection->query($sql);
            }
        } catch (\Exception $e){
            $this->_logger->critical($e->getMessage());
        }
        return true;
    }

}//end class