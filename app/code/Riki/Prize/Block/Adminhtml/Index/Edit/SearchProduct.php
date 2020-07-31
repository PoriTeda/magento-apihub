<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Prize\Block\Adminhtml\Index\Edit;

use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Adminhtml customer enquiry search customer create abstract block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SearchProduct extends \Magento\Backend\Block\Widget implements  RendererInterface
{

    /**
     * @var string
     */
    protected $_template = 'edit/searchproduct.phtml';

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
        $this->setId('prize_search_product');
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $oSearchProductGrid = $this->getLayout()->createBlock('Riki\Prize\Block\Adminhtml\Index\Edit\SearchProduct\GridProduct');
        $this->setChild('prize_search_product_grid',$oSearchProductGrid);
        return $this;
    }

    /**
     * Render
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element){
        return $this->toHtml();
    }

    /**
     * Get Header Text
     */
    public function getHeaderText(){
        return __('Please select product sku by clicking on a row');
    }
}
