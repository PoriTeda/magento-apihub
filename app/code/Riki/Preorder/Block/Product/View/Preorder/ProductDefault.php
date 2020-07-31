<?php

namespace Riki\Preorder\Block\Product\View\Preorder;

use Magento\Framework\View\Element\Template;

class ProductDefault extends \Magento\Catalog\Block\Product\View\AbstractView
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

    public function canShowBlock()
    {
        return $this->helper->getIsProductPreorder($this->getProduct());
    }


    public function getCartLabel()
    {
        return $this->helper->getProductPreorderCartLabel($this->getProduct());
    }

    public function getPreorderNote()
    {
        return $this->helper->getProductPreorderNote($this->getProduct());
    }
}
