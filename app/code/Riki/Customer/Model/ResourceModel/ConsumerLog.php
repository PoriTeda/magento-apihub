<?php
namespace Riki\Customer\Model\ResourceModel;

/**
 * Consumer Log mysql resource
 */
class ConsumerLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('consumer_api_log', 'id');
    }


}