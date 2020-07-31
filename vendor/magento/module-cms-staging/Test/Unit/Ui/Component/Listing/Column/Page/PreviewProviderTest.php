<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Test\Unit\Ui\Component\Listing\Column\Page;

class PreviewProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CmsStaging\Ui\Component\Listing\Column\Page\PreviewProvider
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilderMock;

    protected function setUp()
    {
        $this->urlBuilderMock = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->model = new \Magento\CmsStaging\Ui\Component\Listing\Column\Page\PreviewProvider($this->urlBuilderMock);
    }

    public function testGetUrl()
    {
        $url = 'preview_url';
        $item = [
            '_first_store_id' => 'first_store_id',
            'identifier' => 'identifier',
        ];

        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with(null, ['_direct' => 'identifier', '_nosid' => true])
            ->willReturn($url);

        $this->assertEquals($url, $this->model->getUrl($item));
    }
}
