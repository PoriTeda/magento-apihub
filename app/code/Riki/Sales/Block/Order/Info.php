<?php
namespace Riki\Sales\Block\Order ;
use \Riki\NpAtobarai\Model\Config\Source\TransactionStatus;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Store\Model\ScopeInterface;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;

class Info extends \Magento\Sales\Block\Order\Info
{
    /**
     * @var \Riki\Shipment\Helper\ShipmentHistory
     */
    protected $shipmentHistory;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $config;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $currency;

    /**
     * @var \Riki\NpAtobarai\Model\Transaction\Config
     */
    protected $transactionManagement;

    /**
     * @var \Riki\NpAtobarai\Model\Transaction\Config
     */
    protected $transactionConfig;

    /**
     * @var \Riki\Sales\Model\Order\OrderAdditionalInformationFactory
     */
    protected $orderAdditionalInformationFactory;

    /**
     * Info constructor.
     * @param TemplateContext $context
     * @param Registry $registry
     * @param PaymentHelper $paymentHelper
     * @param AddressRenderer $addressRenderer
     * @param \Riki\Shipment\Helper\ShipmentHistory $shipmentHistory
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Riki\NpAtobarai\Model\TransactionManagement $transactionManagement
     * @param \Riki\NpAtobarai\Model\Transaction\Config $transactionConfig
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        PaymentHelper $paymentHelper,
        AddressRenderer $addressRenderer,
        \Riki\Shipment\Helper\ShipmentHistory $shipmentHistory,
        \Riki\Sales\Helper\Order $orderHelper,
        \Magento\Directory\Model\Currency $currency,
        \Riki\NpAtobarai\Model\TransactionManagement $transactionManagement,
        \Riki\NpAtobarai\Model\Transaction\Config $transactionConfig,
        \Riki\Sales\Model\Order\OrderAdditionalInformationFactory $orderAdditionalInformationFactory,
        array $data = []
    ) {
        $this->shipmentHistory = $shipmentHistory;
        $this->orderHelper = $orderHelper;
        $this->config = $context->getScopeConfig();
        $this->currency = $currency;
        $this->transactionManagement = $transactionManagement;
        $this->transactionConfig = $transactionConfig;
        $this->orderAdditionalInformationFactory = $orderAdditionalInformationFactory;
        parent::__construct($context, $registry, $paymentHelper, $addressRenderer, $data);
    }

    /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Order Detail ( Order Number: # %1)', $this->getOrder()->getRealOrderId()));
        $infoBlock = $this->paymentHelper->getInfoBlock($this->getOrder()->getPayment(), $this->getLayout());
        $this->setChild('payment_info', $infoBlock);
    }

    public function getHistoryShipment($shipment)
    {
        return $this->shipmentHistory->getShipmentDateHistory($shipment);

    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getReceiptName()
    {
        $receiptNumber = $this->_request->getParam('print-order-name', 1);
        return $this->orderHelper->getReceiptName($this->getOrder(), $receiptNumber);
    }

    /**
     * @return string
     */
    public function getShippedOutDate()
    {
        return $this->orderHelper->getOrderShippedOutDate($this->getOrder());
    }

    /**
     * @return mixed
     */
    public function getPaymentTitle()
    {
        $paymentMethod = $this->getOrder()->getPayment()->getMethod();
        $configPath = 'payment/'.$paymentMethod.'/title';
        return $this->config->getValue($configPath, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getCurrencySymbol()
    {
        return $this->currency->getCurrencySymbol();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return float
     */
    public function getRowsTotal(\Magento\Sales\Model\Order $order)
    {
        return $this->orderHelper->getRowsTotal($order);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getNpTransactionsPendingReasons()
    {
        $reasonMessage = [];
        if ($this->getOrder() &&
            $this->getOrder()->getPayment() &&
            $this->getOrder()->getPayment()->getMethod() == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ) {
            $transactions = $this->transactionManagement->getOrderTransactions($this->getOrder());
            foreach ($transactions as $transaction) {
                $pendingReasonCode = $transaction->getAuthorizePendingReasonCodes();
                if($pendingReasonCode !== null) {
                    $pendingReasons = explode(',', $pendingReasonCode);
                    foreach ($pendingReasons as $reasonCode) {
                        $message = $this->transactionConfig->getPendingReasonMessage($reasonCode);
                        if ($message) {
                            $reasonMessage[] = $message;
                        }
                    }
                }
            }
        }
        return $reasonMessage;
    }

    public function getOrderAdditionalInformation()
    {
        $orderAdditionalInformation = $this->orderAdditionalInformationFactory->create()
            ->load($this->getOrder()->getId(),'order_id');

        return $orderAdditionalInformation;
    }
}
