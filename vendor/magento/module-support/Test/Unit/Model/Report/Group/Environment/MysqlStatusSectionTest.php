<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Environment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class MysqlStatusSectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Model\Report\Group\Environment\MysqlStatusSection
     */
    protected $mysqlStatusReport;

    /**
     * @var \Magento\Framework\Module\ModuleResource|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceConnectionMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->resourceConnectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock = $this->createMock(\Magento\Framework\Module\ModuleResource::class);
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->resourceConnectionMock);
        $this->mysqlStatusReport = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\Report\Group\Environment\MysqlStatusSection::class,
            ['resource' => $this->resourceMock]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $variables = [
            'Aborted_clients' => '0',
            'Aborted_connects' => '0',
            'Slave_running' => 'OFF',
            'Com_select' => '0',
            'Test_info' => '0'
        ];
        $variablesAfter10Sec = [
            'Aborted_clients' => '0',
            'Aborted_connects' => '1',
            'Slave_running' => 'OFF',
            'Test_info' => '1'
        ];
        $expectedResult = [
            \Magento\Support\Model\Report\Group\Environment\MysqlStatusSection::REPORT_TITLE => [
                'headers' => ['Variable', 'Value', 'Value after 10 sec'],
                'data' => [
                    ['Aborted_clients', '0', '0'],
                    ['Aborted_connects', '0', '1 (diff: +1)'],
                    ['Slave_running', 'OFF', 'OFF'],
                    ['Com_select', '0', 'n/a']
                ]
            ]
        ];

        $this->resourceConnectionMock->expects($this->at(0))
            ->method('fetchPairs')
            ->with('SHOW GLOBAL STATUS')
            ->willReturn($variables);
        $this->resourceConnectionMock->expects($this->at(1))
            ->method('fetchPairs')
            ->with('SHOW GLOBAL STATUS')
            ->willReturn($variablesAfter10Sec);
        $this->assertSame($expectedResult, $this->mysqlStatusReport->generate());
    }
}
