<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class ItemsDataBuilder
 */
class ItemsDataBuilder implements BuilderInterface
{
    /**
     * Items block name
     */
    const ITEMS = 'Items';

    /**
     * The stock keeping unit used to identify this line item
     */
    const SKU = 'SKU';

    /**
     * A brief description of the product
     */
    const DESCRIPTION = 'Description';

    /**
     * The purchased quantity
     */
    const QUANTITY = 'Quantity';

    /**
     * The pre-tax cost per unit of the product in the lowest denomination
     */
    const UNIT_COST = 'UnitCost';

    /**
     * The tax amount that applies to this line item in the lowest denomination
     */
    const TAX = 'Tax';

    /**
     * The total amount charged for this line item in the lowest denomination
     */
    const TOTAL = 'Total';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        return [
            self::ITEMS => $this->prepareItems($order->getItems())
        ];
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface[]|null $items
     * @return array
     */
    private function prepareItems($items)
    {
        $result = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($items as $item) {
            $result[] = [
                self::SKU => $item->getSku(),
                self::DESCRIPTION => $item->getDescription(),
                self::QUANTITY => $item->getQtyOrdered(),
                self::UNIT_COST => $item->getBasePrice(),
                self::TAX => $item->getBaseTaxAmount(),
                self::TOTAL => $item->getBaseRowTotalInclTax()
            ];
        }

        return $result;
    }
}
