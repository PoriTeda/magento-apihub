<?php


namespace Riki\Wamb\Model\ResourceModel\History;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Riki\Wamb\Model\History::class, \Riki\Wamb\Model\ResourceModel\History::class);
    }
}
