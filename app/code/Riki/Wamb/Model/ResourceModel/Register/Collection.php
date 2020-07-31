<?php


namespace Riki\Wamb\Model\ResourceModel\Register;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Riki\Wamb\Model\Register::class, \Riki\Wamb\Model\ResourceModel\Register::class
        );
    }
}
