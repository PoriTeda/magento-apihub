<?php

namespace Riki\Catalog\Block\Product\View\Type;

use Magento\Bundle\Model\Option;

class Bundle extends \Magento\Bundle\Block\Catalog\Product\View\Type\Bundle
{
    protected $_optionsByProduct = [];

    public function getOptions($stripSelection = false)
    {
        $product = $this->getProduct();

        if (!isset($this->_optionsByProduct[$product->getId()])) {
            $this->options = null;
            $this->_optionsByProduct[$product->getId()] = parent::getOptions($stripSelection);
        }
        return $this->_optionsByProduct[$product->getId()];
    }

    /**
     * Get html for option
     *
     * @param Option $option
     * @return string
     */
    public function getOptionHtml(Option $option)
    {
        $optionBlock = $this->getChildBlock($option->getType());
        if (!$optionBlock) {
            return __('There is no defined renderer for "%1" option type.', $option->getType());
        }
        return $optionBlock->setOption($option)->setProduct($this->getProduct())->toHtml();
    }
}
