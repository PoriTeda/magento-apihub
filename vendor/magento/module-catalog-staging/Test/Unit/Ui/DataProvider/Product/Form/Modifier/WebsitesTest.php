<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

class WebsitesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogStaging\Ui\DataProvider\Product\Form\Modifier\Websites
     */
    private $modifier;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $modifierMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $arrayMergerMock;

    protected function setUp()
    {
        $this->modifierMock = $this->createMock(\Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Websites::class);
        $this->arrayMergerMock = $this->createMock(\Magento\Framework\Stdlib\ArrayManager::class);
        $this->modifier = new \Magento\CatalogStaging\Ui\DataProvider\Product\Form\Modifier\Websites(
            $this->arrayMergerMock,
            $this->modifierMock
        );
    }

    public function testModifyMeta()
    {
        $meta = [
            'websites' => []
        ];
        $this->modifierMock->expects($this->once())->method('modifyMeta')->willReturn($meta);
        $this->arrayMergerMock->expects($this->once())->method('get')->with('websites', $meta)->willReturn(true);
        $this->arrayMergerMock
            ->expects($this->once())
            ->method('set')
            ->with('websites/arguments/data/config/disabled', $meta, true)
            ->willReturn($meta);
        $this->assertEquals($meta, $this->modifier->modifyMeta($meta));
    }
}
