<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Preview;

use Magento\Staging\Model\Preview\UrlBuilder;
use Magento\Staging\Model\VersionManager;

class UrlBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $coreUrlBuilderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $frontendUrlMock;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    protected function setUp()
    {
        $this->coreUrlBuilderMock = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->frontendUrlMock = $this->createMock(\Magento\Framework\Url::class);
        $this->urlBuilder = new UrlBuilder(
            $this->coreUrlBuilderMock,
            $this->frontendUrlMock
        );
    }

    public function testGetPreviewUrl()
    {
        $baseUrl = 'http://www.example.com';
        $versionId = 1;
        $this->coreUrlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with(
                UrlBuilder::URL_PATH_PREVIEW,
                [
                    '_query' => [
                        UrlBuilder::PARAM_PREVIEW_VERSION => $versionId,
                        UrlBuilder::PARAM_PREVIEW_URL => $baseUrl
                    ]
                ]
            );
        $this->urlBuilder->getPreviewUrl($versionId, $baseUrl);
    }

    public function testGetFrontendPreviewUrl()
    {
        $baseUrl = 'http://www.example.com';
        $versionId = 1;
        $this->frontendUrlMock->expects($this->once())->method('getUrl')->willReturn($baseUrl);
        $this->coreUrlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with(
                UrlBuilder::URL_PATH_PREVIEW,
                [
                    '_query' => [
                        UrlBuilder::PARAM_PREVIEW_VERSION => $versionId,
                        UrlBuilder::PARAM_PREVIEW_URL => $baseUrl
                    ],
                ]
            );
        $this->urlBuilder->getPreviewUrl($versionId);
    }
}
