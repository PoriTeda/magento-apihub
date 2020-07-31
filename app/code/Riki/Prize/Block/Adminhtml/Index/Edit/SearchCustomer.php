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
class SearchCustomer extends \Magento\Backend\Block\Widget implements  RendererInterface
{

    /**
     * @var string
     */
    protected $_template = 'edit/searchcustomer.phtml';

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
        $this->setId('prize_search_customer');
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $oSearchCustomerGrid = $this->getLayout()->createBlock('Riki\Prize\Block\Adminhtml\Index\Edit\SearchCustomer\GridCustomer');
        $this->setChild('prize_search_customer_grid',$oSearchCustomerGrid);
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
        return __('Please select customer id by clicking on a row');
    }
}
