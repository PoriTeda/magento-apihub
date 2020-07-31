<?php
namespace Riki\ThirdPartyImportExport\Model;

class Order extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var int|number
     */
    protected $_totalAmountProduct;

    /**
     * @var int|number
     */
    protected $_grandAmount;

    /**
     * @var \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\Detail\Collection
     */
    protected $_items;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var ResourceModel\Shipping\CollectionFactory
     */
    protected $_shippingCollectionFactory;

    /**
     * @var ResourceModel\Order\Detail\CollectionFactory
     */
    protected $_detailCollectionFactory;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $_priceCurrency;


    /**
     * Order constructor.
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param ResourceModel\Order\Detail\CollectionFactory $detailCollectionFactory
     * @param ResourceModel\Shipping\CollectionFactory $shippingCollectionFactory
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\Detail\CollectionFactory $detailCollectionFactory,
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping\CollectionFactory $shippingCollectionFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_priceCurrency = $priceCurrency;
        $this->_detailCollectionFactory = $detailCollectionFactory;
        $this->_shippingCollectionFactory = $shippingCollectionFactory;
        $this->_urlBuilder = $urlBuilder;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize
     */
    protected function _construct()
    {
        $this->_init('Riki\ThirdPartyImportExport\Model\ResourceModel\Order');
    }

    /**
     * Get view url
     * @return string
     */
    public function getViewUrl()
    {
        return $this->_urlBuilder->getUrl('thirdpartyimportexport/order/view', [
            'id' => $this->getId()
        ]);
    }

    /**
     * Get shipping
     *
     * @return \Riki\ThirdPartyImportExport\Model\Shipping
     */
    public function getShipping()
    {
        $collection = $this->_shippingCollectionFactory->create();
        $collection->addFieldToFilter('order_no', $this->getId())
            ->addFieldToFilter('return_item_type', ['neq' => 1]);
        $shipping = $collection->getFirstItem();

        return $shipping;
    }


    /**
     * Get multi shipping
     *
     * @return \Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping\Collection
     */
    public function getShippings()
    {
        $collection = $this->_shippingCollectionFactory->create();
        $collection->addFieldToFilter('order_no', $this->getId())
            ->addFieldToFilter('return_item_type', ['neq' => 1]);

        return $collection;
    }

    /**
     * Shipping fee
     *
     * @return mixed
     */
    public function getShippingCharge()
    {
        $sum = 0;
        $shippings = $this->getShippings();
        foreach ($shippings as $shipping) {
            $sum += $shipping->getShippingCharge();
        }

        return $sum;
    }



    /**
     * Get wrapping fee
     */
    public function getWrappingFee()
    {
        $sum = 0;
        $shippings = $this->getShippings();
        foreach ($shippings as $shipping) {
            $sum += $shipping->getWrappingFee();
        }

        return $sum;
    }

    /**
     * Get detail collection
     *
     * @return mixed
     */
    public function getItems()
    {
        if ($this->_items) {
            return $this->_items;
        }

        $this->_items = $this->_detailCollectionFactory->create();
        if ($this->getPlanType() != 1) {
            $this->_items->addFieldToFilter('order_no', $this->getId())
                ->addFieldToFilter('sku_code', ['neq' => 'HANPUKAIDISCOUNT']);
        } else {
            $this->_items->addFieldToFilter('order_no', $this->getId());
        }

        return $this->_items;
    }

    /**
     * Get label for status
     *
     * @return \Magento\Framework\Phrase
     */
    public function getStatusLabel()
    {
        $status = $this->getOrderStatus();
        if ($status == 0) {
            $status =  __('Pre Order');
        } elseif ($status == 1) {
            $status =  __('Ordinary');
        } elseif ($status == 2) {
            $status =  __('Canceled');
        }

        return $status;
    }

    /**
     * Format price with currency
     *
     * @param $price
     * @return float
     */
    public function formatPrice($price)
    {
        return $this->_priceCurrency->format($price);
    }

    public function formatPriceForBackend($price)
    {
        return $this->_priceCurrency->format($price, false);
    }

    /**
     * Get subscription type label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getSubscriptionTypeLabel()
    {
        $type = $this->getData('plan_type');
        if (is_null($type) || !strlen($type)) {
            return __('SPOT');
        } elseif ($type == 0) {
            return __('Subscription');
        } elseif ($type == 1) {
            return __('Hanpukai');
        }
    }

    /**
     * Get shipping status
     *
     * @return mixed|string
     */
    public function getShippingStatus()
    {
        $statuses = [];
        $shippings = $this->getShippings();

        foreach ($shippings as $shipping) {
            $statuses[] = $shipping->getData('shipping_status');
        }

        if (empty($statuses)) {
            return 0;
        }

        return min($statuses);
    }

    /**
     * Get shipping status label
     *
     * @return \Magento\Framework\Phrase|mixed|string
     */
    public function getShippingStatusLabel()
    {
        $status = $this->getShippingStatus();
        switch ($status) {
            case 0:
                return __('Not Ready');

            case 1:
                return __('Ready');

            case 2:
                return __('In Processing');

            case 3:
                return __('Shipped');

            case 4:
                return __('Canceled');

            default:
                return $status;
        }
    }

    /**
     * Get total money spend of all product in order
     *
     * @return int|number
     */
    public function getTotalAmountProduct()
    {
        if ($this->_totalAmountProduct) {
            return $this->_totalAmountProduct;
        }

        $this->_grandAmount = 0;

        $items = $this->getItems();
        foreach ($items as $item) {
            if ($item->isIgnore()) {
                continue;
            }
            if ($this->getData('plan_type') == 0) {
                $this->_totalAmountProduct += ($item->getData('purchasing_amount') * $item->getData('retail_price'));
            } else {
                $this->_totalAmountProduct += ($item->getData('purchasing_amount') * $item->getData('unit_price'));
            }
        }


        return $this->_totalAmountProduct;
    }

    /**
     * Get total money spend of order
     *
     * @return int|number
     */
    public function getGrandTotal()
    {
        if ($this->_grandAmount) {
            return $this->_grandAmount;
        }

        $this->_grandAmount = 0;
        $this->_grandAmount += $this->getTotalAmountProduct();
        $this->_grandAmount += $this->getWrappingFee();
        $this->_grandAmount += $this->getShippingCharge();
        $this->_grandAmount += $this->getPaymentCommission();
        $this->_grandAmount -= $this->getUsedPoint();

        $this->_grandAmount = max(0, $this->_grandAmount);

        return $this->_grandAmount;
    }

    /**
     * @return $this
     */
    public function resetGrandTotal()
    {
        $this->_grandAmount = 0;
        return $this;
    }

    /**
     * Get billing name
     *
     * @return string
     */
    public function getBillingName()
    {
        return $this->getData('last_name') . ' ' . $this->getData('first_name') . ' 様'; // @codingStandardsIgnoreLine
    }

    /**
     * Get billing address
     *
     * @return string
     */
    public function getBillingAddress()
    {
        $address = $this->getData('postal_code');
        $address = '〒 ' . substr($address, 0, 3) . '-' . substr($address, 3); // format zip code xxx-xxxx // @codingStandardsIgnoreLine

        $address = trim($address, ", ") . ', ' . $this->getData('address1');
        $address = trim($address, ", ") . ', ' . $this->getData('address2');
        $address = trim($address, ", ") . ', ' . $this->getData('address3');
        $address = trim($address, ", ") . ', ' . $this->getData('address4');

        return trim($address, ", ");
    }

    public function getTotalAquiredPoint()
    {
        $sum = 0;
        $shippingCollection = $this->getShippings();
        foreach ($shippingCollection as $shipping) {
            $sum += $shipping->getAcquiredPoint();
        }
        return $sum;
    }
}
