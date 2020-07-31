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
class Deliverytype extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render gift registry item qty as input html element
     *
     * @param  \Magento\Framework\DataObject $row
     * @return string
     */
    protected $helperProfile;
    public function __construct(
        Context $context,
        \Riki\Subscription\Helper\Profile\Data $helperProfile
    )
    {
        $this->helperProfile = $helperProfile;
        parent::__construct($context, []);
    }

    protected function _getValue(\Magento\Framework\DataObject $row)
    {
        $profileId = $row->getData('profile_id');
        $deliveryType = $this->helperProfile->getDeliveryTypeOfProfile($profileId);

        return $deliveryType;
    }
}
