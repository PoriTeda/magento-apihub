<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\Export;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HtmlTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Block\Adminhtml\Report\Export\Html
     */
    protected $exportHtmlBlock;

    /**
     * @var \Magento\Backend\Block\Widget\Context
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
     * @var \Magento\Support\Model\Report\DataConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataConverterMock;

    /**
     * @var \Magento\Support\Model\Report\HtmlGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $htmlGeneratorMock;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeResolverMock;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var \Magento\Framework\Escaper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $escaperMock;

    /**
     * @var \Magento\Support\Model\Report|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reportMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeDateMock;

    protected function setUp()
    {
        $this->reportConfigMock = $this->getMockBuilder(\Magento\Support\Model\Report\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataConverterMock = $this->getMockBuilder(\Magento\Support\Model\Report\DataConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->htmlGeneratorMock = $this->getMockBuilder(\Magento\Support\Model\Report\HtmlGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeResolverMock = $this->getMockBuilder(\Magento\Framework\Locale\ResolverInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->escaperMock = $this->getMockBuilder(\Magento\Framework\Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reportMock = $this->getMockBuilder(\Magento\Support\Model\Report::class)
            ->setMethods(['getId', 'getClientHost', 'getCreatedAt', 'prepareReportData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeDateMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->objectManagerHelper->getObject(
            \Magento\Backend\Block\Widget\Context::class,
            [
                'logger' => $this->loggerMock,
                'escaper' => $this->escaperMock,
                'localeDate' => $this->localeDateMock
            ]
        );
        $this->exportHtmlBlock = $this->objectManagerHelper->getObject(
            \Magento\Support\Block\Adminhtml\Report\Export\Html::class,
            [
                'context' => $this->context,
                'reportConfig' => $this->reportConfigMock,
                'dataConverter' => $this->dataConverterMock,
                'htmlGenerator' => $this->htmlGeneratorMock,
                'localeResolver' => $this->localeResolverMock
            ]
        );

        $this->exportHtmlBlock->setData('report', $this->reportMock);
    }

    public function testGetDataConverter()
    {
        $this->assertSame($this->dataConverterMock, $this->exportHtmlBlock->getDataConverter());
    }

    public function testGetHtmlGenerator()
    {
        $this->assertSame($this->htmlGeneratorMock, $this->exportHtmlBlock->getHtmlGenerator());
    }

    public function testGetReport()
    {
        $this->assertSame($this->reportMock, $this->exportHtmlBlock->getReport());
    }

    public function testGetReportsNoId()
    {
        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->assertEquals([], $this->exportHtmlBlock->getReports());
    }

    public function testGetReportsEmptyData()
    {
        $reportGroups = [
            'general' => [
                'title' => __('General'),
                'sections' => [
                    40 => \Magento\Support\Model\Report\Group\General\VersionSection::class,
                    50 => \Magento\Support\Model\Report\Group\General\DataCountSection::class,
                    70 => \Magento\Support\Model\Report\Group\General\CacheStatusSection::class,
                    80 => \Magento\Support\Model\Report\Group\General\IndexStatusSection::class
                ],
                'priority' => 10
            ]
        ];

        $this->reportMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->reportMock->expects($this->once())
            ->method('prepareReportData')
            ->willReturn([]);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(__('Requested system report has no data to output.'))
            ->willReturn(true);
        $this->reportConfigMock->expects($this->any())
            ->method('getGroups')
            ->willReturn($reportGroups);

        $this->assertEquals([], $this->exportHtmlBlock->getReports());
    }

    /**
     * @param array $reportData
     * @param array $reportGroups
     * @param array $expectedResult
     *
     * @dataProvider getReportsDataProvider
     */
    public function testGetReports(
        array $reportData,
        array $reportGroups,
        array $expectedResult
    ) {
        $this->reportMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->reportMock->expects($this->any())
            ->method('prepareReportData')
            ->willReturn($reportData);
        $this->loggerMock->expects($this->never())
            ->method('error');
        $this->reportConfigMock->expects($this->any())
            ->method('getGroups')
            ->willReturn($reportGroups);

        $this->assertEquals($expectedResult, $this->exportHtmlBlock->getReports());
    }

    /**
     * @return array
     */
    public function getReportsDataProvider()
    {
        return [
            [
                'reportData' => [\Magento\Support\Model\Report\Group\General\VersionSection::class => [
                        'Magento Version' => []
                    ], \Magento\Support\Model\Report\Group\General\DataCountSection::class => [
                        'Data Count' => []
                    ], \Magento\Support\Model\Report\Group\General\CacheStatusSection::class => [
                        'Cache Status' => []
                    ], \Magento\Support\Model\Report\Group\General\IndexStatusSection::class => [
                        'Index Status' => []
                    ], \Magento\Support\Model\Report\Group\Environment\EnvironmentSection::class => [
                        'Environment Information' => []
                    ], \Magento\Support\Model\Report\Group\Environment\MysqlStatusSection::class => [
                        'MySQL Status' => []
                    ]
                ],
                'reportGroups' => [
                    'general' => [
                        'title' => __('General'),
                        'sections' => [
                            40 => \Magento\Support\Model\Report\Group\General\VersionSection::class,
                            50 => \Magento\Support\Model\Report\Group\General\DataCountSection::class,
                            70 => \Magento\Support\Model\Report\Group\General\CacheStatusSection::class,
                            80 => \Magento\Support\Model\Report\Group\General\IndexStatusSection::class
                        ],
                        'priority' => 10
                    ],
                    'environment' => [
                        'title' => __('Environment'),
                        'sections' => [
                            410 => \Magento\Support\Model\Report\Group\Environment\EnvironmentSection::class,
                            420 => \Magento\Support\Model\Report\Group\Environment\MysqlStatusSection::class
                        ],
                        'priority' => 30
                    ]
                ],
                'expectedResult' => [
                    'general' => [
                        'title' => __('General'),
                        'reports' => [
                            'Magento Version' => [],
                            'Data Count' => [],
                            'Cache Status' => [],
                            'Index Status' => []
                        ]
                    ],
                    'environment' => [
                        'title' => __('Environment'),
                        'reports' => [
                            'Environment Information' => [],
                            'MySQL Status' => []
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testGetReportTitleNoId()
    {
        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->reportMock->expects($this->never())
            ->method('getClientHost');

        $this->assertEquals('', $this->exportHtmlBlock->getReportTitle());
    }

    public function testGetReportTitle()
    {
        $clientHost = 'client.host';

        $this->reportMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->reportMock->expects($this->any())
            ->method('getClientHost')
            ->willReturn($clientHost);

        $this->assertEquals($clientHost, $this->exportHtmlBlock->getReportTitle());
    }

    public function testGetReportCreationDateNoId()
    {
        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->reportMock->expects($this->never())
            ->method('getCreatedAt');

        $this->assertEquals('', $this->exportHtmlBlock->getReportCreationDate());
    }

    public function testGetReportCreationDate()
    {
        $date = '2020-03-04 12:00:00';
        $this->reportMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->localeDateMock->expects($this->any())
            ->method('formatDateTime')
            ->willreturn($date);

        $this->assertEquals($date, $this->exportHtmlBlock->getReportCreationDate());
    }

    public function testGetCopyrightTextNoId()
    {
        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->assertEquals('', $this->exportHtmlBlock->getCopyrightText());
    }

    public function testGetCopyrightText()
    {
        $expectedResult = __('&copy; Magento Commerce Inc., %1', date('Y'));

        $this->reportMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->assertEquals($expectedResult, $this->exportHtmlBlock->getCopyrightText());
    }

    public function testGetLangDataIsSet()
    {
        $lang = 'en';

        $this->exportHtmlBlock->setData('lang', $lang);

        $this->localeResolverMock->expects($this->never())
            ->method('getLocale');

        $this->assertEquals($lang, $this->exportHtmlBlock->getLang());
    }

    public function testGetLang()
    {
        $lang = 'en';
        $locale = 'english';

        $this->localeResolverMock->expects($this->once())
            ->method('getLocale')
            ->willReturn($locale);

        $this->assertEquals($lang, $this->exportHtmlBlock->getLang());
    }
}
