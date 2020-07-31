<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Controller\Adminhtml\Entity\Update;

/**
 * Class EditTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $messageManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resultRedirectFactoryMock;

    /**
     * @var \Magento\Staging\Controller\Adminhtml\Update\Edit
     */
    private $edit;

    protected function setUp()
    {
        $this->updateRepositoryMock = $this->getMockBuilder(\Magento\Staging\Api\UpdateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManagerMock = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactoryMock = $this->getMockBuilder(
            \Magento\Framework\Controller\Result\RedirectFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactoryMock);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->edit = $objectManager->getObject(
            \Magento\Staging\Controller\Adminhtml\Update\Edit::class,
            [
                'context' => $this->contextMock,
                'updateRepository' => $this->updateRepositoryMock
            ]
        );
    }

    /**
     * @dataProvider emptyIdProvider
     * @param mixed $emptyId
     */
    public function testExecuteEmptyId($emptyId)
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($emptyId);
        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage');

        $resultRedirectMock = $this->getMockBuilder(\Magento\Backend\Model\View\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('staging/update')
            ->willReturnSelf();

        $this->resultRedirectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultRedirectMock);

        $this->assertEquals($resultRedirectMock, $this->edit->execute());
    }

    public static function emptyIdProvider()
    {
        return [
            [''],
            ['test'],
            [0]
        ];
    }

    public function testExecuteNoEntity()
    {
        $notExistedId = 123;
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($notExistedId);

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage');

        $resultRedirectMock = $this->getMockBuilder(\Magento\Backend\Model\View\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('staging/update')
            ->willReturnSelf();
        $this->resultRedirectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultRedirectMock);

        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->with($notExistedId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException(__('Test')));

        $this->assertEquals($resultRedirectMock, $this->edit->execute());
    }

    public function testExecute()
    {
        $existedId = 123;
        $updateName = '1st April';

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($existedId);

        $updateMock = $this->getMockBuilder(\Magento\Staging\Api\Data\UpdateInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateMock->expects($this->once())
            ->method('getName')
            ->willReturn($updateName);

        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->with($existedId)
            ->willReturn($updateMock);

        $resultPage = $this->getMockBuilder(\Magento\Framework\Controller\ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfig', 'getTitle', 'prepend'])
            ->getMockForAbstractClass();
        $resultPage->expects($this->once())
            ->method('getConfig')
            ->willReturnSelf();
        $resultPage->expects($this->once())
            ->method('getTitle')
            ->willReturnSelf();
        $resultPage->expects($this->once())
            ->method('prepend')
            ->with($updateName)
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultPage);

        $this->assertEquals($resultPage, $this->edit->execute());
    }
}
