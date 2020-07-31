<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Test\Unit\Model\Page;

use Magento\CmsStaging\Model\Page\Hydrator;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\EntityMetadata;

class HydratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Hydrator */
    protected $hydrator;

    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $postDataProcessor;

    /** @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventManager;

    /** @var \Magento\Staging\Model\Entity\RetrieverInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRetriever;

    /** @var \Magento\Cms\Model\Page|\PHPUnit_Framework_MockObject_MockObject */
    protected $page;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var EntityMetadata|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityMetadata;

    /** @var MetadataPool |\PHPUnit_Framework_MockObject_MockObject */
    protected $metadataPool;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->postDataProcessor = $this->getMockBuilder(
            \Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor::class
        )->disableOriginalConstructor()->getMock();
        $this->eventManager = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->entityRetriever = $this->getMockBuilder(\Magento\Staging\Model\Entity\RetrieverInterface::class)
            ->getMockForAbstractClass();
        $this->page = $this->getMockBuilder(\Magento\Cms\Model\Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCreatedIn', 'getUpdatedIn', 'getData', 'setData'])
            ->getMock();
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityMetadata = $this->getMockBuilder(EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hydrator = new Hydrator(
            $this->context,
            $this->postDataProcessor,
            $this->entityRetriever,
            $this->metadataPool
        );
    }

    public function testHydrate()
    {
        $pageId = 1;
        $createdIn = 1000000001;
        $updatedIn = 1000000002;
        $linkField = 'row_id';
        $rowId = 1;
        $data = [
            'is_active' => true,
            'page_id' => $pageId,
            $linkField => $rowId,
            'created_in' => $createdIn,
            'updated_in' => $updatedIn,
        ];
        $this->context->expects($this->once())
            ->method('getEventManager')
            ->willReturn($this->eventManager);
        $this->postDataProcessor->expects($this->once())
            ->method('filter')
            ->with($data)
            ->willReturn($data);
        $this->entityRetriever->expects($this->once())
            ->method('getEntity')
            ->with($pageId)
            ->willReturn($this->page);
        $this->page->expects($this->once())
            ->method('setData')
            ->with($data);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'cms_page_prepare_save',
                ['page' => $this->page, 'request' => $this->request]
            );
        $this->page->expects($this->once())
            ->method('getCreatedIn')
            ->willReturn($createdIn);
        $this->page->expects($this->once())
            ->method('getUpdatedIn')
            ->willReturn($updatedIn);
        $this->page->expects($this->once())
            ->method('getData')
            ->with($linkField)
            ->willReturn($rowId);
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->willReturn($this->entityMetadata);
        $this->entityMetadata->expects($this->once())
            ->method('getLinkField')
            ->willReturn($linkField);
        $this->postDataProcessor->expects($this->once())
            ->method('validate')
            ->with($data)
            ->willReturn(true);
        $this->assertSame($this->page, $this->hydrator->hydrate($data));
    }

    public function testHydrateWithInvalidData()
    {
        $pageId = 1;
        $createdIn = 1000000001;
        $updatedIn = 1000000002;
        $linkField = 'row_id';
        $rowId = 1;
        $data = [
            'is_active' => 1,
            'page_id' => $pageId,
            $linkField => $rowId,
            'created_in' => $createdIn,
            'updated_in' => $updatedIn,
        ];
        $this->context->expects($this->once())
            ->method('getEventManager')
            ->willReturn($this->eventManager);
        $this->postDataProcessor->expects($this->once())
            ->method('filter')
            ->with($data)
            ->willReturn($data);
        $this->entityRetriever->expects($this->once())
            ->method('getEntity')
            ->with($pageId)
            ->willReturn($this->page);
        $this->page->expects($this->once())
            ->method('getCreatedIn')
            ->willReturn($createdIn);
        $this->page->expects($this->once())
            ->method('getUpdatedIn')
            ->willReturn($updatedIn);
        $this->page->expects($this->once())
            ->method('getData')
            ->with($linkField)
            ->willReturn($rowId);
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->willReturn($this->entityMetadata);
        $this->entityMetadata->expects($this->once())
            ->method('getLinkField')
            ->willReturn($linkField);
        $this->page->expects($this->once())
            ->method('setData')
            ->with($data);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'cms_page_prepare_save',
                ['page' => $this->page, 'request' => $this->request]
            );
        $this->postDataProcessor->expects($this->once())
            ->method('validate')
            ->with($data)
            ->willReturn(false);
        $this->assertFalse($this->hydrator->hydrate($data));
    }
}
