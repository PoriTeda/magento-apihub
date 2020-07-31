<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Observer;

class AddressFormatFrontTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Observer\AddressFormatFront
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressFormatMock;

    protected function setUp()
    {
        $this->addressFormatMock = $this->createMock(\Magento\GiftRegistry\Observer\AddressFormat::class);
        $this->model = new \Magento\GiftRegistry\Observer\AddressFormatFront($this->addressFormatMock);
    }

    public function testexecute()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->addressFormatMock->expects($this->once())->method('format')->with($observerMock)->willReturnSelf();
        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
