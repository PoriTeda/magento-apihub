<?php

namespace Riki\Preorder\Block\Product\View\Preorder;

class ProductConfigurable extends \Magento\Catalog\Block\Product\View\AbstractView
{
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $helper;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Riki\Preorder\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $arrayUtils, $data);
    }

    public function getConfigurableAttributes()
    {
        $attributes = [];
        /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeInstance */
        $typeInstance = $this->getProduct()->getTypeInstance();
        $allowedAttributes = $typeInstance->getConfigurableAttributes($this->getProduct());
        foreach($allowedAttributes as $attribute) {
            $attributes[$attribute->getProductAttribute()->getId()] = 0;
        }
        return $attributes;
    }


    public function getProductPreorderMap()
    {
        /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeInstance */
        $typeInstance = $this->getProduct()->getTypeInstance();
        $elementaryProducts = $typeInstance->getUsedProducts($this->getProduct());
        $allowedAttributes = $typeInstance->getConfigurableAttributes($this->getProduct());

        $map = array();
        foreach ($elementaryProducts as $product) {
            /** @var \Magento\Catalog\Model\Product $product */
            if($this->helper->getIsProductPreorder($product)) {
                $map[$product->getId()] = [
                    'cartLabel' => $this->helper->getProductPreorderCartLabel($product),
                    'note'      => $this->helper->getProductPreorderNote($product),
                    'attributes' => []
                ];

                foreach($allowedAttributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $productAttributeId = $productAttribute->getId();
                    $attributeValue = $product->getData($productAttribute->getAttributeCode());
                    $map[$product->getId()]['attributes'][$productAttributeId] = $attributeValue;
                }
            }
        }

        return $map;
    }
}
