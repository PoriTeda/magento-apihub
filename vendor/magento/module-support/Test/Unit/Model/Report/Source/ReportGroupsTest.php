<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Source;

class ReportGroupsTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Support\Model\Report\Source\ReportGroups */
    protected $reportGroups;

    /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    protected $objectManager;

    /** @var \Magento\Support\Model\Report\Config|\PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var array */
    protected $options = [];

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->config = $this->createMock(\Magento\Support\Model\Report\Config::class);

        $this->reportGroups = $this->objectManager->getObject(
            \Magento\Support\Model\Report\Source\ReportGroups::class,
            [
                'config' => $this->config,
            ]
        );
    }

    /**
     * @return void
     */
    public function testSelectedDelete()
    {
        $generalLabel = __('General');
        $environmentLabel = __('Environment');
        $groupOptions = [
            [
                'value' => 'general',
                'label' => $generalLabel
            ],
            [
                'value' => 'environment',
                'label' => $environmentLabel
            ]
        ];

        $expectedOptions = [
            [
                'label' => '',
                'value' => ''
            ],
            [
                'value' => 'general',
                'label' => $generalLabel
            ],
            [
                'value' => 'environment',
                'label' => $environmentLabel
            ],
        ];

        $this->config->expects($this->once())->method('getGroupOptions')->willReturn($groupOptions);

        $this->assertSame($expectedOptions, $this->reportGroups->toOptionArray());
    }
}
