<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Model\Rma\Source;

use Magento\Rma\Model\Rma\Source\Status;

class AbstractSourceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Model\Item\Attribute\Source\StatusFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $statusFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $attrOptionCollectionFactoryMock;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attrOptionFactoryMock;

    /**
     * @var Status
     */
    protected $status;

    protected function setUp()
    {
        $this->statusFactoryMock = $this->createPartialMock(
            \Magento\Rma\Model\Item\Attribute\Source\StatusFactory::class,
            ['create']
        );
        $this->attrOptionCollectionFactoryMock = $this->createPartialMock(
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory::class,
            ['create']
        );
        $this->attrOptionFactoryMock = $this->createPartialMock(
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory::class,
            ['create']
        );
        $this->status = (new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this))->getObject(
            \Magento\Rma\Model\Rma\Source\Status::class,
            [
                'attrOptionCollectionFactory' => $this->attrOptionCollectionFactoryMock,
                'attrOptionFactory' => $this->attrOptionFactoryMock,
                'statusFactory' => $this->statusFactoryMock,
            ]
        );
    }

    /**
     * @dataProvider getAllOptionsDataProvider
     * @param bool $withLabels
     * @param array $expected
     */
    public function testGetAllOptions($withLabels, $expected)
    {
        $this->assertEquals($expected, $this->status->getAllOptions($withLabels));
    }

    public function testGetAllOptionsForGrid()
    {
        $expected = [
            Status::STATE_PENDING => 'Pending',
            Status::STATE_AUTHORIZED => 'Authorized',
            Status::STATE_PARTIAL_AUTHORIZED => 'Partially Authorized',
            Status::STATE_RECEIVED => 'Return Received',
            Status::STATE_RECEIVED_ON_ITEM => 'Return Partially Received' ,
            Status::STATE_APPROVED_ON_ITEM => 'Partially Approved',
            Status::STATE_REJECTED_ON_ITEM => 'Partially Rejected',
            Status::STATE_CLOSED => 'Closed',
            Status::STATE_PROCESSED_CLOSED => 'Processed and Closed',
        ];
        $this->assertEquals($expected, $this->status->getAllOptionsForGrid());
    }

    public function getAllOptionsDataProvider()
    {
        return [
            [
                true,
                [
                    ['label' => 'Pending', 'value' => Status::STATE_PENDING],
                    ['label' => 'Authorized', 'value' => Status::STATE_AUTHORIZED],
                    ['label' => 'Partially Authorized', 'value' => Status::STATE_PARTIAL_AUTHORIZED],
                    ['label' => 'Return Received', 'value' => Status::STATE_RECEIVED],
                    ['label' => 'Return Partially Received', 'value' => Status::STATE_RECEIVED_ON_ITEM],
                    ['label' => 'Partially Approved', 'value' => Status::STATE_APPROVED_ON_ITEM],
                    ['label' => 'Partially Rejected', 'value' => Status::STATE_REJECTED_ON_ITEM],
                    ['label' => 'Closed', 'value' => Status::STATE_CLOSED],
                    ['label' => 'Processed and Closed', 'value' => Status::STATE_PROCESSED_CLOSED],
                ],
            ],
            [
                false,
                [
                    Status::STATE_PENDING,
                    Status::STATE_AUTHORIZED,
                    Status::STATE_PARTIAL_AUTHORIZED,
                    Status::STATE_RECEIVED,
                    Status::STATE_RECEIVED_ON_ITEM,
                    Status::STATE_APPROVED_ON_ITEM,
                    Status::STATE_REJECTED_ON_ITEM,
                    Status::STATE_CLOSED,
                    Status::STATE_PROCESSED_CLOSED
                ]
            ]
        ];
    }
}
