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

class Email extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Customer Email');
    }

    /**
     * {@inheritdoc}
     */
    public function getFraudScore()
    {
        return $this->calculateFraudScore(-1, 2);
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $email = strtolower($this->context->getEmail());
        $first = strtolower($this->context->getFirstname());
        $last = strtolower($this->context->getLastname());

        $parsed = explode('@', $email);
        $identifier = $parsed[0];

        if ($first && strpos($identifier, $first) !== false) {
            $this->addIndicator(1, __('Email (%1) contains firstname', $email));
        }
        if ($last && strpos($identifier, $last) !== false) {
            $this->addIndicator(1, __('Email (%1) contains lastname', $email));
        }

        if (strpos($email, 'example') !== false) {
            $this->addIndicator(-1, __('Email (%1) contains blacklist keyword', $email));
        }

        if (!count($this->indicators)) {
            $this->addIndicator(0, __("The customer's email (%1) not match any risky patterns", $email));
        }
    }
}