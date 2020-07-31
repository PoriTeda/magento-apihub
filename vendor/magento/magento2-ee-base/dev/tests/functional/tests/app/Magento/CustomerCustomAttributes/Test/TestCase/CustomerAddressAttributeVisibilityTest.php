<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\TestCase;

use Magento\Mtf\TestCase\Scenario;

/**
 * @ZephyrId MAGETWO-70064
 */
class CustomerAddressAttributeVisibilityTest extends Scenario
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
