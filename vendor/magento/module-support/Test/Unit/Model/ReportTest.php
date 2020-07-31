<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Exception\LocalizedException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Model\Report
     */
    protected $report;

    /**
     * @var \Magento\Framework\Model\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\Report\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reportConfigMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\Support\Model\Report\DataConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataConverterMock;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var \Magento\Support\Model\Report\Group\AbstractSection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sectionMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $timeZoneMock;

    /**
     * @var \Magento\Enterprise\Model\ProductMetadata|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMetadataMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateFactoryMock;

    /**
     * @var Magento\Framework\Stdlib\DateTime\DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeMock;

    protected function setUp()
    {
        $this->reportConfigMock = $this->getMockBuilder(\Magento\Support\Model\Report\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->getMockForAbstractClass();
        $this->dataConverterMock = $this->getMockBuilder(\Magento\Support\Model\Report\DataConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->sectionMock = $this->getMockBuilder(\Magento\Support\Model\Report\Group\AbstractSection::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->timeZoneMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);
        $this->productMetadataMock = $this->getMockBuilder(\Magento\Enterprise\Model\ProductMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateFactoryMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->dateTimeMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->dateTimeMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->objectManagerHelper->getObject(
            \Magento\Framework\Model\Context::class,
            [
                'logger' => $this->loggerMock
            ]
        );
        $this->report = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\Report::class,
            [
                'context' => $this->context,
                'reportConfig' => $this->reportConfigMock,
                'objectManager' => $this->objectManagerMock,
                'dataConverter' => $this->dataConverterMock,
                'timeZone' => $this->timeZoneMock,
                'dateFactory' => $this->dateFactoryMock,
                'productMetadata' => $this->productMetadataMock
            ]
        );
    }

    public function testGenerate()
    {
        $groups = ['some', 'groups', 'go', 'here'];
        $sections = ['some', 'sections', 'go', 'here'];
        $reportData = ['some' => [], 'sections' => [], 'go' => [], 'here' => []];

        $this->reportConfigMock->expects($this->once())
            ->method('getSectionNamesByGroup')
            ->with($groups)
            ->willReturn($sections);
        $this->objectManagerMock->expects($this->any())
            ->method('create')
            ->willReturn($this->sectionMock);
        $this->sectionMock->expects($this->any())
            ->method('generate')
            ->willReturn([]);

        $this->report->generate($groups);

        $this->assertEquals($groups, $this->report->getReportGroups());
        $this->assertEquals($reportData, $this->report->getReportData());
    }

    public function testPrepareReportDataNoData()
    {
        $this->assertFalse($this->report->prepareReportData());
    }

    public function testPrepareReportData()
    {
        $errorMessage = 'Something gone wrong';
        $exception = new LocalizedException(__($errorMessage));

        $reportData = [
            'section1' => [
                'title1' => [
                    'headers' => ['header1'],
                    'data' => ['data1']
                ]
            ],
            'section2' => [],
            'section3' => [
                'title3' => [
                    'data' => ['exception']
                ]
            ]
        ];

        $preparedData = [
            'section1' => [
                'title1' => [
                    'headers' => ['header1'],
                    'data' => ['data1']
                ]
            ],
            'section3' => [
                'title3' => [
                    'error' => $errorMessage
                ]
            ]
        ];

        $this->dataConverterMock->expects($this->at(0))
            ->method('prepareData')
            ->with(['headers' => ['header1'], 'data' => ['data1']])
            ->willReturn(['headers' => ['header1'], 'data' => ['data1']]);
        $this->dataConverterMock->expects($this->at(1))
            ->method('prepareData')
            ->with(['data' => ['exception']])
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception, [])
            ->willReturn(true);

        $this->report->setReportData($reportData);
        $this->assertEquals($preparedData, $this->report->prepareReportData());
    }

    public function testGetFileNameForReportDownloadNoId()
    {
        $this->assertEquals('', $this->report->getFileNameForReportDownload());
    }

    public function testGetFileNameForReportDownload()
    {
        $date = '2015-12-03-23-45-11';
        $this->report->setId(3);
        $this->report->setClientHost('/local/host');
        $this->timeZoneMock->expects($this->once())->method('formatDateTime')->willReturn($date);

        $this->assertEquals(
            'report-2015-12-03-23-45-11_localhost.html',
            $this->report->getFileNameForReportDownload()
        );
    }

    public function testBeforeSave()
    {
        $testMagentoVersion = '0.0.0';
        $this->productMetadataMock->expects($this->once())->method('getVersion')->willReturn($testMagentoVersion);
        $this->report->beforeSave();
        $this->assertEquals($testMagentoVersion, $this->report->getMagentoVersion());
    }
}
