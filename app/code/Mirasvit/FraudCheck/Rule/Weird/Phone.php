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


namespace Mirasvit\FraudCheck\Rule\Weird;

use Mirasvit\FraudCheck\Rule\AbstractRule;

class Phone extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Phone number');
    }

    /**
     * {@inheritdoc}
     */
    public function getFraudScore()
    {
        return $this->calculateFraudScore(-2, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $phone = $this->context->getBillingPhone();
        $phone = preg_replace('/[^0-9]/', '', $phone);;

        if (preg_match('/([0-9])\1{3}/', $phone)) {
            #4 repeating digits 111122223333
            $this->addIndicator(-1, __('Phone (%1) contains 4 repeating digits', $phone));
        } elseif (preg_match('/([0-9])\1{2}/', $phone)) {
            #3 repeating digits 111222333
            $this->addIndicator(-1, __('Phone (%1) contains 3 repeating digits', $phone));
        }

        if (preg_match('/([0-9])([0-9])([0-9])\1\2\3/', $phone)) {
            #3 repeating digits sequence 123123123123
            $this->addIndicator(-1, __('Phone (%1) contains 3 repeating digits sequence', $phone));
        } elseif (preg_match('/([0-9])([0-9])\1\2/', $phone)) {
            #2 repeating digits sequence 12121212
            $this->addIndicator(-1, __('Phone (%1) contains 2 repeating digits sequence', $phone));
        }

        if (!count($this->indicators)) {
            $this->addIndicator(0,
                __("The customer's phone number (%1) not match any risky patterns", $phone));
        }
    }
}