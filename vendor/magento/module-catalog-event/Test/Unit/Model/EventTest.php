<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Test\Unit\Model;

class EventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogEvent\Model\Event
     */
    protected $model;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(\Magento\CatalogEvent\Model\Event::class);
    }

    protected function tearDown()
    {
        $this->model = null;
    }

    public function testGetIdentities()
    {
        $categoryId = 'categoryId';
        $eventId = 'eventId';
        $this->model->setCategoryId($categoryId);
        $this->model->setId($eventId);
        $eventTags = [
            \Magento\CatalogEvent\Model\Event::CACHE_TAG . '_' . $eventId,
            \Magento\Catalog\Model\Category::CACHE_TAG . '_' . $categoryId,
            \Magento\Catalog\Model\Product::CACHE_PRODUCT_CATEGORY_TAG . '_' . $categoryId,
        ];
        $this->assertEquals($eventTags, $this->model->getIdentities());
    }
}
