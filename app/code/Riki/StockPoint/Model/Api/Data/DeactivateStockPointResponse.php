<?php

namespace Riki\StockPoint\Model\Api\Data;

use Magento\Framework\DataObject;
use \Riki\StockPoint\Api\Data\DeactivateStockPointResponseInterface;

class DeactivateStockPointResponse extends DataObject implements DeactivateStockPointResponseInterface
{
    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        return $this->getData('result');
    }

    /**
     * {@inheritdoc}
     */
    public function setResult($result)
    {
        return $this->setData('result', $result);
    }
}
