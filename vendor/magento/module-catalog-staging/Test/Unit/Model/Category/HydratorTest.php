<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\Category;

use Magento\CatalogStaging\Model\Category\Hydrator;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HydratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \Magento\Staging\Model\Entity\RetrieverInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryFactory;

    /** @var \Magento\Catalog\Controller\Adminhtml\Category\Save|\PHPUnit_Framework_MockObject_MockObject */
    protected $originalController;

    /** @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventManager;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $messageManager;

    /** @var \Magento\Catalog\Model\Category|\PHPUnit_Framework_MockObject_MockObject */
    protected $category;

    /** @var \Magento\Catalog\Model\ResourceModel\Category|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryResource;

    /** @var Hydrator */
    protected $hydrator;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryFactory = $this->getMockBuilder(\Magento\Catalog\Model\CategoryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->originalController = $this->getMockBuilder(\Magento\Catalog\Controller\Adminhtml\Category\Save::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventManager = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->category = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryResource = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hydrator = new Hydrator(
            $this->context,
            $this->categoryFactory,
            $this->originalController
        );
    }

    public function testHydrate()
    {
        $categoryPosition = 2;
        $useConfig = ['attribute_code' => 'attribute_value'];
        $data = [
            'position' => $categoryPosition,
            'use_config' => $useConfig,
        ];
        $this->context->expects($this->once())
            ->method('getEventManager')
            ->willReturn($this->eventManager);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'catalog_category_prepare_save',
                ['category' => $this->category, 'request' => $this->request]
            );
        $this->category->expects($this->once())
            ->method('addData')
            ->with([
                'position' => $categoryPosition,
                'use_config' => $useConfig,
            ]);
        $this->originalController->expects($this->once())
            ->method('stringToBoolConverting')
            ->with($data)
            ->willReturnArgument(0);
        $this->originalController->expects($this->once())
            ->method('imagePreprocessing')
            ->with($data)
            ->willReturnArgument(0);

        $this->categoryFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->category);
        $this->category->expects($this->atLeastOnce())
            ->method('setData')
            ->withConsecutive(
                ['attribute_code', null],
                ['use_post_data_config', ['attribute_code']]
            );
        $this->category->expects($this->once())
            ->method('getResource')
            ->willReturn($this->categoryResource);
        $this->category->expects($this->once())
            ->method('validate')
            ->willReturn(true);
        $this->category->expects($this->once())
            ->method('unsetData')
            ->with('use_post_data_config');
        $this->assertSame($this->category, $this->hydrator->hydrate($data));
    }
}
