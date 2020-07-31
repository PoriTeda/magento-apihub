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

use Magento\Customer\Model\CustomerFactory as CustomerModelFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Rule\Model\Condition\Context;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\ResourceModel\Sale\CollectionFactory as SaleCollectionFactory;

/**
 * @method string getAttribute()
 * @method $this setAttributeOption($attributes)
 * @method array getAttributeOption()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Customer extends AbstractCondition
{
    /**
     * @var CustomerModelFactory
     */
    protected $customerFactory;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var SaleCollectionFactory
     */
    protected $saleCollectionFactory;

    /**
     * @var ReviewCollectionFactory
     */
    protected $reviewCollectionFactory;

    /**
     * @var GroupCollectionFactory
     */
    protected $groupCollectionFactory;

    public function __construct(
        CustomerModelFactory $customerFactory,
        SubscriberFactory $subscriberFactory,
        SaleCollectionFactory $saleCollectionFactory,
        ReviewCollectionFactory $reviewCollectionFactory,
        GroupCollectionFactory $groupCollectionFactory,
        Context $context
    ) {
        $this->customerFactory = $customerFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->saleCollectionFactory = $saleCollectionFactory;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->groupCollectionFactory = $groupCollectionFactory;

        return parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function loadAttributeOptions()
    {
        $attributes = [
            'group_id'         => __('Group'),
            'lifetime_sales'   => __('Lifetime Sales'),
            'number_of_orders' => __('Number of Orders'),
            'is_subscriber'    => __('Is subscriber of newsletter'),
            'reviews_count'    => __('Number of reviews'),
        ];

        $customerAttributes = $this->customerFactory->create()->getAttributes();
        foreach ($customerAttributes as $attr) {
            if ($attr->getStoreLabel() && $attr->getAttributeCode()) {
                $attributes[$attr->getAttributeCode()] = $attr->getStoreLabel();
            }
        }

        $this->setAttributeOption($attributes);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType()
    {
        $type = 'string';

        switch ($this->getAttribute()) {
            case 'group_id':
                $type = 'multiselect';
                break;

            case 'is_subscriber':
                $type = 'select';
                break;
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueElementType()
    {
        $type = 'text';

        switch ($this->getAttribute()) {
            case 'group_id':
                $type = 'multiselect';
                break;

            case 'is_subscriber':
                $type = 'select';
                break;
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueOption()
    {
        $options = [];

        if ($this->getAttribute() === 'is_subscriber') {
            $options = [
                0 => __('No'),
                1 => __('Yes'),
            ];
        } elseif ($this->getAttribute() === 'group_id') {
            $options = $this->groupCollectionFactory->create()->toOptionHash();
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(AbstractModel $object)
    {
        $reviewsCount = 0;

        $totals = $this->saleCollectionFactory->create();
        $subscriber = $this->subscriberFactory->create();
        $customer = $this->customerFactory->create()
            ->setWebsiteId(1);

        if ($customerId = $object->getData('customer_id')) {
            $customer->load($customerId);
        } else {
            $customer->loadByEmail($object->getData('customer_email'));
        }

        if ($customer->getId()) {
            $data = $customer->getData();
            $subscriber->load($customer->getId(), 'customer_id');

            $reviewsCount = $this->reviewCollectionFactory->create()
                ->addCustomerFilter($customer->getId())
                ->count();

            $customerTotals = $totals->setCustomerIdFilter($customer->getId())
                ->setOrderStateFilter(SalesOrder::STATE_CANCELED, true)
                ->load()
                ->getTotals();

            $lifetimeSales = floatval($customerTotals->getData('lifetime'));
            $numberOfOrders = intval($customerTotals->getData('num_orders'));
        } else {
            $email = $object->getData('customer_email');
            $subscriber->loadByEmail($email);
            $data = ['group_id' => 1];

            $customerTotals = $totals->addFieldToFilter('customer_email', $email)
                ->setOrderStateFilter(SalesOrder::STATE_CANCELED, true)
                ->load()
                ->getTotals();

            $lifetimeSales = floatval($customerTotals->getData('lifetime'));
            $numberOfOrders = intval($customerTotals->getData('num_orders'));
        }

        $object->addData($data)
            ->setData('is_subscriber', $subscriber->getId() ? 1 : 0)
            ->setData('reviews_count', $reviewsCount)
            ->setData('lifetime_sales', $lifetimeSales)
            ->setData('number_of_orders', $numberOfOrders)
            ->setData('email', $object->getData('customer_email'));

        $value = $object->getData($this->getAttribute());

        return $this->validateAttribute($value);
    }
}
