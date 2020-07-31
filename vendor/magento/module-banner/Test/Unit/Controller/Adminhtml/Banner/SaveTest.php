<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Test\Unit\Controller\Adminhtml\Banner;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Banner\Controller\Adminhtml\Banner\Save
     */
    protected $saveController;

    /**
     * @var \Magento\Banner\Model\Banner\Validator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $bannerValidatorMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Backend\Model\View\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $redirectMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->bannerValidatorMock = $this->getMockBuilder(\Magento\Banner\Model\Banner\Validator::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepareSaveData'])
            ->getMock();

        $this->saveController = $this->objectManager->getObject(
            \Magento\Banner\Controller\Adminhtml\Banner\Save::class,
            [
                'context' => $this->prepareContext(),
                'bannerValidator' => $this->bannerValidatorMock
            ]
        );
    }

    protected function prepareContext()
    {
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->setMethods(['getPostValue'])
            ->getMockForAbstractClass();
        $this->redirectMock = $this->getMockBuilder(\Magento\Backend\Model\View\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->getMockForAbstractClass();
        $this->messageManagerMock = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->sessionMock = $this->getMockBuilder(\Magento\Backend\Model\Session::class)
            ->setMethods(['setFormData'])
            ->disableOriginalConstructor()
            ->getMock();

        $resultFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->redirectMock);

        $context = $this->objectManager->getObject(
            \Magento\Backend\App\Action\Context::class,
            [
                'request' => $this->requestMock,
                'resultFactory' => $resultFactoryMock,
                'objectManager' => $this->objectManagerMock,
                'messageManager' => $this->messageManagerMock,
                'session' => $this->sessionMock
            ]
        );

        return $context;
    }

    public function testExecuteNoPostData()
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('back', false)
            ->willReturn(false);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn(null);

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/')
            ->willReturnSelf();

        $this->assertInstanceOf(
            \Magento\Backend\Model\View\Result\Redirect::class,
            $this->saveController->execute()
        );
    }

    public function testExecuteBannerNoExist()
    {
        $bannerId = 10;
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['back', false, false],
                    ['banner_id', null, $bannerId],
                    ['id', null, $bannerId]
                ]
            );

        $bannerMock = $this->getBannerModel();
        $bannerMock->expects($this->once())
            ->method('setStoreId')
            ->with(0)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('load')
            ->with($bannerId)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn(['key', 'value']);

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/')
            ->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('This dynamic block does not exist.'))
            ->willReturnSelf();

        $this->assertInstanceOf(
            \Magento\Backend\Model\View\Result\Redirect::class,
            $this->saveController->execute()
        );
    }

    protected function getBannerModel()
    {
        $bannerMock = $this->getMockBuilder(\Magento\Banner\Model\Banner::class)
            ->disableOriginalConstructor()
            ->setMethods(['setStoreId', 'load', 'getId', 'save', 'addData', 'getStoreContents'])
            ->getMock();

        $this->objectManagerMock->expects($this->any())
            ->method('create')
            ->with(\Magento\Banner\Model\Banner::class)
            ->willReturn($bannerMock);

        return $bannerMock;
    }

    public function testExecuteWithLocalizedException()
    {
        $bannerId = 10;
        $storeId = 0;
        $postData = ['key', 'value'];
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['back', false, false],
                    ['banner_id', null, $bannerId],
                    ['id', null, $bannerId]
                ]
            );

        $bannerMock = $this->getBannerModel();
        $bannerMock->expects($this->once())
            ->method('setStoreId')
            ->with($storeId)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('load')
            ->with($bannerId)
            ->willReturnSelf();
        $bannerMock->expects($this->any())
            ->method('getId')
            ->willReturn($bannerId);
        $bannerMock->expects($this->once())
            ->method('save')
            ->willThrowException(new \Magento\Framework\Exception\LocalizedException(__('Error')));

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/edit', ['id' => $bannerId, 'store' => $storeId])
            ->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Error'))
            ->willReturnSelf();

        $this->bannerValidatorMock->expects($this->once())
            ->method('prepareSaveData')
            ->with($postData)
            ->willReturn([]);

        $this->assertInstanceOf(
            \Magento\Backend\Model\View\Result\Redirect::class,
            $this->saveController->execute()
        );
    }

    public function testExecuteWithException()
    {
        $bannerId = 10;
        $storeId = 0;
        $postData = ['key', 'value'];
        $exception = new \Exception(__('Error'));
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['back', false, false],
                    ['banner_id', null, $bannerId],
                    ['id', null, $bannerId]
                ]
            );

        $bannerMock = $this->getBannerModel();
        $bannerMock->expects($this->once())
            ->method('setStoreId')
            ->with($storeId)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('load')
            ->with($bannerId)
            ->willReturnSelf();
        $bannerMock->expects($this->any())
            ->method('getId')
            ->willReturn($bannerId);
        $bannerMock->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/edit', ['id' => $bannerId, 'store' => $storeId])
            ->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('We cannot save the dynamic block.'))
            ->willReturnSelf();

        $this->bannerValidatorMock->expects($this->once())
            ->method('prepareSaveData')
            ->with($postData)
            ->willReturn([]);

        $loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->getMockForAbstractClass();
        $loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception)
            ->willReturnSelf();

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Psr\Log\LoggerInterface::class)
            ->willReturn($loggerMock);

        $this->assertInstanceOf(
            \Magento\Backend\Model\View\Result\Redirect::class,
            $this->saveController->execute()
        );
    }

    public function testExecute()
    {
        $bannerId = 10;
        $postData = ['key', 'value'];
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['back', false, false],
                    ['banner_id', null, $bannerId],
                    ['id', null, $bannerId]
                ]
            );

        $bannerMock = $this->getBannerModel();
        $bannerMock->expects($this->any())
            ->method('getStoreContents')
            ->willReturn(null);
        $bannerMock->expects($this->once())
            ->method('setStoreId')
            ->with(0)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('load')
            ->with($bannerId)
            ->willReturnSelf();
        $bannerMock->expects($this->any())
            ->method('getId')
            ->willReturn($bannerId);
        $bannerMock->expects($this->once())
            ->method('addData')
            ->with($postData)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/')
            ->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('You saved the dynamic block.'))
            ->willReturnSelf();

        $this->bannerValidatorMock->expects($this->once())
            ->method('prepareSaveData')
            ->with($postData)
            ->willReturn($postData);

        $this->sessionMock->expects($this->any())
            ->method('setFormData')
            ->willReturnMap(
                [
                    [$postData, null],
                    [false, null]
                ]
            );

        $this->assertInstanceOf(
            \Magento\Backend\Model\View\Result\Redirect::class,
            $this->saveController->execute()
        );
    }
}
