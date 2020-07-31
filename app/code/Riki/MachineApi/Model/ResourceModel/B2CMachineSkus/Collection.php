<?php
namespace Riki\MachineApi\Model\ResourceModel\B2CMachineSkus;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'type_id';

    public function _construct()
    {
        $this->_init(
            \Riki\MachineApi\Model\B2CMachineSkus::class,
            \Riki\MachineApi\Model\ResourceModel\B2CMachineSkus::class
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        return $this;
    }
}
