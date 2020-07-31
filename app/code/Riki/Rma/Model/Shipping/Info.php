<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Riki_Rma
 * @package  Riki\Rma\Model\Shipping
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
namespace Riki\Rma\Model\Shipping;
/**
 * Class Info
 *
 * @category Riki_Rma
 * @package  Riki\Rma\Model\Shipping
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
class Info extends \Magento\Rma\Model\Shipping\Info
{
    /**
     * Retrieve tracking by tracking entity id
     *
     * @return array
     */
    public function getTrackingInfoByTrackId()
    {
        /** @var $track \Magento\Rma\Model\Shipping */
        $track = $this->_shippingFactory->create()->load($this->getTrackId());
        if ($track->getId() && $this->getProtectCode() === $track->getProtectCode()) {
            $this->_trackingInfo = [[$track]];
        }
        return $this->_trackingInfo;
    }

}
