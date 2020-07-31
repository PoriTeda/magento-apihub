<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PersistentHistory\Test\Unit\Model;

use Magento\PersistentHistory\Model\CustomerEmulator;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerEmulatorObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $compareProductHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $persistentSessionMock;

    /***
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $wishlistHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $persistentHistoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $registryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customerFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $addressRepositoryMock;

    /**
     * @var CustomerEmulator
     */
    private $model;

    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->compareProductHelperMock  = $this->createMock(\Magento\Catalog\Helper\Product\Compare::class);

        $this->persistentSessionMock = $this->createMock(\Magento\Persistent\Helper\Session::class);
        $this->wishlistHelperMock = $this->createMock(\Magento\Wishlist\Helper\Data::class);
        $this->persistentHistoryMock = $this->createMock(\Magento\PersistentHistory\Helper\Data::class);
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->customerFactoryMock = $this->createPartialMock(
            \Magento\Customer\Model\CustomerFactory::class,
            ['create']
        );
        $methods = ['setCustomerId', 'setCustomerGroupId', 'setIsCustomerEmulated'];
        $this->customerSessionMock = $this->createPartialMock(\Magento\Customer\Model\Session::class, $methods);
        $this->customerRepositoryMock = $this->createMock(\Magento\Customer\Api\CustomerRepositoryInterface::class);

        $this->addressRepositoryMock = $this->createMock(\Magento\Customer\Api\AddressRepositoryInterface::class);
        $this->model = new CustomerEmulator(
            $this->persistentSessionMock,
            $this->wishlistHelperMock,
            $this->persistentHistoryMock,
            $this->registryMock,
            $this->customerFactoryMock,
            $this->customerSessionMock,
            $this->customerRepositoryMock,
            $this->addressRepositoryMock
        );
        $this->objectManagerHelper->setBackwardCompatibleProperty(
            $this->model,
            'compareProductHelper',
            $this->compareProductHelperMock
        );
    }

    public function testEmulate()
    {
        $customerId = 1;
        $persistentSession = $this->createPartialMock(\Magento\Persistent\Model\Session::class, ['getCustomerId']);
        $customerMock = $this->createPartialMock(
            \Magento\Customer\Model\Customer::class,
            ['getDefaultShipping', 'getDefaultBilling', 'load', 'getGroupId']
        );
        $customerMock->expects($this->once())->method('getGroupId')->willReturn(2);
        $this->persistentSessionMock->expects($this->once())->method('getSession')->willReturn($persistentSession);
        $persistentSession->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerFactoryMock->expects($this->once())->method('create')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('load')->willReturnSelf();
        $this->customerSessionMock
            ->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->once())->method('setCustomerGroupId')->with(2)->willReturnSelf();
        $this->customerSessionMock
            ->expects($this->once())
            ->method('setIsCustomerEmulated')
            ->with(true)
            ->willReturnSelf();
        $this->persistentHistoryMock->expects($this->once())->method('isCompareProductsPersist')->willReturn(true);
        $this->compareProductHelperMock->expects($this->once())->method('setCustomerId')->with($customerId);
        $this->model->emulate();
    }
}
