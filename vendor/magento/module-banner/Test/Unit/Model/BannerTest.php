<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Test\Unit\Model;

class BannerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Banner\Model\Banner
     */
    protected $banner;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->banner = $objectManager->getObject(\Magento\Banner\Model\Banner::class);
    }

    protected function tearDown()
    {
        $this->banner = null;
    }

    public function testGetIdentities()
    {
        $id = 1;
        $this->banner->setId($id);
        $this->assertEquals(
            [\Magento\Banner\Model\Banner::CACHE_TAG . '_' . $id],
            $this->banner->getIdentities()
        );
    }
}
