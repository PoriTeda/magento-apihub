<?php
namespace Riki\MachineApi\Model\B2CMachineSkus\Config;

use Magento\Framework\DB\Ddl\Table;

class MachineTypeList extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var OptionFactory
     */
    protected $optionFactory;

    /**
     * @var \Riki\MachineApi\Model\B2CMachineSkusFactory
     */
    protected $machineTypeFactory;

    /**
     * @var \Riki\MachineApi\Model\ResourceModel\B2CMachineSkus\CollectionFactory
     */
    protected $machineTypeCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * MachineTypeList constructor.
     * @param \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory
     * @param \Riki\MachineApi\Model\ResourceModel\B2CMachineSkus $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory,
        \Riki\MachineApi\Model\ResourceModel\B2CMachineSkus $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->machineTypeFactory = $machineTypeFactory;
        $this->resourceModel = $resource;
        $this->storeManager = $storeManager;
    }
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $machineTypeCollection = $this->machineTypeFactory->create()->getCollection()->load();
        $this->_options = [];
        if ($machineTypeCollection->getSize()) {
            /** @var \Riki\MachineApi\Model\B2CMachineSkus $machineType */
            foreach ($machineTypeCollection as $machineType) {
                $this->_options[] = ['label'=> $machineType->getTypeName(), 'value'=> $machineType->getId()];
            }
        }
        return $this->_options ;
    }

    /**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string|bool
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions();
        if (sizeof($options) > 0) {
            foreach ($options as $option) {
                if (isset($option['value']) && $option['value'] == $value) {
                    return isset($option['label']) ? $option['label'] : $option['value'];
                }
            }
        }

        if (isset($options[$value])) {
            return $options[$value];
        }
        return false;
    }

    /**
     * Retrieve flat column definition
     *
     * @return array
     */
    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        return [
            $attributeCode => [
                'unsigned' => false,
                'default' => null,
                'extra' => null,
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Custom Attribute Options  ' . $attributeCode . ' column',
            ],
        ];
    }
}
