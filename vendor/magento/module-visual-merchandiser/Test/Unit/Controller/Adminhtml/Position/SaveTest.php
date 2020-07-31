<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Test\Unit\Controller\Adminhtml\Position;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class SaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VisualMerchandiser\Controller\Adminhtml\Position\Save
     */
    protected $controller;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultJson;

    /**
     * @var SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializer;

    /**
     * Set up instances and mock objects
     */
    protected function setUp()
    {
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);

        $this->resultJson = $this->getMockBuilder(\Magento\Framework\Controller\Result\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultJsonFactory = $this->getMockBuilder(\Magento\Framework\Controller\Result\JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $resultJsonFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJson);

        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $context = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->setMethods(['getRequest'])
            ->setConstructorArgs($helper->getConstructArguments(\Magento\Backend\App\Action\Context::class))
            ->getMock();

        $cache = $this->getMockBuilder(\Magento\VisualMerchandiser\Model\Position\Cache::class)
            ->setConstructorArgs(
                $helper->getConstructArguments(\Magento\VisualMerchandiser\Model\Position\Cache::class)
            )
            ->getMock();
        $context->expects($this->once())->method('getRequest')->will($this->returnValue($this->requestMock));

        $this->controller = (new ObjectManager($this))->getObject(
            \Magento\VisualMerchandiser\Controller\Adminhtml\Position\Save::class,
            [
                'context' => $context,
                'cache' => $cache,
                'resultJsonFactory' => $resultJsonFactory,
                'jsonDecoder' => $this->serializer
            ]
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {
        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Json::class,
            $this->controller->execute()
        );
    }
}
