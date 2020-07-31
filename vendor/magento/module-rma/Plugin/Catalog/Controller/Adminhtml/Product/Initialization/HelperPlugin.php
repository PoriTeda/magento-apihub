<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Plugin\Catalog\Controller\Adminhtml\Product\Initialization;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Rma\Model\Product\Source;
use Magento\Rma\Ui\DataProvider\Product\Form\Modifier\Rma;

/**
 * Class HelperPlugin
 */
class HelperPlugin
{
    /**
     * Setting default values according to config settings
     *
     * @param Helper $subject
     * @param ProductInterface $product
     * @param array $productData
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeInitializeFromData(Helper $subject, ProductInterface $product, array $productData): array
    {
        if (isset($productData['use_config_' . Rma::FIELD_IS_RMA_ENABLED])
            && 1 === (int)$productData['use_config_' . Rma::FIELD_IS_RMA_ENABLED]) {
            unset($productData['use_config_' . Rma::FIELD_IS_RMA_ENABLED]);
            $productData[Rma::FIELD_IS_RMA_ENABLED] = Source::ATTRIBUTE_ENABLE_RMA_USE_CONFIG;
        }
        return [$product, $productData];
    }
}
