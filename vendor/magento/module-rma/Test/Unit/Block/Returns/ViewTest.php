<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Block\Returns;

/**
 * Class ViewTest
 */
class ViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Block\Returns\View
     */
    private $view;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $currentCustomerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRepositoryMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->currentCustomerMock = $this->createMock(\Magento\Customer\Helper\Session\CurrentCustomer::class);
        $this->customerRepositoryMock = $this->createMock(\Magento\Customer\Api\CustomerRepositoryInterface::class);
        $this->view = $objectManager->getObject(
            \Magento\Rma\Block\Returns\View::class,
            [
                'currentCustomer' => $this->currentCustomerMock,
                'customerRepository' => $this->customerRepositoryMock
            ]
        );
    }

    public function testGetCustomerData()
    {
        $customerId = 1;
        $customerMock = $this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class);
        $this->currentCustomerMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerRepositoryMock->expects($this->once())->method('getById')->willReturn($customerMock);
        $this->assertEquals($customerMock, $this->view->getCustomerData());
    }
}
