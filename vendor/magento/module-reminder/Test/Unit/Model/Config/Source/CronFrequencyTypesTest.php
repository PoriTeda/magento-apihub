<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Test\Unit\Model\Config\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reminder\Model\Config\Source\CronFrequencyTypes;

class CronFrequencyTypesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reminder\Model\Config\Source\CronFrequencyTypes
     */
    private $model;

    /**
     * @return void
     */
    protected function setUp()
    {
        $helper = new ObjectManager($this);

        $this->model = $helper->getObject(\Magento\Reminder\Model\Config\Source\CronFrequencyTypes::class);
    }

    /**
     * @return void
     */
    public function testGetCronFrequencyTypes()
    {
        $expected = [
            CronFrequencyTypes::CRON_MINUTELY => __('Minute Intervals'),
            CronFrequencyTypes::CRON_HOURLY => __('Hourly'),
            CronFrequencyTypes::CRON_DAILY => __('Daily'),
        ];

        $this->assertEquals($expected, $this->model->getCronFrequencyTypes());
    }
}
