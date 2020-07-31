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


namespace Mirasvit\FraudCheck\Rule\Address;

use Mirasvit\FraudCheck\Rule\AbstractRule;

class AddressMatch extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Shipping and billing addresses');
    }

    /**
     * @return int
     */
    public function getDefaultImportance()
    {
        return 8;
    }

    /**
     * {@inheritdoc}
     */
    public function getFraudScore()
    {
        return $this->calculateFraudScore(-4, 4);
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        if ($this->context->getBillingCountry() == $this->context->getShippingCountry()) {
            $this->addIndicator(1, __('The shipping and billing countries are identical'));
        } else {
            $this->addIndicator(-1, __('The shipping and billing countries are different'));
        }

        if ($this->context->getBillingState() == $this->context->getShippingState()) {
            $this->addIndicator(1, __('The shipping and billing states are identical'));
        } else {
            $this->addIndicator(-1, __('The shipping and billing states are different'));
        }

        if ($this->context->getBillingCity() == $this->context->getShippingCity()) {
            $this->addIndicator(1, __('The shipping and billing cities are identical'));
        } else {
            $this->addIndicator(-1, __('The shipping and billing cities are different'));
        }

        if ($this->context->getBillingPostcode() == $this->context->getShippingPostcode()) {
            $this->addIndicator(1, __('The shipping and billing postcodes are identical'));
        } else {
            $this->addIndicator(-1, __('The shipping and billing postcodes are different'));
        }
    }

}