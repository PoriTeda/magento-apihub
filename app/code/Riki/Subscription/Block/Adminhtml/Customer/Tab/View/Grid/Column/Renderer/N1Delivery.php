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
class N1Delivery extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render gift registry item qty as input html element
     *
     * @param  \Magento\Framework\DataObject $row
     * @return string
     */
    protected $helperProfile;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    public function __construct(
        Context $context,
        \Riki\Subscription\Helper\Profile\Data $helperProfile
    )
    {
        $this->helperProfile = $helperProfile;
        $this->_timezone = $context->getLocaleDate();
        parent::__construct($context, []);
    }

    protected function _getValue(\Magento\Framework\DataObject $row)
    {
        $profileId = $row->getData('profile_id');
        $arrNDelivery = $this->helperProfile->getArrThreeDeliveryOfProfile($profileId,'n+1');
        $html = '';
        $deliveryDate = $this->convertDateToTrueFormat($arrNDelivery['delivery_date']);
        $html .= '<span>'.$deliveryDate;
        $html .= '</br>('.__($arrNDelivery['time_slot']).')</span>';
        if ($arrNDelivery['status'] == \Riki\Subscription\Block\Frontend\Profile\Index::PROFILE_STATUS_EDITABLE) {
            $html .= '</br><span class="margin-status next-ship">';
            $html .= __($arrNDelivery['status']);
            $html .= '</span>';
        }
        else {
            $prepareShip = $arrNDelivery['status'] == \Riki\Subscription\Block\Frontend\Profile\Index::PROFILE_STATUS_PLANED?'prepare-ship' : '';
            $html .= '</br><span class="margin-status '.$prepareShip.'" >';
            $html .= __($arrNDelivery['status']);
            $html .= '</span>';
        }
        return $html;
    }
    /**
     * Convert all date to true format YYYY/mm/dd
     *
     * @param string $date
     *
     * @return string
     */
    public function convertDateToTrueFormat($date)
    {
        return $this->_timezone->date(new \DateTime($date))->format('Y/m/d');
    }
}
