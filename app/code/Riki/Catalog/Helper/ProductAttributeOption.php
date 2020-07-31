<?php
namespace Riki\Catalog\Helper;

class ProductAttributeOption extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory
     */
    protected $attributeOptionLabelFactory;

    /**
     * @var \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory
     */
    protected $attributeOptionFactory;

    /**
     * @var \Magento\Eav\Api\AttributeOptionManagementInterface
     */
    protected $attributeOptionManagement;

    /**
     * @var \Magento\Eav\Model\AttributeRepository
     */
    protected $attributeRepository;

    /**
     * ProductAttributeOption constructor.
     *
     * @param \Magento\Eav\Model\AttributeRepository $attributeRepository
     * @param \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory $attributeOptionLabelInterfaceFactory
     * @param \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $attributeOptionInterfaceFactory
     * @param \Magento\Eav\Api\AttributeOptionManagementInterface $attributeOptionManagement
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Eav\Model\AttributeRepository $attributeRepository,
        \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory $attributeOptionLabelInterfaceFactory,
        \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $attributeOptionInterfaceFactory,
        \Magento\Eav\Api\AttributeOptionManagementInterface $attributeOptionManagement,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->attributeOptionLabelFactory = $attributeOptionLabelInterfaceFactory;
        $this->attributeOptionFactory = $attributeOptionInterfaceFactory;

        parent::__construct($context);
    }

    /**
     * Get a attribute
     *
     * @param $attributeCode
     *
     * @return \Magento\Eav\Api\Data\AttributeInterface|null
     */
    public function getAttribute($attributeCode)
    {
        return $this->attributeRepository->get(\Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE, $attributeCode);
    }

    /**
     * Get options of attribute
     *
     * @param $attributeCode
     *
     *
     * @return \Magento\Eav\Api\Data\AttributeOptionInterface[]|null
     * @throws \Magento\Framework\Exception\StateException
     */
    public function getOptions($attributeCode)
    {
        $attribute = $this->getAttribute($attributeCode);
        $attribute->setStoreId(0);

        try {
            $options = $attribute->getOptions();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\StateException(__('Cannot load options for attribute %1', $attributeCode));
        }

        return $options;
    }

    /**
     * Check have option label or not
     *
     * @param $attributeCode
     * @param $label
     * @param $type
     *
     * @return bool|int|string
     */
    public function hasOptionLabel($attributeCode, $label, $type = 'value')
    {
        $useSourceModel = false;
        $attribute = $this->getAttribute($attributeCode);
        if ($attribute->getSourceModel() != $attribute->_getDefaultSourceModel()) {
            $useSourceModel = true;
        }
        $options = $this->getOptions($attributeCode);
        foreach ($options as $option) {
            if ($useSourceModel) {
                $optionLabel = $type == 'label' ? (string)$option->getLabel() : $option->getValue();
            } else {
                $optionLabel = (string)$option->getLabel();
            }
            if ($optionLabel == $label) {
                return $option->getValue();
            }
        }

        return false;
    }

    /**
     * Add an option label
     *
     * @param $attributeCode
     * @param $label
     * @return bool
     */
    public function addOptionLabel($attributeCode, $label)
    {
        $attribute = $this->getAttribute($attributeCode);
        if ($attribute->getSourceModel() != $attribute->_getDefaultSourceModel()) {
            return false;
        }

        /** @var \Magento\Eav\Model\Entity\Attribute\OptionLabel $label */
        $optionLabel = $this->attributeOptionLabelFactory->create();
        $optionLabel->setStoreId(0)
            ->setLabel($label);

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
        $option = $this->attributeOptionFactory->create();
        $option->setStoreLabels([$optionLabel])
            ->setIsDefault(false)
            ->setSortOrder(1000);


        return $this->attributeOptionManagement->add(
            \Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE,
            $attributeCode,
            $option
        );
    }
}
