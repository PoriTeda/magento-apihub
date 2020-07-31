<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Ui\Component\Listing\Column;

use Magento\Support\Ui\Component\Listing\Column\CodeDump;

class CodeDumpTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Ui\Component\Listing\Column\CodeDump
     */
    protected $codeDump;

    /**
     * @var \Magento\Support\Model\BackupFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backupFactoryMock;

    /**
     * @var \Magento\Support\Model\Backup|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backupMock;

    /**
     * @var \Magento\Support\Model\Backup\Status|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $statusMock;

    /**
     * @var \Magento\Support\Model\Backup\Item\Code|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemCodeMock;

    /**
     * @var \Magento\Support\Model\Backup\Item\Db|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemDbMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->backupMock = $this->createMock(\Magento\Support\Model\Backup::class);
        $this->backupFactoryMock = $this->createPartialMock(\Magento\Support\Model\BackupFactory::class, ['create']);
        $this->backupFactoryMock->expects($this->once())->method('create')->willReturn($this->backupMock);

        $this->statusMock = $this->getMockBuilder(\Magento\Support\Model\Backup\Status::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemCodeMock = $this->getMockBuilder(\Magento\Support\Model\Backup\Item\Code::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemDbMock = $this->getMockBuilder(\Magento\Support\Model\Backup\Item\Db::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\ContextInterface::class)
            ->getMockForAbstractClass();
        $processor = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->never())->method('getProcessor')->willReturn($processor);

        $this->codeDump = $this->objectManager->getObject(
            \Magento\Support\Ui\Component\Listing\Column\CodeDump::class,
            [
                'status' => $this->statusMock,
                'backupFactory' => $this->backupFactoryMock,
                'context' => $contextMock,
            ]
        );
    }

    public function testPrepareDataSource()
    {
        $link = 'http://magento2.loc/admin/support/backup/download/backup_id/1/type/1/';
        $codeDumbLabel = 'd7fbf8df3c6e65b2dee080788281f83f.tar.gz';
        $size = '35.8 MiB';
        $log = 'Code dump was created successfully.';
        $lastUpdate = '2015-08-19 14:54:42';

        $dataSource = [
            'data' => [
                'totalRecords' => 1,
                'items' => [
                    [
                        'id_field_name' => 'backup_id',
                        'backup_id' => '1',
                        'name' => 'd7fbf8df3c6e65b2dee080788281f83f',
                        'status' => '1',
                        'last_update' => $lastUpdate,
                        'log' => $log,
                        'orig_data' => null
                    ]
                ]
            ]
        ];

        $expectedResult = [
            'data' => [
                'totalRecords' => 1,
                'items' => [
                    [
                        'id_field_name' => 'backup_id',
                        'backup_id' => '1',
                        'name' => [
                            'label' => $codeDumbLabel,
                            'value' => [
                                'isLink' => 1,
                                'link' => $link
                            ],
                            'size' => $size
                        ],
                        'status' => '1',
                        'last_update' => $lastUpdate,
                        'log' => $log,
                        'orig_data' => null
                    ]
                ]
            ]
        ];

        $items = [
            'code' => $this->itemCodeMock,
            'db' => $this->itemDbMock
        ];

        $this->codeDump->setData(['name' => 'name']);

        $this->backupMock->expects($this->atLeastOnce())->method('setData');
        $this->backupMock->expects($this->once())->method('getItems')->willReturn($items);

        $this->statusMock->expects($this->once())->method('getCodeDumpLabel')->willReturn($codeDumbLabel);
        $this->statusMock->expects($this->once())->method('getValue')
            ->willReturn(
                [
                    'isLink' => 1,
                    'link' => $link
                ]
            );
        $this->statusMock->expects($this->once())->method('getSize')->willReturn($size);
        $this->assertEquals($expectedResult, $this->codeDump->prepareDataSource($dataSource));
    }
}
