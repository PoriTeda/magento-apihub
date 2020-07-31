<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Plugin\Quote;

class SubstractProductFromQuotesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogStaging\Plugin\Quote\SubstractProductFromQuotes
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    protected function setup()
    {
        $this->versionManagerMock = $this->createMock(\Magento\Staging\Model\VersionManager::class);
        $this->model = new \Magento\CatalogStaging\Plugin\Quote\SubstractProductFromQuotes($this->versionManagerMock);
    }

    public function testAroundSubtractProductFromQuotesWhenVersionIsPreview()
    {
        $quoteResourceMock = $this->createMock(\Magento\Quote\Model\ResourceModel\Quote::class);
        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $closureResult = 'closure_result';

        $closure = function ($productMock) use ($closureResult) {
            return $closureResult;
        };

        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);

        $this->assertEquals(
            $quoteResourceMock,
            $this->model->aroundSubtractProductFromQuotes($quoteResourceMock, $closure, $productMock)
        );
    }

    public function testAroundSubtractProductFromQuotesWhenVersionIsNotPreview()
    {
        $quoteResourceMock = $this->createMock(\Magento\Quote\Model\ResourceModel\Quote::class);
        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $closureResult = 'closure_result';

        $closure = function ($productMock) use ($closureResult) {
            return $closureResult;
        };

        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(false);

        $this->assertEquals(
            $closureResult,
            $this->model->aroundSubtractProductFromQuotes($quoteResourceMock, $closure, $productMock)
        );
    }
}
