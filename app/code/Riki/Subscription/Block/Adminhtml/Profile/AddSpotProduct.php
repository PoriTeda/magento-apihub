<?php

namespace Riki\Subscription\Block\Adminhtml\Profile;

use Magento\Backend\Block\Widget\Grid\Container;

class AddSpotProduct extends Container
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * Filter builder
     *
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;
    
    protected function _construct()
    {
        $this->_controller = 'adminhtml_profile_addSpotProduct';
        $this->_blockGroup = 'Riki_Subscription';
        parent::_construct();
        $this->removeButton('add');
    }


    public function _prepareLayout()
    {
        $this->getToolbar()->addChild(
            'add_spot_product',
            'Magento\Backend\Block\Widget\Button',
            [
                'label' => __('Add Spot'),
                'class' => 'action primary save button-add-spot-product',
            ]
        );

        return parent::_prepareLayout();
    }

}