<?php

namespace Riki\Preorder\Block\Product\View\Preorder;

class ProductGrouped extends \Magento\Catalog\Block\Product\View\AbstractView
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


    public function getGroupPreorderMap()
    {
        /** @var \Magento\GroupedProduct\Model\Product\Type\Grouped $typeInstance */
        $typeInstance = $this->getProduct()->getTypeInstance();

        $elementaryProducts = $typeInstance->getAssociatedProducts($this->getProduct());

        $map = array();
        foreach ($elementaryProducts as $product) {
            /** @var \Magento\Catalog\Model\Product $product */
            if($this->helper->getIsProductPreorder($product)) {
                $map[$product->getId()] = [
                    'cartLabel' => $this->helper->getProductPreorderCartLabel($product),
                    'note'      => $this->helper->getProductPreorderNote($product),
                ];
            }
        }

        return $map;
    }
}
