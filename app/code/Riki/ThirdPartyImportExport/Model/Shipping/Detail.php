<?php
namespace Riki\ThirdPartyImportExport\Model\Shipping;


class Detail extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $_priceCurrency;

    /**
     * @var \Riki\ThirdPartyImportExport\Model\ShippingFactory
     */
    protected $_shippingFactory;

    /**
     * @var \Riki\ThirdPartyImportExport\Model\Order\DetailFactory
     */
    protected $_orderDetailFactory;

    /**
     * Detail constructor.
     * @param \Riki\ThirdPartyImportExport\Model\ShippingFactory $shippingFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Model\Order\DetailFactory $orderDetailFactory,
        \Riki\ThirdPartyImportExport\Model\ShippingFactory $shippingFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_orderDetailFactory = $orderDetailFactory;
        $this->_shippingFactory = $shippingFactory;
        $this->_priceCurrency = $priceCurrency;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('\Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping\Detail');
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

    /**
     * Get total spend for shipping detail
     *
     * @return mixed
     */
    public function getGrandTotal($isHanpukai = false)
    {
        if (!$isHanpukai) {
            return $this->getData('purchasing_amount') * ($this->getData('retail_price') + $this->getData('gift_price'));
        } else {
            return $this->getData('purchasing_amount') * ($this->getData('unit_price') + $this->getData('gift_price'));
        }
    }

    /**
     * Get shipping object
     *
     * @return \Riki\ThirdPartyImportExport\Model\Shipping
     */
    public function getShipping()
    {
        $shipping = $this->_shippingFactory->create();
        $shipping->load($this->getData('shipping_no'));

        return $shipping;
    }

    /**
     * Get order detail object
     * @return \Riki\ThirdPartyImportExport\Model\Order\Detail
     */
    public function getOrderDetail()
    {
        $shipping = $this->getShipping();
        $detail = $this->_orderDetailFactory->create();
        $collection = $detail->getCollection();
        $collection->addFieldToFilter('order_no', $shipping->getData('order_no'));
        $collection->addFieldToFilter('sku_code', $this->getData('sku_code'));
        $result = $collection->getFirstItem();
        if (!$result) {
            return $detail;
        }

        return $result;
    }

    /**
     * Get commodity name
     *
     * @return string
     */
    public function getCommodityName()
    {
        $orderDetail = $this->getOrderDetail();

        return $orderDetail->getCommodityName();
    }

    /**
     * Is ignore when calc and display
     *
     * @return bool
     */
    public function isIgnore()
    {
        if ($this->getData('sku_code') == 'HANPUKAIDISCOUNT') {
            return true;
        }

        return false;
    }

}
