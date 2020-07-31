<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Test\Unit\Model\Cart\Sku\Source;

use Magento\AdvancedCheckout\Model\Cart\Sku\Source\Settings;

class SettingsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Settings
     */
    private $model;

    protected function setUp()
    {
        $this->model = new Settings();
    }

    public function testToOptionArray()
    {
        $expectedResult = [
            ['label' => __('Yes, for Specified Customer Groups'), 'value' => Settings::YES_SPECIFIED_GROUPS_VALUE],
            ['label' => __('Yes, for Everyone'), 'value' => Settings::YES_VALUE],
            ['label' => __('No'), 'value' => Settings::NO_VALUE]
        ];
        $this->assertEquals($expectedResult, $this->model->toOptionArray());
    }
}
