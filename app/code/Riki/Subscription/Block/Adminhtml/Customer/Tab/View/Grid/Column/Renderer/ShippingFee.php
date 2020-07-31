<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Block\Adminhtml\Customer\Tab\View\Grid\Column\Renderer;
use Magento\Backend\Block\Context;

/**
 * Column renderer for gift registry item grid qty column
 * @codeCoverageIgnore
 */
class ShippingFee extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render gift registry item qty as input html element
     *
     * @param  \Magento\Framework\DataObject $row
     * @return string
     */
    protected $helperProfile;
    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    protected  $productCartFactory;

    protected $profileFactory;

    public function __construct(
        Context $context,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
    )
    {
        $this->helperProfile = $helperProfile;
        $this->productCartFactory = $productCartFactory;
        $this->profileFactory = $profileFactory;
        parent::__construct($context, []);
    }

    protected function _getValue(\Magento\Framework\DataObject $row)
    {
        $profileId = $row->getData('profile_id');
        $storeId = null;
        $profileModel = $this->profileFactory->create()->load($profileId);
        if($profileModel->getId()){
            $storeId = $profileModel->getData('store_id');
        }
        $shippingFee = $this->helperProfile->getShippingFeeByProfileId($profileId,$storeId);

        return $shippingFee;
    }
}
