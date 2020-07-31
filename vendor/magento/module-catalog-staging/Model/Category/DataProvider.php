<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Category;

use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Staging\Model\Entity\DataProvider\MetadataProvider;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\EavValidationRules;
use Magento\Catalog\Model\CategoryFactory;

/**
 * Class DataProvider
 */
class DataProvider extends CategoryDataProvider
{
    /**
     * {@inheritdoc}
     */
    protected $ignoreFields = [
        'products_position'
    ];

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param EavValidationRules $eavValidationRules
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\Registry $registry
     * @param Config $eavConfig
     * @param \Magento\Framework\App\RequestInterface $request
     * @param CategoryFactory $categoryFactory
     * @param MetadataProvider $metadataProvider
     * @param array $meta
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        EavValidationRules $eavValidationRules,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        Config $eavConfig,
        \Magento\Framework\App\RequestInterface $request,
        CategoryFactory $categoryFactory,
        MetadataProvider $metadataProvider,
        array $meta = [],
        array $data = []
    ) {
        $meta = array_replace_recursive($meta, $metadataProvider->getMetadata());
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $eavValidationRules,
            $categoryCollectionFactory,
            $storeManager,
            $registry,
            $eavConfig,
            $request,
            $categoryFactory,
            $meta,
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributesMeta(\Magento\Eav\Model\Entity\Type $entityType)
    {
        $result = parent::getAttributesMeta($entityType);
        unset($result['url_key']);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFieldsMap()
    {
        return [
            'general' => [
                'parent',
                'path',
                'is_active',
                'include_in_menu',
                'name',
            ],
            'content' => [
                'image',
                'savedImage.delete',
                'savedImage.value',
                'description',
                'landing_page',
            ],
            'display_settings' => [
                'display_mode',
                'is_anchor',
                'available_sort_by',
                'use_config.available_sort_by',
                'default_sort_by',
                'use_config.default_sort_by',
                'filter_price_range',
                'use_config.filter_price_range',
            ],
            'search_engine_optimization' => [
                'meta_title',
                'meta_keywords',
                'meta_description',
            ],
            'assign_products' => [],
            'design' => [
                'custom_design',
                'custom_use_parent_settings',
                'custom_apply_to_products',
                'page_layout',
                'custom_layout_update',
            ],
        ];
    }
}
