<?php
namespace Riki\Rma\Model\ResourceModel\ReviewCc;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Item extends AbstractDb
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_rma_review_cc_item', 'item_id');
    }
}
