<?php
namespace Riki\Customer\Model\ResourceModel\CategoryEnquiry;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    protected $_idFieldName = \Riki\Customer\Model\CategoryEnquiry::CATEGORY_ID;
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Customer\Model\CategoryEnquiry', 'Riki\Customer\Model\ResourceModel\CategoryEnquiry');
    }

}