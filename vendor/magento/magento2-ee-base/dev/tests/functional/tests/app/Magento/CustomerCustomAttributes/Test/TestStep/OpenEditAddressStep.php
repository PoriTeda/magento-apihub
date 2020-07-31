<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\TestStep;

use Magento\Customer\Test\Page\CustomerAccountIndex;
use Magento\Mtf\TestStep\TestStepInterface;

/**
 * Open edit address step
 */
class OpenEditAddressStep implements TestStepInterface
{
    /**
     * Customer account index
     *
     * @var CustomerAccountIndex
     */
    private $customerAccountIndex;

    /**
     * @param CustomerAccountIndex $customerAccountIndex
     */
    public function __construct(CustomerAccountIndex $customerAccountIndex)
    {
        $this->customerAccountIndex = $customerAccountIndex;
    }

    /**
     * Create customer account.
     *
     * @return array
     */
    public function run()
    {
        $this->customerAccountIndex->getDashboardAddress()->editBillingAddress();
        return [];
    }
}
