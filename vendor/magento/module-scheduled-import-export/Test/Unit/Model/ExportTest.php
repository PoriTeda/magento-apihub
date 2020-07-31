<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Test class for \Magento\ScheduledImportExport\Model\Export
 */
namespace Magento\ScheduledImportExport\Test\Unit\Model;

class ExportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Enterprise data export model
     *
     * @var \Magento\ScheduledImportExport\Model\Export
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_exportConfigMock;

    /**
     * Date value for tests
     *
     * @var string
     */
    protected $_date = '2012-07-12';

    /**
     * Init model for future tests
     */
    protected function setUp()
    {
        $dateModelMock = $this->createPartialMock(\Magento\Framework\Stdlib\DateTime\DateTime::class, ['date']);
        $dateModelMock->expects(
            $this->any()
        )->method(
            'date'
        )->will(
            $this->returnCallback([$this, 'getDateCallback'])
        );

        $this->_model = new \Magento\ScheduledImportExport\Model\Export(
            $this->createMock(\Psr\Log\LoggerInterface::class),
            $this->createMock(\Magento\Framework\Filesystem::class),
            $this->createMock(\Magento\ImportExport\Model\Export\ConfigInterface::class),
            $this->createMock(\Magento\ImportExport\Model\Export\Entity\Factory::class),
            $this->createMock(\Magento\ImportExport\Model\Export\Adapter\Factory::class),
            $dateModelMock,
            []
        );
    }

    /**
     * Test for method 'initialize'
     */
    public function testInitialize()
    {
        $operationData = [
            'file_info' => ['file_format' => 'csv'],
            'entity_attributes' => ['export_filter' => 'test', 'skip_attr' => 'test'],
            'entity_type' => 'customer',
            'operation_type' => 'export',
            'start_time' => '00:00:00',
            'id' => 1,
        ];
        $operation = $this->_getOperationMock($operationData);
        $this->_model->initialize($operation);

        foreach ($operationData as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $this->assertEquals($subValue, $this->_model->getData($this->_getMappedValue($subKey)));
                }
            } else {
                $this->assertEquals($value, $this->_model->getData($this->_getMappedValue($key)));
            }
        }
    }

    /**
     * Test for method 'getScheduledFileName'
     *
     * @param array $data
     * @param string $expectedFilename
     * @dataProvider entityTypeDataProvider
     */
    public function testGetScheduledFileName($data, $expectedFilename)
    {
        $operation = $this->_getOperationMock($data);
        $this->_model->initialize($operation);

        // we should set run date because initialize() resets $operation data
        if (!empty($data['run_date'])) {
            $this->_model->setRunDate($data['run_date']);
        }

        $this->assertEquals($expectedFilename, $this->_model->getScheduledFileName(), 'File name is wrong');
    }

    /**
     * Data provider for test 'testGetScheduledFileName'
     *
     * @return array
     */
    public function entityTypeDataProvider()
    {
        return [
            'Test file name when entity type provided' => [
                '$data' => ['entity_type' => 'customer', 'operation_type' => 'export'],
                '$expectedFilename' => $this->_date . '_export_customer',
            ],
            'Test file name when entity subtype provided' => [
                '$data' => ['entity_type' => 'customer_address', 'operation_type' => 'export'],
                '$expectedFilename' => $this->_date . '_export_customer_address',
            ],
            'Test file name when run date provided' => [
                '$data' => ['entity_type' => 'customer', 'operation_type' => 'export', 'run_date' => '11-11-11'],
                '$expectedFilename' => '11-11-11_export_customer',
            ]
        ];
    }

    /**
     * Retrieve data keys which used inside test model
     *
     * @param string $key
     * @return mixed
     */
    protected function _getMappedValue($key)
    {
        $modelDataMap = ['entity_type' => 'entity', 'start_time' => 'run_at', 'id' => 'scheduled_operation_id'];

        if (array_key_exists($key, $modelDataMap)) {
            return $modelDataMap[$key];
        }

        return $key;
    }

    /**
     * Retrieve operation mock
     *
     * @param array $operationData
     * @return \Magento\ScheduledImportExport\Model\Scheduled\Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getOperationMock(array $operationData)
    {
        /** @var $operation \Magento\ScheduledImportExport\Model\Scheduled\Operation */
        $operation = $this->createPartialMock(
            \Magento\ScheduledImportExport\Model\Scheduled\Operation::class,
            ['__wakeup']
        );
        $operation->setData($operationData);

        return $operation;
    }

    /**
     * Callback to use instead \Magento\Framework\Stdlib\DateTime\DateTime::date()
     *
     * @param string $format
     * @param int|string $input
     * @return string
     */
    public function getDateCallback($format, $input = null)
    {
        if (!empty($format) && $input !== null) {
            return $input;
        }

        return $this->_date;
    }
}
