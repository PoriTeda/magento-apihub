<?php
namespace Riki\Catalog\Model\CatalogUrlRewrite;

use Magento\Catalog\Model\Product;
use Magento\CatalogUrlRewrite\Model\ObjectRegistryFactory;
use Magento\CatalogUrlRewrite\Model\Product\CanonicalUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\Product\CategoriesUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\Product\CurrentUrlRewritesRegenerator;
use Magento\CatalogUrlRewrite\Service\V1\StoreViewService;
use Magento\Store\Model\Store;

class ProductUrlRewriteGenerator extends \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator
{
    /**
     * @var \Riki\Catalog\Model\ResourceModel\ProductUrlRewrite
     */
    protected $resource;

    /**
     * ProductUrlRewriteGenerator constructor.
     * @param CanonicalUrlRewriteGenerator $canonicalUrlRewriteGenerator
     * @param CurrentUrlRewritesRegenerator $currentUrlRewritesRegenerator
     * @param CategoriesUrlRewriteGenerator $categoriesUrlRewriteGenerator
     * @param ObjectRegistryFactory $objectRegistryFactory
     * @param StoreViewService $storeViewService
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Riki\Catalog\Model\ResourceModel\ProductUrlRewrite $resource
     */
    public function __construct(
        CanonicalUrlRewriteGenerator $canonicalUrlRewriteGenerator,
        CurrentUrlRewritesRegenerator $currentUrlRewritesRegenerator,
        CategoriesUrlRewriteGenerator $categoriesUrlRewriteGenerator,
        ObjectRegistryFactory $objectRegistryFactory,
        StoreViewService $storeViewService,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\Catalog\Model\ResourceModel\ProductUrlRewrite $resource
    )
    {
        $this->resource = $resource;

        parent::__construct(
            $canonicalUrlRewriteGenerator,
            $currentUrlRewritesRegenerator,
            $categoriesUrlRewriteGenerator,
            $objectRegistryFactory,
            $storeViewService,
            $storeManager
        );
    }
}
