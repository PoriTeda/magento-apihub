<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MultipleWishlist\Test\Unit\Controller\Search;

use Magento\Catalog\Model\Product\Exception as ProductException;
use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddtocartTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Addtocart
     */
    protected $model;

    /**
     * @var \Magento\Framework\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $redirectMock;

    /**
     * @var \Magento\Wishlist\Model\Wishlist|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $wishlistMock;

    /**
     * @var \Magento\Wishlist\Model\WishlistFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $wishlistFactorytMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionMock;

    /**
     * @var \Magento\Framework\View\Layout|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutMock;

    /**
     * @var \Magento\Framework\View\Element\BlockInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $blockMock;

    /**
     * @var \Magento\Framework\Module\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $moduleManagerMock;

    /**
     * @var \Magento\Wishlist\Model\LocaleQuantityProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quantityProcessorMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\Checkout\Model\Cart|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutCartMock;

    /**
     * @var \Magento\Wishlist\Model\ItemFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemFactoryMock;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var \Magento\Framework\Controller\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirectMock;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->wishlistMock = $this->getMockBuilder(\Magento\Wishlist\Model\Wishlist::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wishlistFactorytMock = $this->getMockBuilder(\Magento\Wishlist\Model\WishlistFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->wishlistFactorytMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->wishlistMock));

        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemFactoryMock = $this->getMockBuilder(\Magento\Wishlist\Model\ItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $searchFactoryMock = $this->getMockBuilder(\Magento\MultipleWishlist\Model\SearchFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $strategyEmailFactoryMock = $this->getMockBuilder(
            \Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $strategyNameFactoryMock = $this->getMockBuilder(
            \Magento\MultipleWishlist\Model\Search\Strategy\NameFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutCartMock = $this->getMockBuilder(\Magento\Checkout\Model\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $localeResolverMock = $this->getMockBuilder(\Magento\Framework\Locale\ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->redirectMock = $this->getMockBuilder(\Magento\Framework\App\Response\RedirectInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->layoutMock = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockMock = $this->getMockBuilder(\Magento\Framework\View\Element\BlockInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['toHtml', 'setRefererUrl'])
            ->getMockForAbstractClass();

        $this->moduleManagerMock = $this->getMockBuilder(\Magento\Framework\Module\Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quantityProcessorMock = $this->getMockBuilder(\Magento\Wishlist\Model\LocaleQuantityProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageManagerMock = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addSuccess'])
            ->getMockForAbstractClass();

        $this->resultFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultRedirectMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->resultRedirectMock);

        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())
            ->method('getRedirect')
            ->willReturn($this->redirectMock);
        $this->contextMock->expects($this->any())
            ->method('getObjectManager')
            ->willReturn($this->objectManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->model = new \Magento\MultipleWishlist\Controller\Search\Addtocart(
            $this->contextMock,
            $this->registryMock,
            $this->itemFactoryMock,
            $this->wishlistFactorytMock,
            $searchFactoryMock,
            $strategyEmailFactoryMock,
            $strategyNameFactoryMock,
            $checkoutSessionMock,
            $this->checkoutCartMock,
            $this->customerSessionMock,
            $localeResolverMock,
            $this->moduleManagerMock,
            $this->quantityProcessorMock
        );
    }

    public function testExecuteWithNoSelectedAndRedirectToCart()
    {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('qty', null)
            ->willReturn(false);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('selected', null)
            ->willReturn(false);

        $cartHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cartHelperMock->expects($this->once())
            ->method('getShouldRedirectToCart')
            ->willReturn(true);
        $cartHelperMock->expects($this->once())
            ->method('getCartUrl')
            ->willReturn('cart_url');

        $this->objectManagerMock->expects($this->exactly(2))
            ->method('get')
            ->with(\Magento\Checkout\Helper\Cart::class)
            ->willReturn($cartHelperMock);

        $salesQuoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $salesQuoteMock->expects($this->once())
            ->method('collectTotals')
            ->willReturnSelf();

        $this->checkoutCartMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->checkoutCartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($salesQuoteMock);

        $this->resultRedirectMock->expects($this->once())
            ->method('setUrl')
            ->with('cart_url')
            ->willReturnSelf();

        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Redirect::class,
            $this->model->execute()
        );
    }

    public function testExecuteWithRedirectToReferer()
    {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('qty', null)
            ->willReturn([11 => 2]);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('selected', null)
            ->willReturn([11 => 'on']);

        $itemMock = $this->getMockBuilder(\Magento\Wishlist\Model\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($itemMock);
        $itemMock->expects($this->once())
            ->method('loadWithOptions')
            ->with(11)
            ->willReturnSelf();

        $this->quantityProcessorMock->expects($this->once())
            ->method('process')
            ->with(2)
            ->willReturn('2');

        $itemMock->expects($this->once())
            ->method('setQty')
            ->with('2')
            ->willReturnSelf();
        $itemMock->expects($this->once())
            ->method('addToCart')
            ->with($this->checkoutCartMock, false)
            ->willReturn(true);

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $cartHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Magento\Checkout\Helper\Cart::class)
            ->willReturn($cartHelperMock);

        $cartHelperMock->expects($this->once())
            ->method('getShouldRedirectToCart')
            ->willReturn(false);
        $this->redirectMock->expects($this->exactly(2))
            ->method('getRefererUrl')
            ->willReturn('referer_url');

        $productMock->expects($this->once())
            ->method('getName')
            ->willReturn('product_name');
        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with('1 product(s) have been added to shopping cart: "product_name".')
            ->willReturnSelf();

        $salesQuoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $salesQuoteMock->expects($this->once())
            ->method('collectTotals')
            ->willReturnSelf();

        $this->checkoutCartMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->checkoutCartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($salesQuoteMock);

        $this->resultRedirectMock->expects($this->once())
            ->method('setUrl')
            ->with('referer_url')
            ->willReturnSelf();

        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Redirect::class,
            $this->model->execute()
        );
    }

    public function testExecuteWithNotSalableAndNoRedirect()
    {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('qty', null)
            ->willReturn([22 => 2]);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('selected', null)
            ->willReturn([22 => 'on']);

        $itemMock = $this->getMockBuilder(\Magento\Wishlist\Model\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($itemMock);
        $itemMock->expects($this->once())
            ->method('loadWithOptions')
            ->with(22)
            ->willReturnSelf();

        $this->quantityProcessorMock->expects($this->once())
            ->method('process')
            ->with(2)
            ->willReturn('2');

        $itemMock->expects($this->once())
            ->method('setQty')
            ->with('2')
            ->willReturnSelf();
        $itemMock->expects($this->once())
            ->method('addToCart')
            ->with($this->checkoutCartMock, false)
            ->willThrowException(new ProductException(__('Test Phrase')));

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $cartHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Magento\Checkout\Helper\Cart::class)
            ->willReturn($cartHelperMock);

        $cartHelperMock->expects($this->once())
            ->method('getShouldRedirectToCart')
            ->willReturn(false);
        $this->redirectMock->expects($this->once())
            ->method('getRefererUrl')
            ->willReturn(false);

        $productMock->expects($this->once())
            ->method('getName')
            ->willReturn('product_name');
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with('We can\'t add the following product(s) to shopping cart: "product_name".')
            ->willReturnSelf();

        $salesQuoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $salesQuoteMock->expects($this->once())
            ->method('collectTotals')
            ->willReturnSelf();

        $this->checkoutCartMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->checkoutCartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($salesQuoteMock);

        $this->resultRedirectMock->expects($this->once())
            ->method('setUrl')
            ->with('')
            ->willReturnSelf();

        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Redirect::class,
            $this->model->execute()
        );
    }

    public function testExecuteWithMagentoException()
    {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('qty', null)
            ->willReturn([22 => 2]);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('selected', null)
            ->willReturn([22 => 'on']);

        $itemMock = $this->getMockBuilder(\Magento\Wishlist\Model\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($itemMock);
        $itemMock->expects($this->once())
            ->method('loadWithOptions')
            ->with(22)
            ->willReturnSelf();

        $this->quantityProcessorMock->expects($this->once())
            ->method('process')
            ->with(2)
            ->willReturn('2');

        $itemMock->expects($this->once())
            ->method('setQty')
            ->with('2')
            ->willReturnSelf();
        $itemMock->expects($this->once())
            ->method('addToCart')
            ->with($this->checkoutCartMock, false)
            ->willThrowException(new \Magento\Framework\Exception\LocalizedException(
                __('Unknown Magento error')
            ));

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $cartHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Magento\Checkout\Helper\Cart::class)
            ->willReturn($cartHelperMock);

        $cartHelperMock->expects($this->once())
            ->method('getShouldRedirectToCart')
            ->willReturn(false);
        $this->redirectMock->expects($this->exactly(2))
            ->method('getRefererUrl')
            ->willReturn('referer_url');

        $productMock->expects($this->once())
            ->method('getName')
            ->willReturn('product_name');
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with('Unknown Magento error for "product_name"')
            ->willReturnSelf();

        $salesQuoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $salesQuoteMock->expects($this->once())
            ->method('collectTotals')
            ->willReturnSelf();

        $this->checkoutCartMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->checkoutCartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($salesQuoteMock);

        $this->resultRedirectMock->expects($this->once())
            ->method('setUrl')
            ->with('referer_url')
            ->willReturnSelf();

        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Redirect::class,
            $this->model->execute()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testExecuteWithException()
    {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('qty', null)
            ->willReturn([22 => 2]);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('selected', null)
            ->willReturn([22 => 'on']);

        $itemMock = $this->getMockBuilder(\Magento\Wishlist\Model\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($itemMock);
        $itemMock->expects($this->once())
            ->method('loadWithOptions')
            ->with(22)
            ->willReturnSelf();

        $this->quantityProcessorMock->expects($this->once())
            ->method('process')
            ->with(2)
            ->willReturn('2');

        $exception = new \Exception();

        $itemMock->expects($this->once())
            ->method('setQty')
            ->with('2')
            ->willReturnSelf();
        $itemMock->expects($this->once())
            ->method('addToCart')
            ->with($this->checkoutCartMock, false)
            ->willThrowException($exception);

        $loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->getMock();

        $this->objectManagerMock->expects($this->at(0))
            ->method('get')
            ->with(\Psr\Log\LoggerInterface::class)
            ->willReturn($loggerMock);

        $loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $cartHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock->expects($this->at(1))
            ->method('get')
            ->with(\Magento\Checkout\Helper\Cart::class)
            ->willReturn($cartHelperMock);

        $cartHelperMock->expects($this->once())
            ->method('getShouldRedirectToCart')
            ->willReturn(false);
        $this->redirectMock->expects($this->exactly(2))
            ->method('getRefererUrl')
            ->willReturn('referer_url');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with('We can\'t add the item to shopping cart.')
            ->willReturnSelf();

        $salesQuoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $salesQuoteMock->expects($this->once())
            ->method('collectTotals')
            ->willReturnSelf();

        $this->checkoutCartMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->checkoutCartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($salesQuoteMock);

        $this->resultRedirectMock->expects($this->once())
            ->method('setUrl')
            ->with('referer_url')
            ->willReturnSelf();

        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Redirect::class,
            $this->model->execute()
        );
    }
}
