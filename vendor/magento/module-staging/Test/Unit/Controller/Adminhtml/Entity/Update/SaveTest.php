<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Controller\Adminhtml\Entity\Update;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $updateRepositoryMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $updateFactoryMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $compaignValidatorMock;

    /** @var \Magento\Staging\Controller\Adminhtml\Update\Save */
    private $save;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $messageManagerMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $resultFactoryMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $requestMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $resultRedirectFactoryMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $redirectMock;

    protected function setUp()
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateRepositoryMock = $this->getMockBuilder(\Magento\Staging\Api\UpdateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateFactoryMock = $this->getMockBuilder(\Magento\Staging\Model\UpdateFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->compaignValidatorMock = $this->getMockBuilder(
            \Magento\Staging\Model\ResourceModel\Db\CampaignValidator::class
        )
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
        $this->save = $objectManager->getObject(
            \Magento\Staging\Controller\Adminhtml\Update\Save::class,
            [
                'context' => $this->contextMock,
                'updateRepository' => $this->updateRepositoryMock,
                'campaignValidator' => $this->compaignValidatorMock,
            ]
        );
    }

    public function testExecute()
    {
        $generalData = [
            'id' => 123,
            'end_time' => '2017-01-31 16:22:09',
            'start_time' => '2029-12-12 16:22:09',
        ];

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('general')
            ->willReturn($generalData);
        $updateMock = $this->getMockBuilder(\Magento\Staging\Model\Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateMock->expects($this->exactly(1))
            ->method('getStartTime')
            ->willReturn('2029-12-12 16:22:09');
        $updateMock->expects($this->once())
            ->method('setData')
            ->willReturn(true);
        $this->compaignValidatorMock->expects($this->once())
            ->method('canBeUpdated')
            ->willReturn(true);
        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($updateMock);
        $this->updateRepositoryMock->expects($this->once())
            ->method('save')
            ->with($updateMock)
            ->willReturn(true);
        $this->resultRedirectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->redirectMock);
        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->willReturnSelf();

        $this->assertEquals($this->redirectMock, $this->save->execute());
    }
}
