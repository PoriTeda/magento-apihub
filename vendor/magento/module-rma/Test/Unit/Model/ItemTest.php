<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Test\Unit\Model;

/**
 * Class ItemTest
 */
class ItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Model\Item
     */
    protected $model;

    /**
     * @var \Magento\Rma\Model\ResourceModel\Item|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Rma\Model\RmaFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaFactoryMock;

    /**
     * @var \Magento\Rma\Model\Rma|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaMock;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializer;

    /**
     * Test setUp
     */
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->resourceMock = $this->createMock(\Magento\Rma\Model\ResourceModel\Item::class);
        $this->rmaFactoryMock = $this->createPartialMock(\Magento\Rma\Model\RmaFactory::class, ['create']);
        $this->rmaMock = $this->createPartialMock(\Magento\Rma\Model\Rma::class, ['getOrderId', '__wakeup', 'load']);

        $this->serializer = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $objectManager->getObject(
            \Magento\Rma\Model\Item::class,
            [
                'resource' => $this->resourceMock,
                'rmaFactory' => $this->rmaFactoryMock,
                'data' => [
                    'order_item_id' => 3,
                    'rma_entity_id' => 4,
                ],
                'serializer' => $this->serializer
            ]
        );
    }

    /**
     * Test getOptions
     * @covers \Magento\Rma\Model\Item::getOptions
     */
    public function testGetOptions()
    {
        $json_options = json_encode(
            [
                "options" => [1, "options"],
                "additional_options" => [2, "additional_options"],
                "attributes_info" => [3, "attributes_info"]
            ]
        );
        $this->model->setProductOptions($json_options);
        $result = [1, "options", 2, "additional_options", 3, "attributes_info"];

        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->assertEquals($result, $this->model->getOptions());
    }

    /**
     * Test getReturnableQty
     */
    public function testGetReturnableQty()
    {
        $this->rmaFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->rmaMock));
        $this->rmaMock->expects($this->once())
            ->method('load')
            ->with($this->equalTo(4))
            ->will($this->returnSelf());
        $this->rmaMock->expects($this->once())
            ->method('getOrderId')
            ->will($this->returnValue(3));
        $this->resourceMock->expects($this->once())
            ->method('getReturnableItems')
            ->with($this->equalTo(3))
            ->will($this->returnValue([3 => 100.50, 4 => 50.00]));
        $this->assertEquals(100.50, $this->model->getReturnableQty());
    }

    /**
     * Test setStatus method.
     *
     * @return void
     */
    public function testSetStatus(): void
    {
        $this->model->setStatus(1);
        $this->assertEquals(1, $this->model->getStatus());
    }
}
