<?php
namespace Riki\Customer\Model\ResourceModel;

/**
 * Class EnquiryHeader
 *
 * @package Riki\Customer\Model\ResourceModel
 */
class EnquiryHeader extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_customer_enquiry_header', 'id');
    }

}