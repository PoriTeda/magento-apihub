<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

abstract class AbstractCronJobSectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\Report\Group\Cron\CronJobs|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cronJobsMock;

    /**
     * @var \Magento\Support\Model\Report\Group\Cron\AbstractCronJobsSection
     */
    protected $report;

    /**
     * @var array
     */
    protected $cronJobs = [
        'clear_cache' => [
            'name' => 'clear_cache',
            'expression' => '*/1 * * * *',
            'instance' => 'Vendor\Module\Class',
            'method' => 'clear',
            'group_code' => 'default'
        ]
    ];

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->cronJobsMock = $this->createMock(\Magento\Support\Model\Report\Group\Cron\CronJobs::class);

        $this->cronJobsMock->expects($this->once())
            ->method('getCronInformation')
            ->with($this->cronJobs['clear_cache'])
            ->willReturn($this->cronJobs['clear_cache']);
    }

    /**
     * @param string $title
     * @param array $data
     * @return array
     */
    protected function getExpectedResult($title, $data)
    {
        return [
            $title => [
                'headers' => [
                    __('Job Code'), __('Cron Expression'), __('Run Class'), __('Run Method'), __('Group Code')
                ],
                'data' => $data
            ]
        ];
    }
}
