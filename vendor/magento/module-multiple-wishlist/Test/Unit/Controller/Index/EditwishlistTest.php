<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MultipleWishlist\Test\Unit\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditwishlistTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $editWishListController;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $wishListEditor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $wishList;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var \Magento\Framework\Controller\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultJsonMock;

    /** @var  int */
    protected $wishListId = 1;

    /** @var  int */
    protected $customerId = 1;

    protected $isAjax = false;

    protected $url;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formKeyValidator;

    protected function setUp()
    {
        $this->context = $this->createPartialMock(
            \Magento\Framework\App\Action\Context::class,
            ['getMessageManager', 'getRequest', 'getResponse', 'getObjectManager', 'getUrl', 'getResultFactory']
        );
        $this->wishListEditor = $this->createPartialMock(
            \Magento\MultipleWishlist\Model\WishlistEditor::class,
            ['edit']
        );
        $this->session = $this->createPartialMock(\Magento\Customer\Model\Session::class, ['getCustomerId']);
        $this->request = $this->createPartialMock(\Magento\Framework\App\Request\Http::class, ['getParam', 'isAjax']);
        $this->response = $this->createPartialMock(\Magento\Framework\App\Response\Http::class, ['representJson']);
        $this->messageManager = $this->createPartialMock(
            \Magento\Framework\Message\Manager::class,
            ['addSuccess', 'addError', 'addException']
        );
        $this->wishList = $this->createPartialMock(\Magento\Wishlist\Model\Wishlist::class, ['getId', 'getName']);
        $this->objectManager = $this->createPartialMock(
            \Magento\Framework\App\ObjectManager::class,
            ['get', 'jsonEncode', 'escapeHtml']
        );
        $this->url = $this->createPartialMock(\Magento\Framework\Url::class, ['getUrl']);

        $this->resultFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultRedirectMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->once())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->formKeyValidator = $this->getMockBuilder(\Magento\Framework\Data\Form\FormKey\Validator::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function tearDown()
    {
        unset(
            $this->url,
            $this->objectManager,
            $this->wishList,
            $this->messageManager,
            $this->response,
            $this->request,
            $this->session,
            $this->wishListEditor,
            $this->context,
            $this->editWishListController
        );
    }

    public function createController()
    {
        $this->formKeyValidator->expects($this->once())
            ->method('validate')
            ->with($this->request)
            ->willReturn(true);

        $this->editWishListController = new \Magento\MultipleWishlist\Controller\Index\Editwishlist(
            $this->context,
            $this->wishListEditor,
            $this->session,
            $this->formKeyValidator
        );
    }

    public function configureCustomerSession()
    {
        $this->session
            ->expects($this->once())
            ->method('getCustomerId')
            ->will($this->returnValue($this->customerId));
    }

    public function configureWishList($getIdExpects, $getNameExpects)
    {
        $this->wishList
            ->expects($this->exactly($getIdExpects))
            ->method('getId')
            ->will($this->returnValue($this->wishListId));
        $this->wishList
            ->expects($this->exactly($getNameExpects))
            ->method('getName')
            ->will($this->returnValue('wishlistTestName'));
    }

    public function configureObjectManager($getExpects, $jsonEncodeExpects, $escapeHtmlExpects)
    {
        $this->objectManager
            ->expects($this->exactly($getExpects))
            ->method('get')
            ->will($this->returnSelf());
        $this->objectManager
            ->expects($this->exactly($jsonEncodeExpects))
            ->method('jsonEncode')
            ->will($this->returnValue(null));
        $this->objectManager
            ->expects($this->exactly($escapeHtmlExpects))
            ->method('escapeHtml')
            ->with('wishlistTestName')
            ->will($this->returnValue('wishlistTestName'));
    }

    public function configureUrl($getUrlExpects)
    {
        $this->url
            ->expects($this->exactly($getUrlExpects))
            ->method('getUrl')
            ->will($this->returnValue(null));
    }

    public function configureContext()
    {
        $this->context
            ->expects($this->once())
            ->method('getMessageManager')
            ->will($this->returnValue($this->messageManager));
        $this->context
            ->expects($this->once())
            ->method('getObjectManager')
            ->will($this->returnValue($this->objectManager));
        $this->context
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($this->request));
        $this->context
            ->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($this->response));
        $this->context
            ->expects($this->once())
            ->method('getUrl')
            ->will($this->returnValue($this->url));
    }

    public function configureResponse($representJsonExpects)
    {
        $this->response
            ->expects($this->exactly($representJsonExpects))
            ->method('representJson')
            ->will($this->returnValue(null));
    }

    public function configureRequest()
    {
        $this->request
            ->expects($this->exactly(3))
            ->method('getParam')
            ->will($this->returnValue(null));
        $this->request
            ->expects($this->once())
            ->method('isAjax')
            ->will($this->returnValue($this->isAjax));
    }

    public function configure(
        $getIdExpects,
        $getNameExpects,
        $getExpects,
        $escapeHtmlExpects,
        $jsonEncodeExpects,
        $getUrlExpects,
        $representJsonExpects
    ) {
        $this->configureWishList($getIdExpects, $getNameExpects);
        $this->configureObjectManager($getExpects, $jsonEncodeExpects, $escapeHtmlExpects);
        $this->configureUrl($getUrlExpects);
        $this->configureResponse($representJsonExpects);
        $this->configureRequest();
        $this->configureContext();
        $this->configureCustomerSession();
        $this->createController();
    }

    public function testExecuteWithInvalidFormKey()
    {
        $this->configureContext();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->resultRedirectMock);

        $this->formKeyValidator->expects($this->once())
            ->method('validate')
            ->with($this->request)
            ->willReturn(false);

        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/')
            ->willReturnSelf();

        $controller = new \Magento\MultipleWishlist\Controller\Index\Editwishlist(
            $this->context,
            $this->wishListEditor,
            $this->session,
            $this->formKeyValidator
        );

        $this->assertSame($this->resultRedirectMock, $controller->execute());
    }

    public function testExecuteWishlistFrameworkException()
    {
        $exeption = new \Magento\Framework\Exception\LocalizedException(__('Sign in to edit wish lists.'));

        $this->messageManager
            ->expects($this->never())
            ->method('addSuccess')
            ->with('Wish list "wishlistTestName" was saved.')
            ->will($this->returnValue(null));
        $this->wishListEditor
            ->expects($this->once())
            ->method('edit')
            ->will($this->throwException($exeption));
        $this->messageManager
            ->expects($this->at(0))
            ->method('addError')
            ->with('Sign in to edit wish lists.')
            ->will($this->returnValue(null));
        $this->messageManager
            ->expects($this->at(1))
            ->method('addError')
            ->with('Could not create a wish list.')
            ->will($this->returnValue(null));

        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/*', [])
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->resultRedirectMock);

        $this->configure(0, 0, 0, 0, 0, 0, 0);
        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Redirect::class,
            $this->editWishListController->execute()
        );
    }

    public function testExecuteWishlistExceptionAndAjax()
    {
        $this->isAjax = true;
        $exeption = new \Exception(__('Sign in to edit wish lists.'));

        $this->messageManager
            ->expects($this->never())
            ->method('addSuccess')
            ->with('Wish list "wishlistTestName" was saved.')
            ->will($this->returnValue(null));
        $this->wishListEditor
            ->expects($this->once())
            ->method('edit')
            ->will($this->throwException($exeption));
        $this->messageManager
            ->expects($this->once())
            ->method('addError')
            ->with('Could not create a wish list.')
            ->will($this->returnValue(null));
        $this->messageManager
            ->expects($this->once())
            ->method('addException')
            ->with($exeption, __('We can\'t create the wish list right now.'))
            ->will($this->returnValue(null));
        $this->objectManager
            ->expects($this->never())
            ->method('escapeHtml')
            ->with('wishlistTestName')
            ->will($this->returnValue('wishlistTestName'));
        $this->url
            ->expects($this->once())
            ->method('getUrl')
            ->with('*/*', null)
            ->will($this->returnValue('magento-test.com'));

        $this->configureWishList(0, 0);
        $this->configureRequest();
        $this->configureContext();
        $this->configureCustomerSession();
        $this->createController();

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with(['redirect' => 'magento-test.com'], false, [])
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnMap([
                [ResultFactory::TYPE_REDIRECT, [], $this->resultRedirectMock],
                [ResultFactory::TYPE_JSON, [], $this->resultJsonMock],
            ]);

        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Json::class,
            $this->editWishListController->execute()
        );
    }

    public function testExecuteWithAjaxAndWishlist()
    {
        $this->isAjax = true;
        $this->configureWishList(4, 1);

        $this->messageManager
            ->expects($this->once())
            ->method('addSuccess')
            ->with('Wish list "wishlistTestName" was saved.')
            ->will($this->returnValue(null));
        $this->wishListEditor
            ->expects($this->once())
            ->method('edit')
            ->will($this->returnValue($this->wishList));
        $this->messageManager
            ->expects($this->never())
            ->method('addError')
            ->with('Could not create a wish list.')
            ->will($this->returnValue(null));
        $this->messageManager
            ->expects($this->never())
            ->method('addException')
            ->will($this->returnValue(null));
        $this->objectManager
            ->expects($this->at(0))
            ->method('get')
            ->with(\Magento\Framework\Escaper::class)
            ->will($this->returnSelf());
        $this->objectManager
            ->expects($this->at(1))
            ->method('escapeHtml')
            ->with('wishlistTestName')
            ->will($this->returnValue('wishlistTestName'));

        $this->configureRequest();
        $this->configureContext();
        $this->configureCustomerSession();
        $this->createController();

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with(['wishlist_id' => $this->wishListId, 'redirect' => null], false, [])
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnMap([
                [ResultFactory::TYPE_REDIRECT, [], $this->resultRedirectMock],
                [ResultFactory::TYPE_JSON, [], $this->resultJsonMock],
            ]);

        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Json::class,
            $this->editWishListController->execute()
        );
    }

    public function testExecuteWithoutAjax()
    {
        $this->messageManager
            ->expects($this->once())
            ->method('addSuccess')
            ->with('Wish list "wishlistTestName" was saved.')
            ->will($this->returnValue(null));

        $this->messageManager
            ->expects($this->never())
            ->method('addError')
            ->will($this->returnValue(null));

        $this->wishListEditor
            ->expects($this->once())
            ->method('edit')
            ->will($this->returnValue($this->wishList));

        $this->configure(3, 1, 1, 1, 0, 0, 0);

        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('wishlist/index/index', ['wishlist_id' => $this->wishListId])
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->resultRedirectMock);

        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Redirect::class,
            $this->editWishListController->execute()
        );
    }
}
