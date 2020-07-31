<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MultipleWishlist\Test\Unit\Controller\Search;

use Magento\Framework\Controller\ResultFactory;
use Magento\MultipleWishlist\Model\Search\Strategy\Name;
use Magento\MultipleWishlist\Model\Search\Strategy\Email;
use Magento\Wishlist\Model\ResourceModel\Wishlist\Collection;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResultsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\MultipleWishlist\Controller\Search\Results
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
     * @var \Magento\Wishlist\Model\WishlistFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $wishlistFactoryMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionMock;

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
     * @var \Magento\Framework\App\ViewInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewMock;

    /**
     * @var \Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $strategyEmailFactoryMock;

    /**
     * @var \Magento\MultipleWishlist\Model\Search\Strategy\NameFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $strategyNameFactoryMock;

    /**
     * @var \Magento\MultipleWishlist\Model\SearchFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchFactoryMock;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->wishlistFactoryMock = $this->getMockBuilder(\Magento\Wishlist\Model\WishlistFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemFactoryMock = $this->getMockBuilder(\Magento\Wishlist\Model\ItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->searchFactoryMock = $this->getMockBuilder(\Magento\MultipleWishlist\Model\SearchFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->strategyEmailFactoryMock = $this->getMockBuilder(
            \Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory::class
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();
        $this->strategyNameFactoryMock = $this->getMockBuilder(
            \Magento\MultipleWishlist\Model\Search\Strategy\NameFactory::class
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();
        $checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutCartMock = $this->getMockBuilder(\Magento\Checkout\Model\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['setLastWishlistSearchParams'])
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

        $this->messageManagerMock = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->viewMock = $this->getMockBuilder(\Magento\Framework\App\ViewInterface::class)
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
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
        $this->contextMock->expects($this->any())
            ->method('getView')
            ->willReturn($this->viewMock);
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $this->moduleManagerMock = $this->getMockBuilder(\Magento\Framework\Module\Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new \Magento\MultipleWishlist\Controller\Search\Results(
            $this->contextMock,
            $this->registryMock,
            $itemFactoryMock,
            $this->wishlistFactoryMock,
            $this->searchFactoryMock,
            $this->strategyEmailFactoryMock,
            $this->strategyNameFactoryMock,
            $checkoutSessionMock,
            $checkoutCartMock,
            $this->customerSessionMock,
            $localeResolverMock,
            $this->moduleManagerMock
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithType()
    {
        $search = 'type';
        $params = [
            'search' => $search,
        ];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        /** @var Name|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $strategyMock = $this->getMockBuilder(\Magento\MultipleWishlist\Model\Search\Strategy\Name::class)
            ->disableOriginalConstructor()
            ->setMethods(['setSearchParams'])
            ->getMock();

        $this->strategyNameFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($strategyMock);

        $this->strategyEmailFactoryMock->expects($this->never())
            ->method('create');

        $strategyMock->expects($this->once())
            ->method('setSearchParams')
            ->with($params);

        /** @var \Magento\MultipleWishlist\Model\Search|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $searchMock = $this->getMockBuilder(\Magento\MultipleWishlist\Model\Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchMock);

        /** @var Collection|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $collectionMock = $this->getMockBuilder(\Magento\Wishlist\Model\ResourceModel\Wishlist\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchMock->expects($this->once())
            ->method('getResults')
            ->with($strategyMock)
            ->willReturn($collectionMock);

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with('search_results', $collectionMock)
            ->willReturn($searchMock);

        $this->customerSessionMock->expects($this->once())
            ->method('setLastWishlistSearchParams')
            ->with($params);

        /** @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $configMock = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithEmail()
    {
        $search = 'email';
        $params = [
            'search' => $search,
        ];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        /** @var Email|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $strategyMock = $this->getMockBuilder(\Magento\MultipleWishlist\Model\Search\Strategy\Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['setSearchParams'])
            ->getMock();

        $this->strategyEmailFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($strategyMock);

        $this->strategyNameFactoryMock->expects($this->never())
            ->method('create');

        $strategyMock->expects($this->once())
            ->method('setSearchParams')
            ->with($params);

        /** @var \Magento\MultipleWishlist\Model\Search|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $searchMock = $this->getMockBuilder(\Magento\MultipleWishlist\Model\Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchMock);

        /** @var Collection|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $collectionMock = $this->getMockBuilder(\Magento\Wishlist\Model\ResourceModel\Wishlist\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchMock->expects($this->once())
            ->method('getResults')
            ->with($strategyMock)
            ->willReturn($collectionMock);

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with('search_results', $collectionMock)
            ->willReturn($searchMock);

        $this->customerSessionMock->expects($this->once())
            ->method('setLastWishlistSearchParams')
            ->with($params);

        /** @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $configMock = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithException()
    {
        $search = 'email';
        $params = [
            'search' => $search,
        ];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        /** @var Email|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $strategyMock = $this->getMockBuilder(\Magento\MultipleWishlist\Model\Search\Strategy\Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['setSearchParams'])
            ->getMock();

        $this->strategyEmailFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($strategyMock);

        $this->strategyNameFactoryMock->expects($this->never())
            ->method('create');

        $strategyMock->expects($this->once())
            ->method('setSearchParams')
            ->with($params);

        /** @var \Magento\MultipleWishlist\Model\Search|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $searchMock = $this->getMockBuilder(\Magento\MultipleWishlist\Model\Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchMock);

        $exception = new \Exception('Exception');

        $searchMock->expects($this->once())
            ->method('getResults')
            ->with($strategyMock)
            ->willThrowException($exception);

        $this->registryMock->expects($this->never())
            ->method('register');

        $this->customerSessionMock->expects($this->never())
            ->method('setLastWishlistSearchParams');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('We could not perform the search.'))
            ->willReturnSelf();

        /** @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $configMock = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithInvalidArgumentException()
    {
        $search = 'email';
        $params = [
            'search' => $search,
        ];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        /** @var Email|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $strategyMock = $this->getMockBuilder(\Magento\MultipleWishlist\Model\Search\Strategy\Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['setSearchParams'])
            ->getMock();

        $this->strategyEmailFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($strategyMock);

        $this->strategyNameFactoryMock->expects($this->never())
            ->method('create');

        $strategyMock->expects($this->once())
            ->method('setSearchParams')
            ->with($params);

        /** @var \Magento\MultipleWishlist\Model\Search|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $searchMock = $this->getMockBuilder(\Magento\MultipleWishlist\Model\Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchMock);

        $exception = new \InvalidArgumentException('InvalidArgumentException');

        $searchMock->expects($this->once())
            ->method('getResults')
            ->with($strategyMock)
            ->willThrowException($exception);

        $this->registryMock->expects($this->never())
            ->method('register');

        $this->customerSessionMock->expects($this->never())
            ->method('setLastWishlistSearchParams');

        $this->messageManagerMock->expects($this->once())
            ->method('addNotice')
            ->with(__('InvalidArgumentException'))
            ->willReturnSelf();

        /** @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $configMock = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithWrongParam()
    {
        $search = 'wrong_param';
        $params = [
            'search' => $search,
        ];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        $this->registryMock->expects($this->never())
            ->method('register');

        $this->customerSessionMock->expects($this->never())
            ->method('setLastWishlistSearchParams');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Please reenter your search options.'))
            ->willReturnSelf();

        /** @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $configMock = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithNoParams()
    {
        $params = [];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        $this->registryMock->expects($this->never())
            ->method('register');

        $this->customerSessionMock->expects($this->never())
            ->method('setLastWishlistSearchParams');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Please reenter your search options.'))
            ->willReturnSelf();

        /** @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $configMock = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }
}
