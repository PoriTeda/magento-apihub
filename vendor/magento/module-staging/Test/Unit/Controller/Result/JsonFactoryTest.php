<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Controller\Result;

use Magento\Staging\Controller\Result\JsonFactory;

class JsonFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var JsonFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);

        $this->factory = new JsonFactory($this->objectManagerMock, $this->messageManagerMock);
    }

    public function testCreate()
    {
        $messageText = 'Some message';
        $messages = $messageText . '<br/>' . $messageText . '<br/>';

        $messageCollectionMock = $this->createMock(\Magento\Framework\Message\Collection::class);

        $jsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->objectManagerMock->expects($this->once())->method('create')->willReturn($jsonMock);

        $this->messageManagerMock->expects($this->once())
            ->method('getMessages')
            ->with(true)
            ->willReturn($messageCollectionMock);
        $messageMock = $this->createMock(\Magento\Framework\Message\MessageInterface::class);
        $items = [$messageMock, $messageMock];
        $messageCollectionMock->expects($this->once())->method('getItems')->willReturn($items);

        $messageMock->expects($this->exactly(2))->method('toString')->willReturn($messageText);

        $jsonMock->expects($this->once())->method('setData')->with(['messages' => $messages]);
        $this->assertEquals($jsonMock, $this->factory->create());
    }
}
