<?php
namespace Riki\Fraud\Model\Rule\Condition;

use Magento\Customer\Model\CustomerFactory as CustomerModelFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Rule\Model\Condition\Context;
use Magento\Sales\Model\ResourceModel\Sale\CollectionFactory as SaleCollectionFactory;

class Customer extends \Mirasvit\FraudCheck\Model\Rule\Condition\Customer
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;

    public function __construct(
        CustomerModelFactory $customerFactory,
        SubscriberFactory $subscriberFactory,
        SaleCollectionFactory $saleCollectionFactory,
        ReviewCollectionFactory $reviewCollectionFactory,
        GroupCollectionFactory $groupCollectionFactory,
        Context $context,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        parent::__construct(
            $customerFactory,
            $subscriberFactory,
            $saleCollectionFactory,
            $reviewCollectionFactory,
            $groupCollectionFactory,
            $context
        );

        $this->_moduleManager = $moduleManager;
    }

    /**
     * Add phone_number to customer attribute list
     *
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $attributes = [
            'group_id'         => __('Group'),
            'lifetime_sales'   => __('Lifetime Sales'),
            'number_of_orders' => __('Number of Orders'),
            'is_subscriber'    => __('Is subscriber of newsletter'),
            'reviews_count'    => __('Number of reviews'),
            'phone_number'   => __('Phone number'),
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
    public function validate(\Magento\Framework\Model\AbstractModel $object)
    {
        $reviewsCount = 0;
        $isSubscriber = 0;

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

            if ($this->_moduleManager->isOutputEnabled('Magento_Newsletter')) {

                $subscriber->load($customer->getId(), 'customer_id');

                if ($subscriber->getId()) {
                    $isSubscriber = 1;
                }
            }

            if ($this->_moduleManager->isOutputEnabled('Magento_Review')) {
                $reviewsCount = $this->reviewCollectionFactory->create()
                    ->addCustomerFilter($customer->getId())
                    ->count();
            }

            $customerTotals = $totals->setCustomerIdFilter($customer->getId())
                ->setOrderStateFilter(\Magento\Sales\Model\Order::STATE_CANCELED, true)
                ->load()
                ->getTotals();

            $lifetimeSales = floatval($customerTotals->getData('lifetime'));
            $numberOfOrders = intval($customerTotals->getData('num_orders'));
        } else {

            $email = $object->getData('customer_email');

            if ($this->_moduleManager->isOutputEnabled('Magento_Newsletter')) {
                $subscriber->loadByEmail($email);
                if ($subscriber->getId()) {
                    $isSubscriber = 1;
                }
            }

            $data = ['group_id' => 1];
            $data['email'] = $email;

            $customerTotals = $totals->addFieldToFilter('customer_email', $email)
                ->setOrderStateFilter(\Magento\Sales\Model\Order::STATE_CANCELED, true)
                ->load()
                ->getTotals();

            $lifetimeSales = floatval($customerTotals->getData('lifetime'));
            $numberOfOrders = intval($customerTotals->getData('num_orders'));
        }

        $data['block_orders'] = isset($data['block_orders'])?($data['block_orders']):0;
        $data['b2b_flag'] = isset($data['b2b_flag'])?($data['b2b_flag']):0;
        $data['is_subscriber'] = $isSubscriber;
        $data['reviews_count'] = $reviewsCount;
        $data['lifetime_sales'] = $lifetimeSales;
        $data['number_of_orders'] = $numberOfOrders;

        $value = isset( $data[ $this->getAttribute() ] ) ? $data[$this->getAttribute()] : 0;
        return $this->validateAttribute($value);
    }
}
