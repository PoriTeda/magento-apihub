<?php

namespace Riki\SubscriptionMachine\Block\Adminhtml\Skus\Edit;

use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class SearchProduct extends \Magento\Backend\Block\Widget implements  RendererInterface
{
    /**
     * @var string
     */
    protected $_template = 'edit/skus_searchproduct.phtml';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     *
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setId('skus_search_product');
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $oSearchProductGrid = $this->getLayout()->createBlock(
            'Riki\SubscriptionMachine\Block\Adminhtml\Skus\Edit\SearchProduct\GridProduct'
        );
        $this->setChild('skus_search_product_grid',$oSearchProductGrid);
        return $this;
    }

    /**
     * Render
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->toHtml();
    }

    /**
     * Get Header Text
     */
    public function getHeaderText()
    {
        return __('Please select product sku by clicking on a row');
    }
}
