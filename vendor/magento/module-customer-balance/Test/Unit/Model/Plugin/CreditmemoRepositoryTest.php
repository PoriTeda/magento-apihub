<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerBalance\Test\Unit\Model\Plugin;

use Magento\CustomerBalance\Model\Plugin\CreditmemoRepository;
use Magento\Sales\Api\Data\CreditmemoExtension;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;

class CreditmemoRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreditmemoRepository
     */
    private $plugin;

    /**
     * @var CreditmemoRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var CreditmemoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $creditMemoMock;

    /**
     * @var CreditmemoExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var CreditmemoExtensionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $creditMemoExtensionFactoryMock;

    protected function setUp()
    {
        $this->subjectMock = $this->getMockForAbstractClass(CreditmemoRepositoryInterface::class);
        $this->creditMemoMock = $this->getMockBuilder(CreditmemoInterface::class)
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

        $this->extensionAttributeMock = $this->getMockBuilder(CreditmemoExtension::class)
            ->setMethods([
                'getCustomerBalanceAmount',
                'getBaseCustomerBalanceAmount',
                'setCustomerBalanceAmount',
                'setBaseCustomerBalanceAmount'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditMemoExtensionFactoryMock = $this->getMockBuilder(CreditmemoExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new CreditmemoRepository(
            $this->creditMemoExtensionFactoryMock
        );
    }

    public function testAfterGet()
    {
        $customerBalanceAmount = 10;
        $baseCustomerBalanceAmount = 15;

        $this->creditMemoMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->creditMemoMock->expects(static::once())
            ->method('getCustomerBalanceAmount')
            ->willReturn($customerBalanceAmount);
        $this->creditMemoMock->expects(static::once())
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
        $this->creditMemoMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGet($this->subjectMock, $this->creditMemoMock);
    }
}
