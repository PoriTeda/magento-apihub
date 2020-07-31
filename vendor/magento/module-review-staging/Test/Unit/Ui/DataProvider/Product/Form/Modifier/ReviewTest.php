<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ReviewStaging\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

class ReviewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\ReviewStaging\Ui\DataProvider\Product\Form\Modifier\Review
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $reviewModifierMock;

    protected function setUp()
    {
        $this->reviewModifierMock = $this->createPartialMock(
            \Magento\Review\Ui\DataProvider\Product\Form\Modifier\Review::class,
            ['modifyData', 'modifyMeta']
        );
        $this->model = new \Magento\ReviewStaging\Ui\DataProvider\Product\Form\Modifier\Review(
            $this->reviewModifierMock
        );
    }

    public function testModifyData()
    {
        $data = ['key' => 'value'];
        $this->reviewModifierMock->expects($this->once())->method('modifyData')->with($data)->willReturn($data);
        $this->assertEquals($data, $this->model->modifyData($data));
    }

    public function testModifyMeta()
    {
        $meta = [
            'review' => [
                'children' => [
                    'review_listing' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataScope' => 'dataScope',
                                    'externalProvider' => 'externalProvider',
                                    'selectionsProvider' => 'selectionsProvider',
                                    'ns' => 'ns',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $modifiedMeta = [
            'review' => [
                'children' => [
                    'stagingreview_listing' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataScope' => 'stagingreview_listing',
                                    'externalProvider' => 'stagingreview_listing.stagingreview_listing_data_source',
                                    'selectionsProvider' =>
                                        'stagingreview_listing.stagingreview_listing.product_columns.ids',
                                    'ns' => 'stagingreview_listing',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->reviewModifierMock->expects($this->once())->method('modifyMeta')->with($meta)->willReturn($meta);
        $this->assertEquals($modifiedMeta, $this->model->modifyMeta($meta));
    }

    public function testModifyMetaWithDisabledReview()
    {
        $meta = ['key' => 'value'];

        $this->reviewModifierMock->expects($this->once())->method('modifyMeta')->with($meta)->willReturn($meta);
        $this->assertEquals($meta, $this->model->modifyMeta($meta));
    }
}
