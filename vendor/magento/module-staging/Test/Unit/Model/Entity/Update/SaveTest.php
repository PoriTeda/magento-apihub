<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Entity\Update;

use Magento\Framework\Exception\LocalizedException;
use Magento\Staging\Model\Entity\Update\Save;

class SaveTest extends \PHPUnit\Framework\TestCase
{
    /** @var Save */
    protected $save;

    /** @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $messageManager;

    /** @var \Magento\Staging\Controller\Result\JsonFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $jsonFactory;

    /** @var \Magento\Staging\Model\Entity\Update\Action\Pool|\PHPUnit_Framework_MockObject_MockObject */
    protected $actionPool;

    /** @var \Magento\Staging\Model\Entity\Update\Action\Save\SaveAction|\PHPUnit_Framework_MockObject_MockObject */
    protected $action;

    /** @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject */
    protected $resultJson;

    /** @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var string */
    private $entityName = 'entity name';

    public function setUp()
    {
        $this->jsonFactory = $this->getMockBuilder(\Magento\Staging\Controller\Result\JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionPool = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\Action\Pool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->action = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\Action\Save\SaveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->resultJson = $this->getMockBuilder(\Magento\Framework\Controller\Result\Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->save = new Save(
            $this->messageManager,
            $this->jsonFactory,
            $this->actionPool,
            $this->logger,
            $this->entityName
        );
    }

    public function testExecuteWIthLocalizedException()
    {
        $params = [
            'stagingData' => [
                'mode' => 'save'
            ]
        ];

        $this->actionPool->expects($this->once())
            ->method('getAction')
            ->with($this->entityName, 'save', 'save')
            ->willReturn($this->action);
        $this->actionPool->expects($this->once())
            ->method('getExecutor')
            ->with($this->action)
            ->willReturnArgument(0);
        $exception = new LocalizedException(__('Error'));
        $this->action->expects($this->once())
            ->method('execute')
            ->with($params)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('Error');
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->with([], ['error' => true])
            ->willReturn($this->resultJson);
        $this->assertSame($this->resultJson, $this->save->execute($params));
    }

    public function testExecuteWIthException()
    {
        $params = [
            'stagingData' => [
                'mode' => 'save'
            ]
        ];

        $exception = new \Exception('Something went wrong');
        $this->actionPool->expects($this->once())
            ->method('getAction')
            ->with($this->entityName, 'save', 'save')
            ->willReturn($this->action);
        $this->actionPool->expects($this->once())
            ->method('getExecutor')
            ->with($this->action)
            ->willReturnArgument(0);
        $this->action->expects($this->once())
            ->method('execute')
            ->with($params)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addException')
            ->with($exception, __('Something went wrong while saving the %1.', 'entity name'));
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);
        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->with([], ['error' => true])
            ->willReturn($this->resultJson);

        $this->assertSame($this->resultJson, $this->save->execute($params));
    }

    public function testExecute()
    {
        $params = [
            'stagingData' => [
                'mode' => 'save'
            ]
        ];

        $this->actionPool->expects($this->once())
            ->method('getAction')
            ->with($this->entityName, 'save', 'save')
            ->willReturn($this->action);
        $this->actionPool->expects($this->once())
            ->method('getExecutor')
            ->with($this->action)
            ->willReturnArgument(0);
        $this->action->expects($this->once())
            ->method('execute')
            ->with($params)
            ->willReturn(true);
        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->with([], ['error' => false])
            ->willReturn($this->resultJson);
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with(__('You saved this %1 update.', 'entity name'));

        $this->assertSame($this->resultJson, $this->save->execute($params));
    }

    public function testExecuteWithoutModeValue()
    {
        $params = [
            'stagingData' => [
            ]
        ];
        $this->actionPool->expects($this->never())
            ->method('getAction');
        $this->actionPool->expects($this->never())
            ->method('getExecutor')
            ->with($this->action)
            ->willReturnArgument(0);
        $exception = new LocalizedException(__('The \'mode\' value is unexpected.'));
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('The \'mode\' value is unexpected.');
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->with([], ['error' => true])
            ->willReturn($this->resultJson);
        $this->assertSame($this->resultJson, $this->save->execute($params));
    }
}
