<?php
namespace Riki\Sales\Block\Adminhtml\Order\View;

class StockPointInfo extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * @var \Riki\StockPoint\Model\Api\BuildStockPointPostData
     */
    protected $apiBuildStockPointPostData;
    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * StockPointInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Riki\StockPoint\Model\Api\BuildStockPointPostData $buildStockPointPostData
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Riki\StockPoint\Model\Api\BuildStockPointPostData $buildStockPointPostData,
        \Riki\Sales\Helper\Order $orderHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $data
        );
        $this->apiBuildStockPointPostData = $buildStockPointPostData;
        $this->orderHelper = $orderHelper;
    }
    /**
     * Get delivery information of stock point order
     *
     * @return bool|array
     */
    public function getStockPointDeliveryOrderInfo()
    {
        $order = $this->getOrder();
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
