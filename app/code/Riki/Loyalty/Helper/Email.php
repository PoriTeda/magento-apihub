<?php
namespace Riki\Loyalty\Helper;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XPATH_APPROVAL_NAME  = 'riki_loyalty/point/approval_to_name';
    const XPATH_APPROVAL_EMAIL  = 'riki_loyalty/point/approval_to_email';
    const XPATH_APPROVAL_TEMPLATE  = 'riki_loyalty/point/approval_email_template';

    const XPATH_SERIAL_CODE_TEMPLATE  = 'riki_loyalty/serial_code/template_confirmation';
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Riki\Loyalty\Model\ResourceModel\RewardFactory
     */
    protected $_rewardResourceFactory;

    /**
     * @var \Magento\Backend\Model\Url
     */
    protected $_backendUrl;

    /**
     * Email constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Riki\Loyalty\Model\ResourceModel\RewardFactory $resourceFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\Loyalty\Model\ResourceModel\RewardFactory $resourceFactory,
        \Magento\Backend\Model\Url $backendUrl
    )
    {
        parent::__construct($context);
        $this->_transportBuilder = $transportBuilder;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_storeManager = $storeManager;
        $this->_rewardResourceFactory = $resourceFactory;
        $this->_backendUrl = $backendUrl;
    }

    /**
     * Send mail request approval for pending shopping point
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    public function requestApproval($order)
    {
        try {
            //prepare email parameter
            $template = $this->scopeConfig->getValue(self::XPATH_APPROVAL_TEMPLATE);
            $emailTo = explode(',', $this->scopeConfig->getValue(self::XPATH_APPROVAL_EMAIL));
            $emailToName = $this->scopeConfig->getValue(self::XPATH_APPROVAL_NAME);
            /** @var \Riki\Loyalty\Model\ResourceModel\Reward $rewardResource */
            $rewardResource = $this->_rewardResourceFactory->create();
            $vars = [
                'incrementId' => $order->getIncrementId(),
                'detailUrl' => $this->_backendUrl->getUrl('sales/order/view', ['order_id' => $order->getId()]),
                'pendingPoint' => $rewardResource->pointOrderByStatus(
                    $order->getIncrementId(), \Riki\Loyalty\Model\Reward::STATUS_PENDING_APPROVAL
                )
            ];
            $this->_transportBuilder->addTo($emailTo, $emailToName);
            $this->_transportBuilder
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->_storeManager->getStore()->getId()
                ])->setTemplateIdentifier($template)
                ->setTemplateVars($vars);
            $this->_inlineTranslation->suspend();
            //send message
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * Send mail confirmation apply serial code
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Riki\SerialCode\Model\SerialCode $serialCode
     */
    public function serialCodeConfirmation($customer, $serialCode)
    {
        try {
            // prepare email parameter
            /* controlled by Email Marketing */
            /* Email: Serial code reigistration */
            $template = $this->scopeConfig->getValue(self::XPATH_SERIAL_CODE_TEMPLATE);
            $vars = [
                'customerName' => sprintf(
                    '%s %s(%s %s)様',
                    $customer->getLastname(),
                    $customer->getFirstname(),
                    $customer->getLastnamekana(),
                    $customer->getFirstnamekana()
                ),
                'serialCode' => $serialCode->getData('campaign_id'),
                'pointIssued' => $serialCode->getData('issued_point')
            ];
            $this->_transportBuilder->addTo($customer->getEmail(), $customer->getName());
            $this->_transportBuilder
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->_storeManager->getStore()->getId()
                ])->setTemplateIdentifier($template)
                ->setTemplateVars($vars);
            $this->_inlineTranslation->suspend();
            //send message
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }
}