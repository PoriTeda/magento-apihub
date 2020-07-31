<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Observer;

use Magento\Framework\App\Area;

class AddressFormatAdminTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Observer\AddressFormatAdmin
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressFormatMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $designMock;

    protected function setUp()
    {
        $this->addressFormatMock = $this->createMock(\Magento\GiftRegistry\Observer\AddressFormat::class);
        $this->designMock = $this->createMock(\Magento\Framework\View\DesignInterface::class);
        $this->model = new \Magento\GiftRegistry\Observer\AddressFormatAdmin(
            $this->addressFormatMock,
            $this->designMock
        );
    }

    public function testexecute()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->designMock->expects($this->once())->method('getArea')->willReturn(Area::AREA_FRONTEND);
        $this->addressFormatMock->expects($this->once())->method('format')->with($observerMock)->willReturnSelf();
        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
