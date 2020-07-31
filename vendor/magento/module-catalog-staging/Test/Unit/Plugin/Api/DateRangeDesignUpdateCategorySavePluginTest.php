<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Plugin\Api;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\CatalogStaging\Plugin\Api\DateRangeDesignUpdateCategorySavePlugin;
use Magento\Framework\TestFramework\Unit\Matcher\MethodInvokedAtIndex;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\VersionManager;

class DateRangeDesignUpdateCategorySavePluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManager;

    /**
     * @var DateRangeDesignUpdateCategorySavePlugin
     */
    private $plugin;

    public function setUp()
    {
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new DateRangeDesignUpdateCategorySavePlugin(
            $this->versionManager
        );
    }

    public function testBeforeSave()
    {
        $startTime = '1970-01-01 00:00:00';
        $endTime = '1971-01-01 00:00:00';

        $category = $this->createMock(CategoryInterface::class);
        $version = $this->createMock(UpdateInterface::class);

        $this->versionManager->expects(static::once())
            ->method('getVersion')
            ->willReturn($version);
        $version->expects(static::once())
            ->method('getStartTime')
            ->willReturn($startTime);
        $version->expects(static::once())
            ->method('getEndTime')
            ->willReturn($endTime);

        $category->expects(new MethodInvokedAtIndex(0))
            ->method('setCustomAttribute')
            ->with(
                'custom_design_from',
                $startTime
            );

        $category->expects(new MethodInvokedAtIndex(1))
            ->method('setCustomAttribute')
            ->with(
                'custom_design_to',
                $endTime
            );

        $this->plugin->beforeSave(
            $this->createMock(CategoryRepositoryInterface::class),
            $category
        );
    }
}
