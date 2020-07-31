<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Model\Shipping;

/**
 * Class InfoTest
 */
class InfoTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Model\Shipping\Info
     */
    private $model;

    /**
     * @var \Magento\Rma\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rmaDataMock;

    /**
     * @var \Magento\Rma\Model\RmaFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rmaFactoryMock;

    /**
     * @var \Magento\Rma\Model\ShippingFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingFactoryMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManagerHelper;

    protected function setUp()
    {
        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->rmaDataMock = $this->createMock(\Magento\Rma\Helper\Data::class);
        $this->rmaFactoryMock = $this->createPartialMock('Magento\Rma\Model\RmaFactory', ['create']);
        $this->shippingFactoryMock = $this->createPartialMock('Magento\Rma\Model\ShippingFactory', ['create']);
        $this->model = $this->objectManagerHelper->getObject(
            \Magento\Rma\Model\Shipping\Info::class,
            [
                'rmaData' => $this->rmaDataMock,
                'rmaFactory' => $this->rmaFactoryMock,
                'shippingFactory' => $this->shippingFactoryMock
            ]
        );
    }
    
    public function testGetTrackingInfoByRmaWithVulnerableHash()
    {
        $rmaId = '123';
        $protectedCode = '0e015339760548602306096794382326';
        $maliciousProtectedCode = '0';
        $this->model->setRmaId($rmaId);
        $this->model->setProtectCode($maliciousProtectedCode);
        $rmaMock = $this->getMockBuilder(\Magento\Rma\Model\Rma::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getEntityId', 'getProtectCode'])
            ->getMock();
        $this->rmaFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($rmaMock));
        $rmaMock->expects($this->once())
            ->method('load')
            ->with($rmaId)
            ->willReturnSelf();
        $rmaMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($rmaId);
        $rmaMock->expects($this->once())
            ->method('getProtectCode')
            ->willReturn($protectedCode);
        $this->assertEmpty($this->model->getTrackingInfoByRma());
        $this->assertEmpty($this->model->getTrackingInfo());
    }

    public function testGetTrackingInfoByTrackIdWithVulnerableHash()
    {
        $trackId = '123';
        $protectedCode = '0e015339760548602306096794382326';
        $maliciousProtectedCode = '0';
        $this->model->setTrackId($trackId);
        $this->model->setProtectCode($maliciousProtectedCode);
        $rmaShippingMock = $this->getMockBuilder(\Magento\Rma\Model\Shipping::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getId', 'getProtectCode', 'getNumberDetail'])
            ->getMock();
        $this->shippingFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($rmaShippingMock));
        $rmaShippingMock->expects($this->once())
            ->method('load')
            ->with($trackId)
            ->willReturnSelf();
        $rmaShippingMock->expects($this->once())
            ->method('getId')
            ->willReturn($trackId);
        $rmaShippingMock->expects($this->once())
            ->method('getProtectCode')
            ->willReturn($protectedCode);
        $this->assertEmpty($this->model->getTrackingInfoByTrackId());
        $this->assertEmpty($this->model->getTrackingInfo());
    }
}
