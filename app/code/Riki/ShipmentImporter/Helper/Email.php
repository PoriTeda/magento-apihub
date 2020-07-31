<?php
/**
 * Riki Shipment Importer
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ShipmentImporter\Helper;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Email
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Email extends AbstractHelper {
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var Data
     */
    protected $dataHelper;
    /**
     * @var \Riki\Loyalty\Model\ResourceModel\Reward\Collection
     */
    protected $pointCollection;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;
    /**
     * @var \Riki\EmailMarketing\Helper\Order
     */
    protected $orderEmailHelper;

    protected $inlineTranslation;

    protected $transportBuilder;
    /**
     * Email constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Data $dataHelper
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Riki\Loyalty\Model\ResourceModel\Reward\Collection $rewardCollection
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Riki\EmailMarketing\Helper\Order $orderEmailHelper
     */
    Public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\ShipmentImporter\Helper\Data $dataHelper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\Loyalty\Model\ResourceModel\Reward\Collection $rewardCollection,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Riki\EmailMarketing\Helper\Order $orderEmailHelper,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation

    ) {
        $this->customerFactory = $customerFactory;
        $this->dataHelper = $dataHelper;
        $this->pointCollection = $rewardCollection;
        $this->priceHelper = $priceHelper;
        $this->orderEmailHelper = $orderEmailHelper;
        $this->scopeConfig = $context;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        parent::__construct($context);
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    public function getEmailParameters(
        \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $order = $shipment->getOrder();
        //get tracking info
        $tracks = $shipment->getAllTracks();
        $trackingCodes = array();
        $trackingUrl = array();
        $carrierName = array();
        if($tracks) {
            foreach($tracks as $_track) {
                $trackingCodes[] = $_track->getTrackNumber();
                $trackingUrl[] = $this->dataHelper->getCarrierUrl(
                    $_track->getCarrierCode()
                );
                $carrierName[(string)$_track->getTitle()] = $this->dataHelper->getInquiryTitle($_track->getCarrierCode());
            }
        }
        /**
         * Controlled by Email Marketing Module
         * Email content for Shipped out Shipment
         */
        $eTrackingCodes = implode(',', $trackingCodes);
        $eTrackingUrls = implode("\r\n", $trackingUrl);
        $eCarrierNames = implode("\r\n", $carrierName);
        $emailTemplateVariables = $this->orderEmailHelper->getOrderVariablesByShipment
        (
            $eCarrierNames,
            $eTrackingCodes,
            $eTrackingUrls,
            $order,
            $shipment
        );
        return $emailTemplateVariables;
    }

    /**
     * @param   $itemIds
     * @return  int
     */
    public function getTotalPointShipment($itemIds) {

        $pointCollection = $this->pointCollection
                            ->addFieldToFilter('status',\Riki\Loyalty\Model\Reward::STATUS_SHOPPING_POINT)
                            ->addFieldToFilter('order_item_id', array('in'=>$itemIds));
        $totalPoint = 0;
        if($pointCollection->getSize()) {
            foreach($pointCollection as $_point) {
                $totalPoint+= $_point->getPoint();
            }
        }

        return $totalPoint;
    }

    /**
     * @return mixed
     */
    public function getSenderEmail()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/email',$storeScope);
    }
    /**
     * @return mixed
     */
    public function getSenderName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/name',$storeScope);
    }

    /**
     * @return mixed
     */
    public function getReceiver()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('shipmentimporter/email/receiver',$storeScope);

    }
    /**
     * @param $emailTemplateVariables
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables)
    {
        $senderInfo = [
            'name' => $this->getSenderName() ,
            'email' => $this->getSenderEmail()
        ];
        $this->transportBuilder->setTemplateIdentifier('shipmentimporter_email_template_error_cvs')
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($this->getReceiver());
        return $this;
    }

    /**
     * @param $emailTemplateVariables
     */
    public function sendCancelationEmailCvs($emailTemplateVariables)
    {
        $this->inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables);
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

}