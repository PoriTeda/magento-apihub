<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Test class for \Magento\ScheduledImportExport\Model\Import
 */
namespace Magento\ScheduledImportExport\Test\Unit\Model;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Enterprise data import model
     *
     * @var \Magento\ScheduledImportExport\Model\Import
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_importConfigMock;

    /**
     * Init model for future tests
     */
    protected function setUp()
    {
        $this->_importConfigMock = $this->createMock(\Magento\ImportExport\Model\Import\ConfigInterface::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $indexerRegistry = $this->createMock(\Magento\Framework\Indexer\IndexerRegistry::class);
        $this->_model = new \Magento\ScheduledImportExport\Model\Import(
            $logger,
            $this->createMock(\Magento\Framework\Filesystem::class),
            $this->createMock(\Magento\ImportExport\Helper\Data::class),
            $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class),
            $this->_importConfigMock,
            $this->createMock(\Magento\ImportExport\Model\Import\Entity\Factory::class),
            $this->createMock(\Magento\ImportExport\Model\ResourceModel\Import\Data::class),
            $this->createMock(\Magento\ImportExport\Model\Export\Adapter\CsvFactory::class),
            $this->createMock(\Magento\Framework\HTTP\Adapter\FileTransferFactory::class),
            $this->createPartialMock(\Magento\MediaStorage\Model\File\UploaderFactory::class, ['create']),
            $this->createMock(\Magento\ImportExport\Model\Source\Import\Behavior\Factory::class),
            $indexerRegistry,
            $this->createMock(\Magento\ImportExport\Model\History::class),
            $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class)
        );
    }

    /**
     * Unset test model
     */
    protected function tearDown()
    {
        unset($this->_model);
    }

    /**
     * Test for method 'initialize'
     */
    public function testInitialize()
    {
        /**
         * @var $operation \Magento\ScheduledImportExport\Model\Scheduled\Operation
         */
        $operation = $this->createPartialMock(\Magento\ScheduledImportExport\Model\Scheduled\Operation::class, [
                '__wakeup',
                'getFileInfo',
                'getEntityType',
                'getBehavior',
                'getOperationType',
                'getStartTime',
                'getId',
            ]);
        $fileInfo = [
            'entity_type' => 'another customer',
            'behavior' => 'replace',
            'operation_type' => 'import',
            'custom_option' => 'value',
        ];
        $operationData = [
            'entity' => 'test entity',
            'behavior' => 'customer',
            'operation_type' => 'update',
            'run_at' => '00:00:00',
            'scheduled_operation_id' => 1,
        ];

        $operation->expects($this->once())->method('getFileInfo')->willReturn($fileInfo);
        $operation->expects($this->once())->method('getEntityType')->willReturn($operationData['entity']);
        $operation->expects($this->once())->method('getBehavior')->willReturn($operationData['behavior']);
        $operation->expects($this->once())->method('getOperationType')->willReturn($operationData['operation_type']);
        $operation->expects($this->once())->method('getStartTime')->willReturn($operationData['run_at']);
        $operation->expects($this->once())->method('getId')->willReturn($operationData['scheduled_operation_id']);

        $importMock = $this->createPartialMock(\Magento\ScheduledImportExport\Model\Import::class, [
                'setData'
            ]);
        $expectedData = array_merge($fileInfo, $operationData);
        $importMock->expects($this->once())->method('setData')->with($expectedData);

        $actualResult = $importMock->initialize($operation);
        $this->assertEquals($importMock, $actualResult);
    }
}
