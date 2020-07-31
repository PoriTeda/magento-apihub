<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Ui\Component\Listing\Column\Entity;

use Magento\Staging\Ui\Component\Listing\Column\Entity\Actions;

class ActionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $componentFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $urlProviderMock;

    /**
     * @var Actions
     */
    private $actions;

    protected function setUp()
    {
        $processorMock = $this->createMock(\Magento\Framework\View\Element\UiComponent\Processor::class);
        $this->contextMock = $this->createMock(\Magento\Framework\View\Element\UiComponent\ContextInterface::class);
        $this->contextMock->expects($this->never())->method('getProcessor')->willReturn($processorMock);
        $this->urlBuilderMock = $this->createMock(\Magento\Staging\Model\Preview\UrlBuilder::class);
        $this->componentFactoryMock = $this->createMock(\Magento\Framework\View\Element\UiComponentFactory::class);
        $this->urlProviderMock = $this->createMock(
            \Magento\Staging\Ui\Component\Listing\Column\Entity\UrlProviderInterface::class
        );

        $this->actions = new Actions(
            $this->contextMock,
            $this->componentFactoryMock,
            $this->urlBuilderMock,
            'entity_id',
            'entity_id',
            'modalProvider',
            'loaderProvider',
            $this->urlProviderMock,
            [],
            [
                'name' => 'save_action',
            ]
        );
    }

    public function testPrepareDataSource()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'id' => 1000,
                        'entity_id' => 1,
                    ],
                ],
            ],
        ];

        $expectedResult = [
            'data' => [
                'items' => [
                    [
                        'id' => 1000,
                        'entity_id' => 1,
                        'save_action' => [
                            'edit' => [
                                'callback' => [
                                    [
                                        'provider' => 'loaderProvider',
                                        'target' => 'destroyInserted',
                                    ],
                                    [
                                        'provider' => 'loaderProvider',
                                        'target' => 'updateData',
                                        'params' => [
                                            'entity_id' => 1,
                                            'update_id' => 1000,
                                        ],
                                    ],
                                    [
                                        'provider' => 'modalProvider',
                                        'target' => 'openModal',
                                    ],
                                ],
                                'label' => __('View/Edit'),
                            ],
                            'preview' => [
                                'href' => null,
                                'label' => __('Preview'),
                                'target' => '_blank'
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedResult, $this->actions->prepareDataSource($dataSource));
    }
}
