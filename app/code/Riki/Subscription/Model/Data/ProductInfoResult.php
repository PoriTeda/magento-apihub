<?php

namespace Riki\Subscription\Model\Data;

use Magento\Framework\DataObject;
use Nestle\Gillette\Api\Data\CartEstimationResultInterface;
use phpDocumentor\Reflection\Types\This;
use Riki\Subscription\Api\Data\ProductInfoResultInterface;

/**
 * Class ProductInformationResult
 * @package Riki\Subscription\Model\Data
 */
Class ProductInfoResult extends DataObject  implements ProductInfoResultInterface {


    /**
     * {@inheritDoc}
     */
    public function setProductInformation($productInformation)
    {
        return $this->setData(self::PRODUCT_INFORMATION, $productInformation);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductInformation()
    {
        return $this->getData(self::PRODUCT_INFORMATION);
    }

}
