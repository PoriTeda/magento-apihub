<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Ui\Component\Listing\Column;

use Magento\Support\Ui\Component\Listing\Column\BackupActions;

class BackupActionsTest extends \PHPUnit\Framework\TestCase
{
    public function testPrepareItemsByBackupId()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $contextMock = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\ContextInterface::class)
            ->getMockForAbstractClass();
        $processor = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->never())->method('getProcessor')->willReturn($processor);
        /** @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
        $urlBuilderMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \Magento\Support\Ui\Component\Listing\Column\BackupActions $model */
        $model = $objectManager->getObject(
            \Magento\Support\Ui\Component\Listing\Column\BackupActions::class,
            [
                'urlBuilder' => $urlBuilderMock,
                'context' => $contextMock,
            ]
        );

        // Define test input and expectations
        $backupId = 1;
        $items = [
            'data' => [
                'items' => [
                    [
                        'backup_id' => $backupId
                    ]
                ]
            ]
        ];
        $name = 'item_name';
        $expectedItems = [
            [
                'backup_id' => $backupId,
                $name => [
                    'log' => [
                        'href' => 'support/backup/log',
                        'label' => __('Show Log'),
                    ],
                    'delete' => [
                        'href' => 'support/backup/delete',
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete "%1"', ['${ $.$data.backup_id }']),
                            'message' => __(
                                'Are you sure you want to delete a "%1" record?',
                                ['${ $.$data.backup_id }']
                            )
                        ],
                    ]
                ],
            ]
        ];
        // Configure mocks and object data
        $urlBuilderMock->expects($this->any())
            ->method('getUrl')
            ->willReturnMap(
                [
                    [
                        BackupActions::BACKUP_URL_PATH_SHOW_LOG,
                        [
                            'id' => $backupId
                        ],
                        'support/backup/log',
                    ],
                    [
                        BackupActions::BACKUP_URL_PATH_DELETE,
                        [
                            'id' => $backupId
                        ],
                        'support/backup/delete',
                    ],
                ]
            );

        $model->setName($name);
        $items = $model->prepareDataSource($items);

        $this->assertEquals($expectedItems, $items['data']['items']);
    }
}
