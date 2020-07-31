<?php 
namespace Riki\User\Model;

class Password  extends \Magento\Framework\Model\AbstractModel
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\User\Model\ResourceModel\Password');
    }
}