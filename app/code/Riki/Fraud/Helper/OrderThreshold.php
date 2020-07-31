<?php

namespace Riki\Fraud\Helper;

class OrderThreshold extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CEDYNA_THRESHOLD = 'fraud_check/threshold/cedyna_threshold';
    const ORDER_THRESHOLD = 'fraud_check/threshold/order_threshold';
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $_priceHelper;
    /**
     * @var \Riki\Customer\Helper\ShoshaHelper
     */
    protected $_shoshaHelper;

    /**
     * OrderThreshold constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceHelper
     * @param \Riki\Customer\Helper\ShoshaHelper $shoshaHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceHelper,
        \Riki\Customer\Helper\ShoshaHelper $shoshaHelper
    ){
        parent::__construct($context);
        $this->_shoshaHelper = $shoshaHelper;
        $this->_priceHelper = $priceHelper;
    }

    /**
     * @return mixed
     */
    public function getCedynaThreshold()
    {
        return $this->scopeConfig->getValue(self::CEDYNA_THRESHOLD);
    }

    /**
     * @return mixed
     */
    public function getOrderThreshold()
    {
        return $this->scopeConfig->getValue(self::ORDER_THRESHOLD);
    }

    public function isThresholdCart($customerId, $grandTotal)
    {
        $threshold = (float)$this->getOrderThreshold();

        $cedynaCustomer = $this->_shoshaHelper->isCedynaCustomer($customerId);

        if($cedynaCustomer){
            $threshold = (float)$this->getCedynaThreshold();
        }

        if( (float)$grandTotal > $threshold ){
            return $this->thresholdMessage($threshold);
        } else {
            return false;
        }
    }

    /**
     * @param $customerId
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomerById($customerId){
        try {
            return $this->_customerRepository->getById($customerId);
        } catch (\Exception $e){
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    public function thresholdMessage($threshold){
        return __('The total amount of payment must be equal or less than %1.', $this->_priceHelper->format($threshold, false));
    }
}
