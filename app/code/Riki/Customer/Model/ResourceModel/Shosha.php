<?php
namespace Riki\Customer\Model\ResourceModel;

/**
 * Class Shosha
 * @package Riki\Customer\Model\ResourceModel
 */
class Shosha extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_shosha_business_code', 'id');
    }

}