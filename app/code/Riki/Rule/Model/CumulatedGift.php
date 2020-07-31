<?php

namespace Riki\Rule\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Riki\Customer\Model\AmbCustomerRepository;
use Riki\Rule\Logger\Logger;
use Riki\Customer\Model\CustomerRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Riki\Customer\Model\SsoConfig;

/**
 * Promotion - Cumulative
 * save gift product (3.1.1)
 *
 * @method \Riki\Rule\Model\ResourceModel\CumulatedGift _getResource()
 * @method \Riki\Rule\Model\ResourceModel\CumulatedGift getResource()
 */
class CumulatedGift extends \Magento\Framework\Model\AbstractModel
{
    const TABLE = 'riki_cumulated_gift';

    /**
     * @var AmbCustomerRepository
     */
    protected $abmCustomerRepository;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $helperSalesData;

    /**
     * CumulatedGift constructor.
     * @param AmbCustomerRepository $abmCustomerRepository
     * @param Logger $logger
     * @param CustomerRepository $customerRepository
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Riki\Sales\Helper\Data $helperSalesData
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        AmbCustomerRepository $abmCustomerRepository,
        Logger $logger,
        CustomerRepository $customerRepository,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        CustomerRepositoryInterface $customerRepositoryInterface,
        \Riki\Sales\Helper\Data $helperSalesData,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->abmCustomerRepository = $abmCustomerRepository;
        $this->_logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->helperSalesData = $helperSalesData;
    }

    /**
     * init data
     */
    protected function _construct()
    {
        $this->_init('Riki\Rule\Model\ResourceModel\CumulatedGift');
    }

    /**
     * Check the qty to add to counter
     *
     * @param \Magento\Quote\Model\Quote|\Magento\Sales\Model\Order $obj
     * @return int $counter
     */
    public function getCounterFromCart($obj)
    {
        $counter = 0;
        $items = $obj->getAllItems();
        foreach ($items as $item) {
            if (!$this->helperSalesData->isCumulativeGiftItem($item)) {
                $product = $item->getProduct();
                $qty = $obj instanceof \Magento\Quote\Model\Quote ? $item->getQty() : $item->getQtyOrdered();
                if ($product->getData('filter_part_applicable') && $product->getData('filter_part_number')) {
                    $counter += $product->getData('filter_part_number') * $qty;
                }
            }
        }
        return $counter;
    }

    /**
     * Cancel order - reset the cumulative counter of customer
     * @param \Magento\Sales\Model\Order $order
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function cancel($order)
    {
        $counter = $this->getCounterFromCart($order);
        $customer = $this->customerRepositoryInterface->getById($order->getCustomerId());
        $consumerId = $this->getConsumerID($customer);
        $customerCounter = $this->getCustomerAPICounter($consumerId);
        if ($customerCounter >= $counter) {
            $customerNewCounter = $customerCounter - $counter;
            $this->setCustomerAPICounter($consumerId, $customerNewCounter);
            $this->getResource()->removeCumulativeGiftData($order->getIncrementId());
        } else {
            $this->_logger->info(
                'Error when reset cumulative Counter Customer Sub. Consumer ID: ' .
                $consumerId
            );
        }
    }

    /**
     * Get Point in consumer DB
     *
     * @param string $consumerId
     * @return int
     */
    public function getCustomerAPICounter($consumerId)
    {
        $data = [
            'COUNTER_POINT' => 860
        ];
        try {
            $response = $this->abmCustomerRepository->getCustomerSub($data, $consumerId);
            if (property_exists($response, 'return')) {
                $codeReturn = $response->return;
                if (isset($codeReturn[0]->array[0]) &&
                    SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]->array[0]
                ) {
                    if (isset($codeReturn[4]) && $codeReturn[4]->array[3]) {
                        return $codeReturn[4]->array[3];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_logger->info(
                'Error when call API to get Customer Sub. Consumer ID: ' .
                $consumerId . ' . Details: ' . $e->getMessage()
            );
        }
        return 0;
    }

    /**
     * @param int $consumerId
     * @param mixed $customerNewCounter
     * @return int
     */
    public function setCustomerAPICounter($consumerId, $customerNewCounter)
    {
        $data = [
            860 => $customerNewCounter
        ];
        try {
            $response = $this->customerRepository->setCustomerSubAPI($consumerId, $data);
        } catch (\Exception $e) {
            $this->_logger->info(
                'Error when call API to get Customer Sub. Consumer ID: ' .
                $consumerId . ' . Details: ' . $e->getMessage()
            );
        }
        return 0;
    }

    /**
     * @param mixed $customer
     * @return null
     */
    public function getConsumerID($customer)
    {
        $attribute = $customer->getCustomAttribute('consumer_db_id');
        if ($attribute && $attribute->getValue()) {
            return $attribute->getValue();
        }
        return null;
    }

    /**
     * @param int $consumerId
     * @param int $availableQty
     * @param int $qtyAddToCart
     * @return int
     */
    public function batchToAttach($consumerId, $availableQty, $qtyAddToCart)
    {
        $qtyNotAttached = $this->countNotAttachByConsumer($consumerId);
        $qtyCanAdd = $availableQty - $qtyAddToCart;
        if ($qtyCanAdd > 0) {
            if ($qtyCanAdd < $qtyNotAttached) {
                $qty = $qtyCanAdd;
            } else {
                $qty = $qtyNotAttached;
            }
            return $qty;
        }
        return 0;
    }

    /**
     * @param int $consumerDbId
     * @return int
     */
    public function countNotAttachByConsumer($consumerDbId)
    {
        $notAttachedIds = $this->getResource()->getNotAttachedIds($consumerDbId);

        return count($notAttachedIds);
    }
}
