<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Test\Unit\Model\Url;

use Magento\Staging\Model\VersionManager;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * Class StorageTest
 */
class StorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogStaging\Model\Url\Storage
     */
    private $model;

    /**
     * @var UrlPersistInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlPersistMock;

    /**
     * @var VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->urlPersistMock = $this->createMock(UrlPersistInterface::class);

        $this->versionManagerMock = $this->createMock(VersionManager::class);

        $this->model = $this->objectManager->getObject(
            \Magento\CatalogStaging\Model\Url\Storage::class,
            [
                'urlPersist' => $this->urlPersistMock,
                'versionManager' => $this->versionManagerMock,
            ]
        );
    }

    /**
     * Tests DeleteByData not in preview mode
     */
    public function testDeleteByData()
    {
        $urls = [1, 2];

        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn(false);

        $this->urlPersistMock->expects($this->once())
            ->method('deleteByData')
            ->with($urls);

        $this->model->deleteByData($urls);
    }

    /**
     * Tests DeleteByData in preview mode
     */
    public function testDeleteByDataPreview()
    {
        $urls = [1, 2];

        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn(true);

        $this->urlPersistMock->expects($this->never())
            ->method('deleteByData')
            ->with($urls);

        $this->model->deleteByData($urls);
    }

    /**
     * Tests Replace not in preview mode
     */
    public function testReplaceNotPreview()
    {
        $url1 = $this->createMock(UrlRewrite::class);
        $url2 = $this->createMock(UrlRewrite::class);
        $urls = [$url1, $url2];
        $replacedUrls = [$this->createMock(UrlRewrite::class)];

        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn(false);

        $this->urlPersistMock->expects($this->once())
            ->method('replace')
            ->with($urls)
            ->willReturn($replacedUrls);

        $this->assertEquals($replacedUrls, $this->model->replace($urls));
    }

    /**
     * Tests Replace in preview mode
     */
    public function testReplaceEmpty()
    {
        $url1 = $this->createMock(UrlRewrite::class);
        $url2 = $this->createMock(UrlRewrite::class);
        $urls = [$url1, $url2];

        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn(true);

        $this->urlPersistMock->expects($this->never())
            ->method('replace');

        $this->assertEquals([], $this->model->replace($urls));
    }
}
