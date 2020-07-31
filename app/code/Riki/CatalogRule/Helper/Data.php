<?php
namespace Riki\CatalogRule\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Registry;
use Riki\CatalogRule\Model\ResourceModel\Rule;

class Data extends AbstractHelper
{
    protected $coreRegistry;

    public function __construct(
        Registry $coreRegistry,
        Context $context
    )
    {
        parent::__construct($context);

        $this->coreRegistry = $coreRegistry;
    }

    public function registerPreLoadedProductIds($productIds)
    {
        if (is_array($productIds) && count($productIds)) {
            $preloadCatalogRuleIds = $this->coreRegistry->registry(Rule::PRELOAD_PRODUCT_IDS_KEY);
            if(!$preloadCatalogRuleIds){
                $preloadCatalogRuleIds = [];
            }
            array_push($preloadCatalogRuleIds, ...array_unique($productIds));
            $this->coreRegistry->unregister(Rule::PRELOAD_PRODUCT_IDS_KEY);
            $this->coreRegistry->register(Rule::PRELOAD_PRODUCT_IDS_KEY, array_unique($preloadCatalogRuleIds));
        }
    }
}
