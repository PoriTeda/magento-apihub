<?php

namespace Riki\Preorder\Block\Product\ListProduct;

use Magento\Framework\View\Element\Template;

class Preorder extends \Magento\Framework\View\Element\Template
{
    const PRODUCT = 'product';
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $helper;

    /**
     * Note constructor.
     * @param Template\Context $context
     * @param \Riki\Preorder\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Riki\Preorder\Helper\Data $helper,
        array $data = []
    )
    {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getPreorderNote()
    {
        return $this->helper->getProductPreorderNote($this->getProduct());
    }

    /**
     * @return string
     */
    public function getCartLabel()
    {
        return $this->helper->getProductPreorderCartLabel($this->getProduct());
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     */
    public function setProduct(\Magento\Catalog\Model\Product $product)
    {
        $this->setData(static::PRODUCT, $product);
        return $this;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->getData(static::PRODUCT);
    }
}