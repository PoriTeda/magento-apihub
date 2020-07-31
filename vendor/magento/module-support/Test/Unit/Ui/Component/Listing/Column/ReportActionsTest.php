<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Ui\Component\Listing\Column;

use Magento\Support\Ui\Component\Listing\Column\ReportActions;

class ReportActionsTest extends \PHPUnit\Framework\TestCase
{
    public function testPrepareItemsByReportId()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        /** @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
        $urlBuilderMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\ContextInterface::class)
            ->getMockForAbstractClass();
        $processor = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->never())->method('getProcessor')->willReturn($processor);

        /** @var \Magento\Support\Ui\Component\Listing\Column\ReportActions $model */
        $model = $objectManager->getObject(
            \Magento\Support\Ui\Component\Listing\Column\ReportActions::class,
            [
                'urlBuilder' => $urlBuilderMock,
                'context' => $contextMock,
            ]
        );

        // Define test input and expectations
        $reportId = 1;
        $items = [
            'data' => [
                'items' => [
                    [
                        'report_id' => $reportId
                    ]
                ]
            ]
        ];
        $name = 'item_name';
        $expectedItems = [
            [
                'report_id' => $reportId,
                $name => [
                    'view' => [
                        'href' => 'support/report/view',
                        'label' => __('View'),
                    ],
                    'delete' => [
                        'href' => 'support/report/delete',
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete "%1"', ['${ $.$data.report_id }']),
                            'message' => __(
                                'Are you sure you wan\'t to delete a "%1" record?',
                                ['${ $.$data.report_id }']
                            )
                        ],
                    ],
                    'download' => [
                        'href' => 'support/report/download',
                        'label' => __('Download'),
                    ]
                ],
                'report_data' => null
            ]
        ];
        // Configure mocks and object data
        $urlBuilderMock->expects($this->any())
            ->method('getUrl')
            ->willReturnMap(
                [
                    [
                        ReportActions::REPORT_URL_PATH_VIEW,
                        [
                            'id' => $reportId
                        ],
                        'support/report/view',
                    ],
                    [
                        ReportActions::REPORT_URL_PATH_DELETE,
                        [
                            'id' => $reportId
                        ],
                        'support/report/delete',
                    ],
                    [
                        ReportActions::REPORT_URL_PATH_DOWNLOAD,
                        [
                            'id' => $reportId
                        ],
                        'support/report/download',
                    ]
                ]
            );

        $model->setName($name);
        $items = $model->prepareDataSource($items);

        $this->assertEquals($expectedItems, $items['data']['items']);
    }
}
