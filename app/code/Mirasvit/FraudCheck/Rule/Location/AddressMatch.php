<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\FraudCheck\Rule\Location;

use Mirasvit\FraudCheck\Rule\AbstractRule;

class AddressMatch extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Customer Location');
    }

    /**
     * {@inheritdoc}
     */
    public function getFraudScore()
    {
        return $this->calculateFraudScore(-2, 2);
    }

    /**
     * @return int
     */
    public function getDefaultImportance()
    {
        return 9;
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $ip = $this->context->getIp();
        $ipLocation = $this->context->getMatchService()->getIpLocation($ip);

        $ipCountry = $ipLocation ? $ipLocation->getCountryCode() : '';
        $billingCountry = $this->context->getBillingCountry();
        $shippingCountry = $this->context->getShippingCountry();

        if (!$ipCountry) {
            $this->addIndicator(-2, __("Can't determine country for IP: %1", $ip));

            return;
        }

        if ($billingCountry) {
            if ($ipCountry == $billingCountry) {
                $this->addIndicator(1,
                    __('Order placed from %1 and billing address is in %2', $ipCountry, $billingCountry));
            } else {
                $this->addIndicator(-1,
                    __('Order placed from %1, but billing address is in %2', $ipCountry, $billingCountry));
            }
        } elseif ($billingCountry) {
            $this->addIndicator(-1,
                __("Billing country is not set", $billingCountry));
        }

        if ($shippingCountry) {
            if ($ipCountry == $shippingCountry) {
                $this->addIndicator(1,
                    __('Order placed from %1 and shipping address is in %2', $ipCountry, $shippingCountry));
            } else {
                $this->addIndicator(-1,
                    __('Order placed from %1, but shipping address is in %2', $ipCountry, $shippingCountry));
            }
        } elseif ($shippingCountry) {
            $this->addIndicator(-1,
                __("Shipping country is not set", $shippingCountry));
        }

        return;
    }
}