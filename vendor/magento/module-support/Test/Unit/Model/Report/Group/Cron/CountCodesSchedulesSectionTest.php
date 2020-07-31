<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit_Framework_MockObject_MockObject as ObjectMock;

class CountCodesSchedulesSectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory|ObjectMock
     */
    protected $scheduleCollectionFactoryMock;

    /**
     * @var \Psr\Log\LoggerInterface|ObjectMock
     */
    protected $loggerMock;

    /**
     * @var \Magento\Support\Model\Report\Group\Cron\CountCodesSchedulesSection
     */
    protected $report;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->scheduleCollectionFactoryMock = $this->createPartialMock(
            \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory::class,
            ['create']
        );
        $this->loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);

        $this->report = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\Report\Group\Cron\CountCodesSchedulesSection::class,
            [
                'scheduleCollectionFactory' => $this->scheduleCollectionFactoryMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGenerate()
    {
        $table = 'cron_schedule';
        $sql = "SELECT COUNT( * ) AS `cnt`, `job_code`
                FROM `" . $table . "`
                GROUP BY `job_code`
                ORDER BY `job_code`";
        $result = [
            ['job_code' => 'clear_cache', 'cnt' => 1],
            ['job_code' => 'tax_index', 'cnt' => 2],
        ];

        /** @var \Magento\Framework\DB\Adapter\AdapterInterface|ObjectMock $adapter */
        $adapter = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('fetchAll')
            ->with($sql)
            ->willReturn($result);

        /** @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb|ObjectMock $abstractDb */
        $abstractDb = $this->getMockBuilder(\Magento\Framework\Model\ResourceModel\Db\AbstractDb::class)
            ->setMethods(['getConnection', 'getTable'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $abstractDb->expects($this->once())
            ->method('getConnection')
            ->willReturn($adapter);

        $abstractDb->expects($this->once())
            ->method('getTable')
            ->willReturn($table);

        /** @var \Magento\Cron\Model\ResourceModel\Schedule\Collection|ObjectMock $collection */
        $collection = $this->createMock(\Magento\Cron\Model\ResourceModel\Schedule\Collection::class);
        $collection->expects($this->once())
            ->method('getResource')
            ->willReturn($abstractDb);

        $this->scheduleCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $this->setExpectedResult([['clear_cache', 1], ['tax_index', 2]]);
    }

    /**
     * @return void
     */
    public function testGenerateWithException()
    {
        $e = new \Exception('Test exception');
        $this->scheduleCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willThrowException($e);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($e);

        $this->setExpectedResult();
    }

    /**
     * @param array $data
     * @return void
     */
    protected function setExpectedResult($data = [])
    {
        $expectedResult = [
            'Cron Schedules by job code' => [
                'headers' => [__('Job Code'), __('Count')],
                'data' => $data
            ]
        ];
        $this->assertEquals($expectedResult, $this->report->generate());
    }
}
