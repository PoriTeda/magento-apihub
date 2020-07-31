<?php

namespace Riki\Sales\Block\Adminhtml\Order\Create;

class AdditionalInfo extends \Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate
{
    const IS_ACTIVE = 1;

    protected $_orderChannel;

    protected $_orderType;

    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    protected $shippingCauseFactory;

    protected $shippingReasonFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Riki\Sales\Model\Config\Source\OrderChannel $channel,
        \Riki\Sales\Model\Config\Source\OrderType $orderType,
        \Riki\Sales\Model\ResourceModel\ShippingCause\CollectionFactory $shippingCauseFactory,
        \Riki\Sales\Model\ResourceModel\ShippingReason\CollectionFactory $shippingReasonFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $data
        );

        $this->_orderChannel = $channel;
        $this->_orderType = $orderType;
        $this->shippingCauseFactory = $shippingCauseFactory;
        $this->shippingReasonFactory = $shippingReasonFactory;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Serialize\Serializer\Json::class
        );
    }

    /**
     * @return string
     */
    public function getChangeOrderTypeUrl()
    {
        return $this->getUrl('riki_sales/*/changeType');
    }

    /**
     * @return string
     */
    public function getEarnOrderTypeUrl()
    {
        return $this->getUrl('riki_sales/*/earnedPoint');
    }

    /**
     * @return mixed
     */
    public function getSelectedType()
    {
        $type = $this->_sessionQuote->getChargeType();

        if (!$type)
            $type = $this->getQuoteData('charge_type');
        if (!$type)
            $type = \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_NORMAL;

        return $type;
    }

    /**
     * @return array
     */
    public function getChannelOptions()
    {
        $arrOption = $this->_orderChannel->toArray();

        /**
         * Does not show option machine_maintenance,online for create order BO
         */
        $hideOption = [
            \Riki\Sales\Model\Config\Source\OrderChannel::ORDER_CHANEL_TYPE_ONLINE,
            \Riki\Sales\Model\Config\Source\OrderChannel::ORDER_CHANEL_TYPE_MACHINE_API,
        ];
        foreach ($hideOption as $option) {
            if (isset($arrOption[$option])) {
                unset($arrOption[$option]);
            }
        }

        return $arrOption;
    }

    /**
     * @return array
     */
    public function getTypeOptions()
    {
        return $this->_orderType->toArray();
    }

    /**
     * @return mixed
     */
    public function getOriginalOrderId()
    {
        $result = $this->_sessionQuote->getOriginalOrderId();

        if (!$result)
            $result = $this->getQuoteData('original_order_id');

        return $result;
    }

    /**
     * get reason list from config
     *
     * @return array
     */
    public function getReplacementOrderReasonOptions()
    {
        $result = [];
        $optionsConfig = $this->_scopeConfig->getValue('riki_order/replacement_order/reason');

        if ($optionsConfig) {
            $options = $this->serializer->unserialize($optionsConfig);

            if (is_array($options)) {
                foreach ($options as $option) {
                    $result[$option['code'] . ' - ' . $option['title']] = $option['code'] . ' - ' . $option['title'];
                }
            }
        }

        return $result;
    }

    public function getFreeSampleOrderReasonOptions()
    {
        $orderReasons = $this->shippingReasonFactory->create()->load()
            ->addFieldToFilter('is_active',self::IS_ACTIVE)->getData();
        $result = [];
        if (count($orderReasons) > 0) {
            foreach ($orderReasons as $orderReason) {
                $result[$orderReason['description']] = $orderReason['description'];
            }
        }

        return $result;
    }

    public function getFreeSampleOrderCauseOptions()
    {
        $orderCauses = $this->shippingCauseFactory->create()->load()
            ->addFieldToFilter('is_active',self::IS_ACTIVE)->getData();
        $result = [];
        if (count($orderCauses) > 0) {
            foreach ($orderCauses as $orderCause) {
                $result[$orderCause['description']] = $orderCause['description'];
            }
        }

        return $result;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getQuoteData($key)
    {
        return $this->getQuote()->getData($key);
    }

    /**
     * Check earn poin BO order
     *
     * @return int
     */
    public function getEarnPointChecked()
    {
        if ($this->_sessionQuote->getAllowedEarnedPoint()) {
            return 1;
        }
        return 0;
    }
}
