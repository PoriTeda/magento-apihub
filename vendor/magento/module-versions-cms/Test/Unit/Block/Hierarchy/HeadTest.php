<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Block\Hierarchy;

use Magento\VersionsCms\Model\CurrentNodeResolverInterface;

class HeadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Helper\Hierarchy|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cmsHierarchy;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Node|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $chapter;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Node|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $section;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Node|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $next;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Node|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $prev;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Node|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $first;

    /**
     * @var \Magento\Framework\View\LayoutInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layout;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Node|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $node;

    /**
     * @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pageConfig;

    /**
     * @var \Magento\VersionsCms\Block\Hierarchy\Head
     */
    protected $head;

    /**
     * @var CurrentNodeResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $currentNodeResolver;

    /**
     * @var \Magento\Framework\View\Element\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    protected function setUp()
    {
        $this->cmsHierarchy = $this->createMock(\Magento\VersionsCms\Helper\Hierarchy::class);

        $this->chapter = $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
        $this->section = $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
        $this->next = $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
        $this->prev = $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
        $this->first = $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);

        $this->pageConfig = $this->createMock(\Magento\Framework\View\Page\Config::class);
        $this->node = $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
        $this->layout = $this->createMock(\Magento\Framework\View\LayoutInterface::class);

        $this->currentNodeResolver = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();

        $this->context = $this->getMockBuilder(\Magento\Framework\View\Element\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        /** @var \Magento\VersionsCms\Block\Hierarchy\Head $head */
        $this->head = (new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this))
            ->getObject(
                \Magento\VersionsCms\Block\Hierarchy\Head::class,
                [
                    'cmsHierarchy' => $this->cmsHierarchy,
                    'pageConfig' => $this->pageConfig,
                    'currentNodeResolver' => $this->currentNodeResolver,
                    'context' => $this->context,
                ]
            );
    }

    public function testPrepareLayoutMetaDataEnabledAndNodeExistsShouldAddRemotePageAssets()
    {
        $chapterUrl = 'chapter/url';
        $sectionUrl = 'section/url';
        $nextUrl = 'next/url';
        $prevUrl = 'prev/url';
        $firstUrl = 'first/url';

        $treeMetaData = [
            'meta_cs_enabled' => true,
            'meta_next_previous' => true,
            'meta_first_last' => true
        ];

        $this->cmsHierarchy->expects($this->once())->method('isMetadataEnabled')->willReturn(true);

        $this->chapter->expects($this->once())->method('getId')->willReturn(1);
        $this->chapter->expects($this->once())->method('getUrl')->willReturn($chapterUrl);

        $this->section->expects($this->once())->method('getId')->willReturn(1);
        $this->section->expects($this->once())->method('getUrl')->willReturn($sectionUrl);

        $this->next->expects($this->once())->method('getId')->willReturn(1);
        $this->next->expects($this->once())->method('getUrl')->willReturn($nextUrl);

        $this->prev->expects($this->once())->method('getId')->willReturn(1);
        $this->prev->expects($this->once())->method('getUrl')->willReturn($prevUrl);

        $this->first->expects($this->once())->method('getId')->willReturn(1);
        $this->first->expects($this->once())->method('getUrl')->willReturn($firstUrl);

        $this->node->expects($this->once())->method('getTreeMetaData')->willReturn($treeMetaData);
        $this->node->expects($this->any())
            ->method('getMetaNodeByType')
            ->willReturnMap(
                [
                    [\Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_CHAPTER, $this->chapter],
                    [\Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_SECTION, $this->section],
                    [\Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_NEXT, $this->next],
                    [\Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_PREVIOUS, $this->prev],
                    [\Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_FIRST, $this->first],
                ]
            );

        $this->pageConfig->expects($this->at(0))->method('addRemotePageAsset')
            ->with(
                $chapterUrl,
                '',
                ['attributes' => ['rel' => \Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_CHAPTER]]
            )
            ->willReturnSelf();
        $this->pageConfig->expects($this->at(1))->method('addRemotePageAsset')
            ->with(
                $sectionUrl,
                '',
                ['attributes' => ['rel' => \Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_SECTION]]
            )
            ->willReturnSelf();
        $this->pageConfig->expects($this->at(2))->method('addRemotePageAsset')
            ->with(
                $nextUrl,
                '',
                ['attributes' => ['rel' => \Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_NEXT]]
            )
            ->willReturnSelf();
        $this->pageConfig->expects($this->at(3))->method('addRemotePageAsset')
            ->with(
                $prevUrl,
                '',
                ['attributes' => ['rel' => \Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_PREVIOUS]]
            )
            ->willReturnSelf();
        $this->pageConfig->expects($this->at(4))->method('addRemotePageAsset')
            ->with(
                $firstUrl,
                '',
                ['attributes' => ['rel' => \Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_FIRST]]
            )
            ->willReturnSelf();

        $this->currentNodeResolver->expects($this->once())
            ->method('get')
            ->with($this->request)
            ->willReturn($this->node);

        $this->assertSame($this->head, $this->head->setLayout($this->layout));
    }
}
