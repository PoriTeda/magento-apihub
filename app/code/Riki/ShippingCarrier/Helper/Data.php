<?php
/**
 * Shipping data helper
 */
namespace Riki\ShippingCarrier\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Carriers root xml path
     */
    const XML_PATH_CARRIERS_ROOT = 'carriers/';
    const PARAM_VALUE = '/production_webservices_url' ;

    protected $apiBuildStockPointPostData;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Riki\StockPoint\Model\Api\BuildStockPointPostData $buildStockPointPostData
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Riki\StockPoint\Model\Api\BuildStockPointPostData $buildStockPointPostData
    ) {
        $this->localeResolver = $localeResolver;
        $this->apiBuildStockPointPostData = $buildStockPointPostData;
        parent::__construct($context);
    }
    /**
     * Get shipping carrier config value
     *
     * @param string $codeName
     * @return string
     */
    public function getCarrierConfigValue($codeName)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CARRIERS_ROOT.$codeName.self::PARAM_VALUE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get delivery information of stock point order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array|bool
     */
    public function getStockPointDeliveryOrderInfo(\Magento\Sales\Model\Order $order)
    {
        if ($order->getData('is_stock_point')) {
            $orderNumber = $order->getIncrementId();
            $requestData = ['magento_order_id'=>$orderNumber];
            $responseData = $this->apiBuildStockPointPostData->callApiGetStockPointDeliveryStatus($requestData);
            $responseData['delivery_information'] = $order->getData('stock_point_delivery_information') ?: '';

            return $responseData;
        }
        return false;
    }

    /**
     * Format date as spec
     *
     * @param $date
     * @return false|string
     */
    public function formatStockPointDate($date)
    {
        return date('M d, Y', strtotime($date));
    }
}
