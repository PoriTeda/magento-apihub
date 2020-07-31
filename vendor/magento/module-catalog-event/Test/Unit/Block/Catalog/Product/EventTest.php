<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Test\Unit\Block\Catalog\Product;

class EventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogEvent\Block\Catalog\Product\Event
     */
    protected $block;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);

        $this->block = $objectManager->getObject(
            \Magento\CatalogEvent\Block\Catalog\Product\Event::class,
            ['registry' => $this->registryMock]
        );
    }

    protected function tearDown()
    {
        $this->block = null;
    }

    public function testGetIdentities()
    {
        $eventTags = ['catalog_category_1'];
        $productMock = $this->createPartialMock(\Magento\Catalog\Model\Product::class, ['getEvent']);
        $eventMock = $this->createMock(\Magento\CatalogEvent\Model\Event::class);
        $eventMock->expects($this->once())
            ->method('getIdentities')
            ->willReturn($eventTags);
        $productMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->registryMock->expects(
            $this->exactly(2)
        )->method(
            'registry'
        )->with(
            'current_product'
        )->will(
            $this->returnValue($productMock)
        );
        $this->assertEquals($eventTags, $this->block->getIdentities());
    }

    /**
     * @param int $categoryId
     * @param array $noEventTags
     * @dataProvider getIdentitiesNoEventDataProvider
     */
    public function testGetIdentitiesNoEvent($categoryId, $noEventTags)
    {
        $productMock = $this->createPartialMock(\Magento\Catalog\Model\Product::class, ['getCategoryId']);
        $productMock->expects($this->once())->method('getCategoryId')->will($this->returnValue($categoryId));

        $this->registryMock->expects(
            $this->exactly(3)
        )->method(
            'registry'
        )->with(
            'current_product'
        )->will(
            $this->returnValue($productMock)
        );
        $this->assertEquals($noEventTags, $this->block->getIdentities());
    }

    public function getIdentitiesNoEventDataProvider()
    {
        return [
            [1, ['cat_c_p_1']],
            [false, []]
        ];
    }
}
