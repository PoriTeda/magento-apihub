<?php
namespace Riki\Customer\Model;

class EnquiryHeader  extends \Magento\Framework\Model\AbstractModel
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Customer\Model\ResourceModel\EnquiryHeader');
    }
}