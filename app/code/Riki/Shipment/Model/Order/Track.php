<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Riki_Shipment
 * @package  Riki\Shipment\Model\Order
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
namespace Riki\Shipment\Model\Order;
/**
 * Class Track
 *
 * @category Riki_Shipment
 * @package  Riki\Shipment\Model\Order
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
class Track extends \Magento\Shipping\Model\Order\Track
{
    /**
     * Retrieve detail for shipment track
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getNumberDetail()
    {
        $carrierInstance = $this->_carrierFactory->create('');
        if (!$carrierInstance) {
            $custom = [];
            $custom['title'] = $this->getTitle();
            $custom['number'] = $this->getTrackNumber();
            return $custom;
        } else {
            $carrierInstance->setStore($this->getStore());
        }

        $trackingInfo = $carrierInstance->getTrackingInfo($this->getNumber());
        if (!$trackingInfo) {
            return __('No detail for number "%1"', $this->getNumber());
        }

        return $trackingInfo;
    }
}