<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Test\Unit\Controller\Adminhtml\Products;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassAssignTest extends \PHPUnit\Framework\TestCase
{
    /*
     * @var \Magento\VisualMerchandiser\Controller\Adminhtml\Products\MassAssign
     */
    protected $controller;

    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $context;

    /**
     * Magento\Framework\DataObject
     */
    protected $response;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layout;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface
     */
    protected $product;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultJson;

    /**
     * Set up instances and mock objects
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->context = $this->createMock(\Magento\Backend\App\Action\Context::class);

        $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);

        $this->objectManager = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasMessages'])
            ->getMockForAbstractClass();

        $this->layout = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['initMessages'])
            ->getMockForAbstractClass();

        $this->resultJson = $this->getMockBuilder(\Magento\Framework\Controller\Result\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJson
            ->expects($this->any())
            ->method('setJsonData')
            ->willReturn($this->resultJson);

        $this->product = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->product
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->productRepository = $this->getMockBuilder(\Magento\Catalog\Api\ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository
            ->expects($this->any())
            ->method('get')
            ->willReturn($this->product);

        $resultJsonFactory = $this->getMockBuilder(\Magento\Framework\Controller\Result\JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $resultJsonFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJson);

        $this->context
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->request));

        $this->context
            ->expects($this->any())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);

        $this->context
            ->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManager);

        $this->response = $this->createPartialMock(\Magento\Framework\DataObject::class, ['setError']);

        $this->objectManager
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->response);

        $this->layoutFactory = $this->getMockBuilder(\Magento\Framework\View\LayoutFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->layoutFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->layout);

        $messagesBlock = $this->getMockBuilder(\Magento\Framework\View\Element\Messages::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->layout
            ->expects($this->any())
            ->method('getMessagesBlock')
            ->willReturn($messagesBlock);

        $this->controller = (new ObjectManager($this))->getObject(
            \Magento\VisualMerchandiser\Controller\Adminhtml\Products\MassAssign::class,
            [
                'context' => $this->context,
                'layoutFactory' => $this->layoutFactory,
                'resultJsonFactory' => $resultJsonFactory,
                'productRepository' => $this->productRepository
            ]
        );
    }

    /**
     * Test execute assign method
     */
    public function testExecuteAssign()
    {
        $map = [
            ['action', null, 'assign'],
            ['add_product_sku', null, '24-MB01']
        ];

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getParam')
            ->will($this->returnValueMap($map));

        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Json::class,
            $this->controller->execute()
        );
    }

    /**
     * Test execute remove method
     */
    public function testExecuteRemove()
    {
        $map = [
            ['action', null, 'remove'],
            ['add_product_sku', null, '24-MB01']
        ];

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getParam')
            ->will($this->returnValueMap($map));

        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Json::class,
            $this->controller->execute()
        );
    }
}
