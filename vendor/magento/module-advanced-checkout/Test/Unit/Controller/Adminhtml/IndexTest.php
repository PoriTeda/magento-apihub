<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Test\Unit\Controller\Adminhtml;

/**
 * Tests for AdvancedCheckout Index
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedCheckout\Test\Unit\Controller\Adminhtml\Stub\Child
     */
    protected $controller;

    /**
     * @var \Magento\Framework\ObjectManagerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\App\Request\Http | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerFactory;

    /**
     * Set Up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->objectManager = $this->createMock(\Magento\Framework\ObjectManager\ObjectManager::class);
        $this->customerFactory = $this->createPartialMock(
            \Magento\Customer\Api\Data\CustomerInterfaceFactory::class,
            ['create']
        );

        $this->request = $this->createPartialMock(\Magento\Framework\App\Request\Http::class, ['getPost', 'getParam']);
        $response = $this->createMock(\Magento\Framework\App\ResponseInterface::class);

        $context = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $context->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);
        $context->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->controller = $objectManager->getObject(
            \Magento\AdvancedCheckout\Test\Unit\Controller\Adminhtml\Stub\Child::class,
            ['context' => $context, 'customerFactory' => $this->customerFactory]
        );
    }

    /**
     * Test AdvancedCheckoutIndex InitData with Quote id false
     *
     * @return void
     */
    public function testInitData()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturn(true);

        $customerModel = $this->createPartialMock(
            \Magento\Customer\Model\Customer::class,
            ['getWebsiteId', 'load', 'getId', 'getData']
        );
        $customerModel->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $customerId = 1;
        $customerModel->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($customerId);
        $customerModel->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn(true);

        $store = $this->createMock(\Magento\Store\Model\Store::class);

        $storeManager = $this->createPartialMock(
            \Magento\Store\Model\StoreManager::class,
            ['getWebsiteId', 'getStore']
        );
        $storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($store);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->expects($this->once())
            ->method('getId')
            ->willReturn(false);

        $cart = $this->createMock(\Magento\AdvancedCheckout\Model\Cart::class);
        $cart->expects($this->once())
            ->method('setSession')
            ->willReturnSelf();
        $cart->expects($this->once())
            ->method('setContext')
            ->willReturnSelf();
        $cart->expects($this->once())
            ->method('setCurrentStore')
            ->willReturnSelf();
        $cart->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $session = $this->createMock(\Magento\Backend\Model\Session::class);
        $quoteRepository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);

        $this->objectManager->expects($this->at(0))
            ->method('create')
            ->with(\Magento\Customer\Model\Customer::class)
            ->willReturn($customerModel);
        $this->objectManager->expects($this->at(1))
            ->method('get')
            ->with(\Magento\Store\Model\StoreManager::class)
            ->willReturn($storeManager);
        $this->objectManager->expects($this->at(2))
            ->method('get')
            ->with(\Magento\AdvancedCheckout\Model\Cart::class)
            ->willReturn($cart);
        $this->objectManager->expects($this->at(3))
            ->method('get')
            ->with(\Magento\Backend\Model\Session::class)
            ->willReturn($session);
        $this->objectManager->expects($this->at(4))
            ->method('get')
            ->with(\Magento\Quote\Api\CartRepositoryInterface::class)
            ->willReturn($quoteRepository);
        $customerData = $this->expectCustomerModelConvertToCustomerData($customerModel, $customerId);
        $quote->expects($this->once())
            ->method('setCustomer')
            ->with($customerData);
        $quote->expects($this->once())
            ->method('setStore')
            ->willReturnSelf();

        $this->controller->execute();
    }

    /**
     * Expecting for converting Customer Model
     *
     * @param $customerModel
     * @param $customerId
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function expectCustomerModelConvertToCustomerData($customerModel, $customerId)
    {
        $customerData = $this->getMockForAbstractClass(
            \Magento\Customer\Api\Data\CustomerInterface::class,
            [],
            '',
            false
        );
        $customerData->expects($this->once())
            ->method('setId')
            ->with($customerId)
            ->willReturnSelf();

        $this->customerFactory->expects($this->once())
            ->method('create')
            ->willReturn($customerData);

        $customerDataArray = ['entity_id' => 1];
        $customerModel->expects($this->once())
            ->method('getData')
            ->willReturn($customerDataArray);

        return $customerData;
    }
}
