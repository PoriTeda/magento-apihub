<?php
namespace Bluecom\Paygent\Block\Checkout;

class Review extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $storeCode;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $orderModel;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * Review constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context       Context
     * @param \Magento\Framework\Registry                      $registry      Registry
     * @param \Magento\Payment\Helper\Data                     $paymentHelper Data
     * @param \Magento\Sales\Model\Order                       $order         Order
     * @param array                                            $data          Data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\Order $order,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->paymentHelper = $paymentHelper;
        $this->storeCode = $this->_storeManager->getStore()->getCode();
        $this->orderModel = $order;
    }

    /**
     * Get store config
     *
     * @param string $path Path
     *
     * @return mixed
     */
    public function getConfigValue($path)
    {
        return $this->_scopeConfig->getValue($path, 'store', $this->storeCode);
    }

    /**
     * Prepare Layout
     *
     * @return $this
     * 
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Order Review # %1', $this->getOrder()->getRealOrderId()));
        $infoBlock = $this->paymentHelper->getInfoBlock($this->getOrder()->getPayment(), $this->getLayout());
        $this->setChild('payment_info', $infoBlock);
        return $this;
    }


    /**
     * Retrieve current order model instance
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    /**
     * Get order Increment Id
     *
     * @return mixed
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    /**
     * Return confirm url
     *
     * @return string
     */
    public function getConfirmUrl()
    {
        return $this->getUrl('*/*/confirm', ['orderId' => $this->getOrderIncrementId()]);
    }


}
