<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\General;

class CacheStatusSectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Model\Report\Group\General\CacheStatusSection
     */
    protected $cacheStatus;

    /**
     * @var \Magento\Framework\App\Cache\TypeList|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $typeListMock;

    /**
     * @var \Magento\Framework\DataObject|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectConfigMock;

    /**
     * @var \Magento\Framework\DataObject|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectLayoutMock;

    /**
     * @var \Magento\Framework\DataObject|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectBlockHtmlMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp()
    {
        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->typeListMock = $this->createMock(\Magento\Framework\App\Cache\TypeList::class);

        $this->objectConfigMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCacheType', 'getDescription', 'getTags', 'getStatus'])
            ->getMock();
        $this->objectLayoutMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCacheType', 'getDescription', 'getTags', 'getStatus'])
            ->getMock();
        $this->objectBlockHtmlMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCacheType', 'getDescription', 'getTags', 'getStatus'])
            ->getMock();

        $this->cacheStatus = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\Report\Group\General\CacheStatusSection::class,
            ['typeList' => $this->typeListMock]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $expectedData = [
            \Magento\Support\Model\Report\Group\General\CacheStatusSection::REPORT_TITLE => [
                'headers' => ['Cache', 'Status', 'Type', 'Associated Tags', 'Description'],
                'data' => [
                    [
                        'Configuration',
                        'Enabled',
                        'config',
                        'CONFIG',
                        'Various XML configurations that were collected across modules and merged'
                    ],
                    ['Layouts', 'Enabled', 'layout', 'LAYOUT_GENERAL_CACHE_TAG', 'Layout building instructions'],
                    ['Blocks HTML output', 'Enabled', 'block_html', 'BLOCK_HTML', 'Page blocks HTML']
                ]
            ]
        ];

        $invalidatedCacheTypes = [];
        $cacheTypes = [
            'config' => $this->objectConfigMock,
            'layout' => $this->objectLayoutMock,
            'block_html' => $this->objectBlockHtmlMock
        ];

        $configId = 'config';
        $configCacheType = 'Configuration';
        $configDescription = 'Various XML configurations that were collected across modules and merged';
        $configTags = 'CONFIG';

        $layoutId = 'layout';
        $layoutCacheType = 'Layouts';
        $layoutDescription = 'Layout building instructions';
        $layoutTags = 'LAYOUT_GENERAL_CACHE_TAG';

        $blockHtmlId = 'block_html';
        $blockHtmlCacheType = 'Blocks HTML output';
        $blockHtmlDescription = 'Page blocks HTML';
        $blockHtmlTags = 'BLOCK_HTML';

        $status = 1;

        $this->typeListMock->expects($this->once())->method('getInvalidated')->willReturn($invalidatedCacheTypes);
        $this->typeListMock->expects($this->once())->method('getTypes')->willReturn($cacheTypes);

        $this->objectConfigMock->expects($this->once())->method('getId')->willReturn($configId);
        $this->objectLayoutMock->expects($this->once())->method('getId')->willReturn($layoutId);
        $this->objectBlockHtmlMock->expects($this->once())->method('getId')->willReturn($blockHtmlId);

        $this->objectConfigMock->expects($this->once())->method('getCacheType')->willReturn($configCacheType);
        $this->objectLayoutMock->expects($this->once())->method('getCacheType')->willReturn($layoutCacheType);
        $this->objectBlockHtmlMock->expects($this->once())->method('getCacheType')->willReturn($blockHtmlCacheType);

        $this->objectConfigMock->expects($this->once())->method('getDescription')->willReturn($configDescription);
        $this->objectLayoutMock->expects($this->once())->method('getDescription')->willReturn($layoutDescription);
        $this->objectBlockHtmlMock->expects($this->once())->method('getDescription')->willReturn($blockHtmlDescription);

        $this->objectConfigMock->expects($this->once())->method('getTags')->willReturn($configTags);
        $this->objectLayoutMock->expects($this->once())->method('getTags')->willReturn($layoutTags);
        $this->objectBlockHtmlMock->expects($this->once())->method('getTags')->willReturn($blockHtmlTags);

        $this->objectConfigMock->expects($this->once())->method('getStatus')->willReturn($status);
        $this->objectLayoutMock->expects($this->once())->method('getStatus')->willReturn($status);
        $this->objectBlockHtmlMock->expects($this->once())->method('getStatus')->willReturn($status);

        $this->assertSame($expectedData, $this->cacheStatus->generate());
    }
}
