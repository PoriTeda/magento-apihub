<?php
namespace Riki\AdvancedInventory\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ReAssignation extends AbstractDb
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_advancedinventory_reassignation', 'id');
    }
}
