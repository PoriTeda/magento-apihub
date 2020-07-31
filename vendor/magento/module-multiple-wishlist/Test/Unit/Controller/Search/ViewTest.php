<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MultipleWishlist\Test\Unit\Controller\Search;

use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var View
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
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var \Magento\Framework\View\Result\Page|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultPageMock;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->wishlistMock = $this->getMockBuilder(\Magento\Wishlist\Model\Wishlist::class)
            ->setMethods(['getVisibility', 'load', 'getId', 'getCustomerId'])
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
        $itemFactoryMock = $this->getMockBuilder(\Magento\Wishlist\Model\ItemFactory::class)
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
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutCartMock = $this->getMockBuilder(\Magento\Checkout\Model\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $localeResolverMock = $this->getMockBuilder(\Magento\Framework\Locale\ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $responseMock = $this->getMockBuilder(\Magento\Framework\App\Response\HttpInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectMock = $this->getMockBuilder(\Magento\Framework\App\Response\RedirectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->layoutMock = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockMock = $this->getMockBuilder(\Magento\Framework\View\Element\BlockInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['toHtml', 'setRefererUrl'])
            ->getMock();

        $this->resultFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock = $this->getMockBuilder(\Magento\Framework\View\Result\Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE, [])
            ->willReturn($this->resultPageMock);

        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($responseMock);
        $this->contextMock->expects($this->any())
            ->method('getRedirect')
            ->willReturn($this->redirectMock);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->moduleManagerMock = $this->getMockBuilder(\Magento\Framework\Module\Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new \Magento\MultipleWishlist\Controller\Search\View(
            $this->contextMock,
            $this->registryMock,
            $itemFactoryMock,
            $this->wishlistFactorytMock,
            $searchFactoryMock,
            $strategyEmailFactoryMock,
            $strategyNameFactoryMock,
            $checkoutSessionMock,
            $checkoutCartMock,
            $this->customerSessionMock,
            $localeResolverMock,
            $this->moduleManagerMock
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\NotFoundException
     */
    public function testExecuteNotFoundFirst()
    {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with($this->equalTo('wishlist_id'))
            ->will($this->returnValue(false));

        $this->model->execute();
    }

    /**
     * @param $wishlistId
     * @param $visibility
     * @param $customerId
     *
     * @dataProvider getNotFoundParametersDataProvider
     * @expectedException \Magento\Framework\Exception\NotFoundException
     */
    public function testExecuteNotFoundSecond($wishlistId, $visibility, $customerId)
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with($this->equalTo('wishlist_id'))
            ->will($this->returnValue(true));

        $this->wishlistMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->wishlistMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($wishlistId));
        $this->wishlistMock->expects($this->any())
            ->method('getVisibility')
            ->will($this->returnValue($visibility));
        $this->wishlistMock->expects($this->any())
            ->method('getCustomerId')
            ->will($this->returnValue($customerId));

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomerId')
            ->will($this->returnValue(1));

        $this->model->execute();
    }

    /**
     * @return array
     */
    public function getNotFoundParametersDataProvider()
    {
        return [
            [0, 0, 0],
            [1, 0, 0],
            [0, 1, 0],
        ];
    }

    public function testExecute()
    {
        $wishlistId = 1;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with($this->equalTo('wishlist_id'))
            ->will($this->returnValue(true));

        $this->wishlistMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $this->wishlistMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($wishlistId));
        $this->wishlistMock->expects($this->any())
            ->method('getVisibility')
            ->will($this->returnValue(1));

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with($this->equalTo('shared_wishlist'), $this->equalTo($this->wishlistMock))
            ->will($this->returnValue(1));

        $this->resultPageMock->expects($this->any())
            ->method('getLayout')
            ->willReturn($this->layoutMock);

        $this->blockMock->expects($this->once())
            ->method('setRefererUrl')
            ->willReturnMap([
                ['', $this->layoutMock],
            ]);

        $this->layoutMock->expects($this->once())
            ->method('getBlock')
            ->willReturnMap([
                ['customer.wishlist.info', $this->blockMock],
            ]);

        $this->redirectMock->expects($this->once())
            ->method('getRefererUrl')
            ->willReturn('');

        $this->assertInstanceOf(
            \Magento\Framework\View\Result\Page::class,
            $this->model->execute()
        );
    }
}
