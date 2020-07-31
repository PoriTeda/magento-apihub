<?php

namespace Riki\Fraud\Cron;

class CedynaThresholdReset
{
    const CEDYNA_COUNTER_ATTRIBUTE = 'cedyna_counter';
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    /**
     * @var \Riki\Fraud\Logger\Logger $logger
     */
    protected $_logger;

    public function __construct(
        \Magento\Eav\Model\Entity\Context $context,
        \Riki\Fraud\Logger\Logger $logger
    ){
        $this->_resource = $context->getResource();
        $this->_logger = $logger;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection()
    {
        return $this->_resource->getConnection();
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $this->getConnection()->update(
            $this->_resource->getTableName('riki_shosha_business_code'),
            ['cedyna_counter' => 0]
        );

        $this->_logger->info('Cedyna Monthly Reset - Reset Cedyna Monthly Counter value Success');

        /*deactive old data*/
        $this->getConnection()->update(
            $this->_resource->getTableName('riki_order_cedyna_threshold'),
            ['is_actived' => 0]
        );

        $this->_logger->info('Cedyna Monthly Reset - Deactived old data success');

        $this->_logger->info('Cedyna Monthly Reset - Success');

        return ;
    }

}
