<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Test\Unit\Controller\Adminhtml\Category;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Abstract shared functionality for controller tests
 */
class AbstractGrid extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    protected $controllerClass;

    /**
     * @var \Magento\VisualMerchandiser\Controller\Adminhtml\Category\Grid
     */
    protected $gridController;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $rawFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $category;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $block;

    /**
     * Set up instances and mock objects
     */
    protected function setUp()
    {
        $this->context = $this->createMock(\Magento\Backend\App\Action\Context::class);

        $this->category = $this->createPartialMock(\Magento\Catalog\Model\Category::class, ['setStoreId']);

        $this->objectManager = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager
            ->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->category));

        $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);

        $this->context
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->request));

        $this->context
            ->expects($this->any())
            ->method('getObjectManager')
            ->will($this->returnValue($this->objectManager));

        $this->layoutFactory = $this->getMockBuilder(\Magento\Framework\View\LayoutFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->block = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            ['toHtml', 'setPositionCacheKey']
        );
        $this->block
            ->expects($this->atLeastOnce())
            ->method('toHtml')
            ->will($this->returnValue('block-html'));

        $resultRaw = (new ObjectManager($this))->getObject(\Magento\Framework\Controller\Result\Raw::class);
        $this->rawFactory = $this->createPartialMock(
            \Magento\Framework\Controller\Result\RawFactory::class,
            ['create']
        );
        $this->rawFactory
            ->expects($this->any())
            ->method('create')
            ->will($this->returnValue($resultRaw));

        $registry = $this->createPartialMock(\Magento\Framework\Registry::class, ['register']);
        $wysiwygConfig = $this->createPartialMock(\Magento\Cms\Model\Wysiwyg\Config::class, ['setStoreId']);
        $this->objectManager
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                [\Magento\Framework\Registry::class, $registry],
                [\Magento\Cms\Model\Wysiwyg\Config::class, $wysiwygConfig]
            ]));

        $this->gridController = (new ObjectManager($this))->getObject($this->controllerClass, [
            'context' => $this->context,
            'resultRawFactory' => $this->rawFactory,
            'layoutFactory' => $this->layoutFactory
        ]);
    }

    protected function progressTest($block, $id)
    {
        $layout = $this->createPartialMock(\Magento\Framework\DataObject::class, ['createBlock']);
        $layout
            ->expects($this->any())
            ->method('createBlock')
            ->with(
                $this->equalTo($block),
                $this->equalTo($id)
            )
            ->will($this->returnValue($this->block));

        $this->layoutFactory
            ->expects($this->any())
            ->method('create')
            ->will($this->returnValue($layout));

        $this->assertInstanceOf(
            \Magento\Framework\Controller\Result\Raw::class,
            $this->gridController->execute()
        );
    }
}
