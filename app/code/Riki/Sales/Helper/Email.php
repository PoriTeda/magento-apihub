<?php
namespace Riki\Sales\Helper;
/* Use exception namespace */
use Magento\Framework\DataObject;
use Magento\Framework\Exception\MailException;
use Magento\Setup\Fixtures\ImagesFixture;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslate;
    /**
     * @var \Riki\EmailMarketing\Helper\Data
     */
    protected $emailDataHelper;
    /**
     * @var \Riki\EmailMarketing\Helper\Order
     */
    protected $orderDataHelper;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory 
     */
    protected $profileFactory;
    /**
     * @var \Riki\SubscriptionFrequency\Model\FrequencyFactory
     */
    protected $_frequencyFactory;

    protected $orderData ;
    /**
     * Email constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Data $data
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslate
     * @param \Riki\EmailMarketing\Helper\Data $emailDataHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\Sales\Helper\Data $data,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslate,
        \Riki\EmailMarketing\Helper\Data $emailDataHelper,
        \Riki\EmailMarketing\Helper\Order $orderDataHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory
    )
    {
        $this->dataHelper = $data;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslate = $inlineTranslate;
        $this->emailDataHelper = $emailDataHelper;
        $this->orderDataHelper = $orderDataHelper;
        $this->orderFactory = $orderFactory;
        $this->profileFactory = $profileFactory;
        $this->_frequencyFactory = $frequencyFactory;
        parent::__construct($context);
    }

    /**
     * Send order cancel mail
     *
     * @param $emailTemplateVariables
     * @param $emailReceiver
     * @param bool $toAdmin
     */
    public function sendMailCancelOrder($emailTemplateVariables, $emailReceiver, $toAdmin = false)
    {
        $this->inlineTranslate->suspend();
        $senderInfo = $this->dataHelper->getSenderEmail();
        if ($toAdmin) {
            $this->transportBuilder->setTemplateIdentifier($this->dataHelper->getTemplateEmailCVSAdmin());
        } else {
            if(in_array($emailTemplateVariables['order_type'],array("HANPUKAI","SUBSCRIPTION"))
                || $emailTemplateVariables['subscription_profile_id'])
            {
                $this->transportBuilder->setTemplateIdentifier($this->dataHelper->getTemplateEmailCancelSubscription());
            }
            else
            {
                $this->transportBuilder->setTemplateIdentifier($this->dataHelper->getTemplateEmail());
            }

        }
        $this->transportBuilder->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->dataHelper->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($emailReceiver);

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslate->resume();
    }

    /**
     * @param string $shippingDescription
     * @param string $emailReceiver
     * @param array $vars
     * @throws MailException
     */
    public function sendMailCancelFraudOrder($shippingDescription, $emailReceiver, $vars = [])
    {
        $this->inlineTranslate->suspend();
        if ($shippingDescription != Order::FRAUD_LOGIC_CODE && $shippingDescription != Order::FRAUD_SEGMENT_CODE)
        {
            return;
        }
        if (!$this->dataHelper->isFraudLogicEnable() && !$this->dataHelper->isFraudSegmentEnable())
        {
            return;
        }
        $senderInfo = $this->dataHelper->getFraudEmailSender($shippingDescription);
        if ($shippingDescription == Order::FRAUD_LOGIC_CODE && $this->dataHelper->isFraudLogicEnable()){
            $this->transportBuilder->setTemplateIdentifier($this->dataHelper->getFraudLogicTemplateEmail());
            $this->sendMailMethod($vars, $senderInfo, $emailReceiver);
            return;
        }
        if ($shippingDescription == Order::FRAUD_SEGMENT_CODE && $this->dataHelper->isFraudSegmentEnable()){
            $this->transportBuilder->setTemplateIdentifier($this->dataHelper->getFraudSegmentTemplateEmail());
            $this->sendMailMethod($vars, $senderInfo, $emailReceiver);
            return;
        }
    }

    /**
     * @param array $vars
     * @param array $senderInfo
     * @param string $emailReceiver
     * @throws MailException
     */
    public function sendMailMethod($vars, $senderInfo, $emailReceiver)
    {
        $this->transportBuilder->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_ADMINHTML,
                'store' => $this->dataHelper->getStore()->getId(),
            ]
        )
            ->setTemplateVars($vars)
            ->setFrom($senderInfo)
            ->addTo($emailReceiver);

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslate->resume();
    }

    /**
     * Send cancel mail for pre order
     *
     * @param $emailTemplateVariables
     * @param $emailReceiver
     */
    public function sendMailCancelPreOrder($emailTemplateVariables, $emailReceiver)
    {
        $this->inlineTranslate->suspend();
        $senderInfo = $this->dataHelper->getSenderEmail();

        $this->transportBuilder->setTemplateIdentifier($this->dataHelper->getCancelPreorderEmailTemplate());

        $this->transportBuilder->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->dataHelper->getStore()->getId(),
            ]
        )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($emailReceiver);
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslate->resume();
    }

    /**
     * Send cancel mail for pre order
     *
     * @param $emailTemplateVariables
     * @param $emailReceiver
     */
    public function sendMailConfirmationPreOrder($emailTemplateVariables, $emailReceiver)
    {
        $this->inlineTranslate->suspend();
        $senderInfo = $this->dataHelper->getSenderEmail();

        $this->transportBuilder->setTemplateIdentifier($this->dataHelper->getConfirmationPreorderEmailTemplate());

        $this->transportBuilder->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->dataHelper->getStore()->getId(),
            ]
        )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($emailReceiver);
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslate->resume();
    }

    public function getOrderData()
    {
        return $this->orderData;
    }

    /**
     * Send email for order change
     *
     * @param $orderId
     * @param null $emailTemplateId
     */
    public function sendMailOrderChange($orderId,$emailTemplateId=null)
    {
        $order = $this->orderFactory->create()->load($orderId);
        if($order->getId()) {

            $this->orderData = $order;

            if($emailTemplateId ==null){
                 if ($order->getSubscriptionProfileId() !=null){
                     $emailTemplateId = 'order_change_subscription';
                 }
            }

            $emailTemplateVariables = $this->orderDataHelper->getOrderVariables($order,$emailTemplateId);
            $isEnable = true;
            if ($emailTemplateVariables['order_type'] != "SPOT" || $emailTemplateVariables['subscription_profile_id']) {
                $templateId = $this->emailDataHelper->getTempalteEmailOrderChangeSubscription();
                $isEnable = $this->emailDataHelper->isEnableToSendSubscriptionOrderChange();
            } else {
                $templateId = $this->emailDataHelper->getTempalteEmailOrderChangeSpot();
                $isEnable = $this->emailDataHelper->isEnableToSendSpotOrderChange();
            }
            if($isEnable){
                $this->inlineTranslate->suspend();


                if ($emailTemplateVariables['customer_first_name']
                    && $emailTemplateVariables['receiver'] != $order->getCustomerEmail()) {
                    $emailTemplateVariables['receiver'] = $order->getCustomerEmail(); //Fix receiver email address
                }

                $senderInfo = $this->dataHelper->getSenderEmail();
                $this->transportBuilder->setTemplateIdentifier($templateId);
                $this->transportBuilder->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $this->dataHelper->getStore()->getId(),
                    ]
                )

                    ->setTemplateVars($emailTemplateVariables)
                    ->setFrom($senderInfo)
                    ->addTo($emailTemplateVariables['receiver']);
                $transport = $this->transportBuilder->getTransport();
                $transport->setRelationEntityId($order->getIncrementId());
                $transport->setRelationEntityType('order_change');
                $transport->sendMessage();
                $this->inlineTranslate->resume();
            }
        }
    }

    /**
     * Send email to notify when place order when place order with free payment or shipment fee 
     * @param $emailTemplateVariables
     */
    public function sendMailFreePayFeeFreeShipFee($emailTemplateVariables){
        $this->inlineTranslate->suspend();
        $senderInfo = $this->dataHelper->getFreeOrderShipmentPaymentFeeSender();
        $emailReceiver = $this->dataHelper->getFreeOrderShipmentPaymentFeeReceivers();
        $this->transportBuilder->setTemplateIdentifier($this->dataHelper->getFreeOrderShipmentPaymentFeeTemplate());

        $this->transportBuilder->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_ADMINHTML,
                'store' => $this->dataHelper->getStore()->getId(),
            ]
        )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($emailReceiver);
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslate->resume();
    }

    /**
     * @param $order
     * @param null $profileId
     * @param null $emailTemplateId
     */
    public function sendMailSubscriptionChange($order,$profileId = null,$emailTemplateId=null)
    {
        if($this->emailDataHelper->isEnableToSendSubscriptionOrderChange())
        {
            $profileModel = $this->profileFactory->create()->load($profileId);
            $profileId = $profileModel->getData('profile_id');
            $emailTemplateVariables = array_merge(
                $this->orderDataHelper->getOrderVariables($order, $emailTemplateId),
                $this->orderDataHelper->getSubscriptionSimulateOrderInformation($profileId)
            );
            $emailTemplateVariables['subscription_profile_id'] = $profileId;
            if ($emailTemplateVariables['order_type'] != "SPOT" || $emailTemplateVariables['subscription_profile_id']) {
                $templateId = $this->emailDataHelper->getTempalteEmailOrderChangeSubscription();
            } else {
                $templateId = $this->emailDataHelper->getTempalteEmailOrderChangeSpot();
            }
            $this->inlineTranslate->suspend();
            $senderInfo = $this->dataHelper->getSenderEmail();
            $this->transportBuilder->setTemplateIdentifier($templateId);
            $this->transportBuilder->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->dataHelper->getStore()->getId(),
                ]
            )
                ->setTemplateVars($emailTemplateVariables)
                ->setFrom($senderInfo)
                ->addTo($emailTemplateVariables['receiver']);
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslate->resume();
        }
    }

    /**
     * Convert item cart to object for simulate
     *
     * @param $productArray
     * @return array
     */
    public function convertProductData($productArray){
        $data = [];
        foreach ($productArray as  $product){
            $obj = new DataObject();
            $obj->setData($product);
            $data[$obj->getData("cart_id")] = $obj;
        }
        return $data;
    }

    /**
     * Convert data item cart
     *
     * @param $ProductCat
     * @return array
     */
    public function getProductJson($ProductCat){
        $productArray =[];
        foreach ($ProductCat as $product){
            $productArray[] =  $product->getData();
        }
        return $productArray;
    }

    /**
     * @param $frequencyUnit
     * @param $requencyInterval
     * @return mixed
     */
    public function getSubProfileFrequencyID($frequencyUnit,$requencyInterval)
    {
        $frequencyId = $this->_frequencyFactory->create()->getResource()->getIdByData(
            $frequencyUnit,
            $requencyInterval);

        return $frequencyId;
    }
}
