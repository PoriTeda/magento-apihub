<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Plugin\Api;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Api\AttributeInterfaceFactory;
use Magento\Staging\Model\VersionManager;

class DateRangeDesignUpdateCategorySavePlugin
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var string
     */
    private static $designFromKey = 'custom_design_from';

    /**
     * @var string
     */
    private static $designToKey = 'custom_design_to';

    /**
     * DateRangeDesignUpdateCategorySavePlugin constructor.
     * @param VersionManager $versionManager
     */
    public function __construct(
        VersionManager $versionManager
    ) {
        $this->versionManager = $versionManager;
    }

    /**
     * @param CategoryRepositoryInterface $subject
     * @param CategoryInterface $category
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(CategoryRepositoryInterface $subject, CategoryInterface $category)
    {
        $version = $this->versionManager->getVersion();

        $category->setCustomAttribute(self::$designFromKey, $version->getStartTime());
        $category->setCustomAttribute(self::$designToKey, $version->getEndTime());
    }
}
