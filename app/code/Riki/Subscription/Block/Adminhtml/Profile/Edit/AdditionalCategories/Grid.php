<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Block\Adminhtml\Profile\Edit\AdditionalCategories;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;

/**
 * Adminhtml enquiry create search customer block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Grid extends \Riki\Subscription\Block\Adminhtml\Profile\Edit\MainProduct\Grid
{

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('additional_product_grid');
        $this->setUseAjax(true);
        $this->setRowClickCallback('profileAdditionalProductAdd.productGridRowClick.bind(profileAdditionalProductAdd)');
        $this->setCheckboxCheckCallback('profileAdditionalProductAdd.productGridCheckboxCheck.bind(profileAdditionalProductAdd)');
        $this->setRowInitCallback('profileAdditionalProductAdd.productGridRowInit.bind(profileAdditionalProductAdd)');
        $this->setDefaultSort('entity_id');
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    /**
     * Prepare collection to be displayed in the grid
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $profileId = $this->getRequest()->getParam('id');
        if($profileId) {
            $subscriptionCourseResourceModel = $this->helperProfile->getSubscriptionCourseResourceModel();
            $profileData = $this->helperProfile->load($profileId);

            if(!$this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID)){
                $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $profileData->getData("course_id"));
            }

            $iFrequencyId = $this->subscriptionHelper->getFrequencyIdByUnitAndInterval($profileData->getData("frequency_unit"),$profileData->getData("frequency_interval"));

            if($iFrequencyId && !$this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID)){
                $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $iFrequencyId);
            }

            if(!$this->_registry->registry('subscription_profile_obj')){
                $this->_registry->register('subscription_profile_obj',$profileData );
            }

            if($profileData->getId()) {
                $products = $subscriptionCourseResourceModel->getAllProductByCourse($profileData->getData("course_id"), $profileData->getData("store_id"),null,1);
                if($products instanceof  \Magento\Catalog\Model\ResourceModel\Product\Collection) {
                    $productIds = $products->getAllIds();
                    if ($productIds) { // improve performance by decrease load catalog rule
                        $this->catalogRuleHelper->registerPreLoadedProductIds($productIds);
                    }
                }
            }
            else{
                $products = null;
            }
        }
        else{
            $products =  null;
        }
        $this->setCollection($products);
        return \Magento\Backend\Block\Widget\Grid\Extended::_prepareCollection();
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'profile/profile_edit/LoadBlock',
            ['block' => 'additionalcategories_grid', '_current' => true, 'collapse' => null]
        );
    }
}
