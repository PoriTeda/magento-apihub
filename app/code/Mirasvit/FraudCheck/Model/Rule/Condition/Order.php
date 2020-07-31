<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\FraudCheck\Model\Rule\Condition;

use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * @method string getAttribute()
 * @method $this setAttributeOption($attributes)
 * @method array getAttributeOption()
 */
class Order extends AbstractCondition
{
    private $orderCollectionFactory;

    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        Context $context,
        array $data = []
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;

        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $attributes = [
            'discount_amount'   => __('Discount Amount'),
            'subtotal'          => __('Subtotal'),
            'grand_total'       => __('Grand Total'),
            'shipping_amount'   => __('Shipping Amount'),
            'tax_amount'        => __('Tax Amount'),
            'remote_ip'         => __('Placed from IP'),
            'total_item_count'  => __('Items Count'),
            'total_qty_ordered' => __('Items Quantity'),
            'is_new_ip'         => __('Is new IP'),
        ];

        asort($attributes);

        $this->setAttributeOption($attributes);

        return $this;
    }

    public function getInputType()
    {
        if ($this->getAttribute() === 'is_new_ip') {
            return 'boolean';
        }

        return parent::getInputType();
    }

    public function getValueOption()
    {
        $options = [];

        if ($this->getAttribute() === 'is_new_ip') {
            $options = [
                0 => __('No'),
                1 => __('Yes'),
            ];
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(AbstractModel $object)
    {
        if ($this->getAttribute() === 'is_new_ip') {
            $size = $this->orderCollectionFactory->create()
                ->addFieldToFilter('remote_ip', $object->getData('remote_ip'))
                ->addFieldToFilter('entity_id', ['neq' => intval($object->getId())])
                ->addFieldToFilter('customer_id', ['eq' => intval($object->getCustomerId())])
                ->setOrder('created_at', 'asc')
                ->getSize();

            $value = $size === 0 ? 1 : 0;

            return $this->validateAttribute($value);
        }

        return parent::validate($object);
    }
}
