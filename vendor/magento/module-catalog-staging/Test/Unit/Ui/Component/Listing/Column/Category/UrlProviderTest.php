<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Ui\Component\Listing\Column\Category;

use Magento\CatalogStaging\Ui\Component\Listing\Column\Category\UrlProvider;
use Magento\Framework\Url;

class UrlProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Url|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    /**
     * @var UrlProvider
     */
    private $urlProvider;

    protected function setUp()
    {
        $this->urlBuilderMock = $this->getMockBuilder(Url::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlProvider = new UrlProvider(
            $this->urlBuilderMock
        );
    }

    public function testGetUrl()
    {
        $item = [
            'entity_id' => 1
        ];
        $this->urlBuilderMock->expects(static::once())
            ->method('getUrl')
            ->with(
                'catalog/category/view',
                [
                    'id' => $item['entity_id'],
                    '_nosid' => true
                ]
            );

        $this->urlProvider->getUrl($item);
    }
}
