<?php

namespace Bluecom\PaymentFee\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Totals extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Json factory
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJson;

    /**
     * Helper data
     *
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_helper;

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Riki\Subscription\Model\Promotion\Registry
     */
    protected $promoRegistry;

    /**
     * Init
     *
     * @param Context                                          $context         context
     * @param Session                                          $checkoutSession session
     * @param \Magento\Framework\Json\Helper\Data              $helper          helper
     * @param \Bluecom\PaymentFee\Logger\Logger                $logger          logger
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJson      result json
     */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Json\Helper\Data $helper,
        \Bluecom\PaymentFee\Logger\Logger $logger,
        \Magento\Framework\Controller\Result\JsonFactory $resultJson,
        \Riki\Subscription\Model\Promotion\Registry $promoRegistry
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->_resultJson = $resultJson;
        $this->promoRegistry = $promoRegistry;
    }

    /**
     * Trigger to re-calculate the collect Totals
     *
     * @return bool
     */
    public function execute()
    {
        $response = [
            'errors' => false,
            'message' => 'Re-calculate successful.'
        ];
        try {
            //Trigger to re-calculate totals
            $payment = $this->_helper->jsonDecode($this->getRequest()->getContent());
            if (isset($payment['payment'])) {
                $this->_checkoutSession->getQuote()->getPayment()->setMethod($payment['payment']);
                $this->_checkoutSession->getQuote()->setTotalsCollectedFlag(false);
                $this->_checkoutSession->getQuote()->collectTotals()->save();

                $this->_checkoutSession->getQuote()->getShippingAddress()->setCollectShippingRates(true);
                $this->_checkoutSession->getQuote()->getShippingAddress()->collectShippingRates();
            }
            $this->promoRegistry->resetHandle();
            $this->_checkoutSession->getQuote()->setTotalsCollectedFlag(false);
            $this->_checkoutSession->getQuote()->collectTotals()->save();

        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'message' => $e->getMessage()
            ];
        }

        /**
         * Result json
         *
         * @var \Magento\Framework\Controller\Result\Raw $resultRaw result rav
         */
        $resultJson = $this->_resultJson->create();
        return $resultJson->setData($response);
    }
}