<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\TestCase;

use Magento\Mtf\TestCase\Scenario;

/**
 * @ZephyrId MAGETWO-70076
 */
class DefaultCustomerAddressAttributesVisibilityTest extends Scenario
{
    /**
     * Run test
     *
     * @return void
     */
    public function test()
    {
        $this->executeScenario();
    }
}
