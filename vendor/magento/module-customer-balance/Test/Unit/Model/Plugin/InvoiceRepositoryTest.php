<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerBalance\Test\Unit\Model\Plugin;

use Magento\CustomerBalance\Model\Plugin\InvoiceRepository;
use Magento\Sales\Api\Data\InvoiceExtension;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class InvoiceRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InvoiceRepository
     */
    private $plugin;

    /**
     * @var InvoiceRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var InvoiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $invoiceMock;

    /**
     * @var InvoiceExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var InvoiceExtensionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $invoiceExtensionFactoryMock;

    protected function setUp()
    {
        $this->subjectMock = $this->getMockForAbstractClass(InvoiceRepositoryInterface::class);
        $this->invoiceMock = $this->getMockBuilder(InvoiceInterface::class)
            ->setMethods([
                'getExtensionAttributes',
                'setExtensionAttributes',
                'setCustomerBalanceAmount',
                'setBaseCustomerBalanceAmount',
                'getBaseCustomerBalanceAmount',
                'getCustomerBalanceAmount'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extensionAttributeMock = $this->getMockBuilder(InvoiceExtension::class)
            ->setMethods([
                'getCustomerBalanceAmount',
                'getBaseCustomerBalanceAmount',
                'setCustomerBalanceAmount',
                'setBaseCustomerBalanceAmount'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceExtensionFactoryMock = $this->getMockBuilder(InvoiceExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new InvoiceRepository(
            $this->invoiceExtensionFactoryMock
        );
    }

    public function testBeforeSave()
    {
        $this->invoiceMock
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->extensionAttributeMock->expects($this->once())->method('getCustomerBalanceAmount')->willReturn(10);
        $this->extensionAttributeMock->expects($this->once())->method('getBaseCustomerBalanceAmount')->willReturn(15);
        $this->invoiceMock->expects($this->once())->method('setCustomerBalanceAmount')->with(10)->willReturnSelf();
        $this->invoiceMock->expects($this->once())->method('setBaseCustomerBalanceAmount')->with(15)->willReturnSelf();
        $this->plugin->beforeSave($this->subjectMock, $this->invoiceMock);
    }

    public function testAfterGet()
    {
        $customerBalanceAmount = 10;
        $baseCustomerBalanceAmount = 15;

        $this->invoiceMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->invoiceMock->expects(static::once())
            ->method('getCustomerBalanceAmount')
            ->willReturn($customerBalanceAmount);
        $this->invoiceMock->expects(static::once())
            ->method('getBaseCustomerBalanceAmount')
            ->willReturn($baseCustomerBalanceAmount);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setCustomerBalanceAmount')
            ->with($customerBalanceAmount)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects(static::once())
            ->method('setBaseCustomerBalanceAmount')
            ->with($baseCustomerBalanceAmount)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGet($this->subjectMock, $this->invoiceMock);
    }
}
