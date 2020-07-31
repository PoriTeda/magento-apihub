<?php 
namespace Riki\Customer\Model;

class ConsumerLog  extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_SUCCESS = 1;
    const STATUS_ERROR = 0;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Customer\Model\ResourceModel\ConsumerLog');
    }
}