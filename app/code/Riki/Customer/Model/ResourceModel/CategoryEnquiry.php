<?php
namespace Riki\Customer\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Category post mysql resource
 */
class CategoryEnquiry extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        // Table Name and Primary Key column
        $this->_init('enquiry_category', 'entity_id');
    }
}