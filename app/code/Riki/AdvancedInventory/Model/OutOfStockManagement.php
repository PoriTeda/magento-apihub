<?php
namespace Riki\AdvancedInventory\Model;

use Bluecom\Paygent\Model\Paygent;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\Translate\Inline\StateInterface as TranslateInlineState;
use Magento\Store\Model\StoreManagerInterface;
use Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface;
use Riki\AdvancedInventory\Helper\Logger as LoggerHelper;
use Riki\Subscription\Api\ProfileRepositoryInterface;

class OutOfStockManagement implements \Riki\AdvancedInventory\Api\OutOfStockManagementInterface
{
    const XML_PATH_REJECT_ORDER_GENERATION_RECIPIENTS_EMAIL = 'advancedinventory_outofstock/authorize_failure_email/recipients';
    const XML_PATH_REJECT_ORDER_GENERATION_EMAIL_TEMPLATE = 'advancedinventory_outofstock/authorize_failure_email/template';

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    protected $oosHelper;

    /**
     * @var ResourceModel\OutOfStock
     */
    protected $oosResourceModelFactory;

    /**
     * @var OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var Paygent
     */
    protected $paygent;

    /**
     * @var LoggerHelper
     */
    protected $loggerHelper;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var TranslateInlineState
     */
    protected $translateInlineState;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Timezone
     */
    protected $timezone;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagement;

    /**
     * OutOfStockManagement constructor.
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $oosHelper
     * @param ResourceModel\OutOfStock $outOfStockResourceModel
     * @param OutOfStockRepositoryInterface $outOfStockRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProfileRepositoryInterface $profileRepository
     * @param Paygent $paygent
     * @param LoggerHelper $loggerHelper
     * @param TransportBuilder $transportBuilder
     * @param TranslateInlineState $translateInlineState
     * @param ScopeConfigInterface $scopeConfig
     * @param Timezone $timezone
     * @param StoreManagerInterface $storeManagement
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\OutOfStock $oosHelper,
        \Riki\AdvancedInventory\Model\ResourceModel\OutOfStock $outOfStockResourceModel,
        OutOfStockRepositoryInterface $outOfStockRepository,
        CustomerRepositoryInterface $customerRepository,
        ProfileRepositoryInterface $profileRepository,
        Paygent $paygent,
        LoggerHelper $loggerHelper,
        TransportBuilder $transportBuilder,
        TranslateInlineState $translateInlineState,
        ScopeConfigInterface $scopeConfig,
        Timezone $timezone,
        StoreManagerInterface $storeManagement
    ) {
        $this->oosHelper = $oosHelper;
        $this->oosResourceModelFactory = $outOfStockResourceModel;
        $this->outOfStockRepository = $outOfStockRepository;
        $this->customerRepository = $customerRepository;
        $this->profileRepository = $profileRepository;
        $this->transportBuilder = $transportBuilder;
        $this->translateInlineState = $translateInlineState;
        $this->scopeConfig = $scopeConfig;
        $this->paygent = $paygent;
        $this->loggerHelper = $loggerHelper;
        $this->timezone = $timezone;
        $this->storeManagement = $storeManagement;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getOosQuote()
    {
        return $this->oosHelper->getOutOfStockQuote();
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function isOosGeneratedOrder($orderId)
    {
        $result = $this->oosResourceModelFactory->getOosByGeneratedOrderId($orderId);

        if ($result && count($result)) {
            return true;
        }

        return false;
    }

    /**
     * @param $productId
     * @return array
     */
    public function getOutOfStockIdsByProductId($productId)
    {
        return $this->oosResourceModelFactory->getOutOfStockIdsByProductId($productId);
    }

    /**
     * @param array $messages
     * @return $this|mixed
     */
    public function sendAuthorizeFailureEmail(array $messages)
    {
        $recipientsEmail = $this->scopeConfig->getValue(self::XML_PATH_REJECT_ORDER_GENERATION_RECIPIENTS_EMAIL);

        if (empty($recipientsEmail)) {
            return $this;
        }

        $recipientsEmail = explode(',', $recipientsEmail);

        $emailTemplate = $this->scopeConfig->getValue(self::XML_PATH_REJECT_ORDER_GENERATION_EMAIL_TEMPLATE);

        try {
            $emailTemplateVars = $this->generateAuthorizeFailureEmailTemplateVars($messages);

            $transport = $this->transportBuilder->setTemplateIdentifier(
                $emailTemplate
            )->setTemplateOptions(
                [
                    'area' => Area::AREA_FRONTEND,
                    'store' => $this->storeManagement->getStore()->getId()
                ]
            )->setTemplateVars(
                $emailTemplateVars
            )->setFrom(
                $this->getAuthorizeFailureEmailIdentity()
            )->addTo(
                array_map(function ($recipientEmail) {
                    return trim($recipientEmail);
                }, $recipientsEmail)
            )->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->loggerHelper->getOosLogger()->error(__(
                'Send authorize failure mail unsuccessfully. Error message: %1',
                $e->getMessage()
            ));
        }

        return $this;
    }

    /**
     * @param array $messages
     * @return array
     */
    protected function generateAuthorizeFailureEmailTemplateVars(array $messages)
    {
        $oosItemsData = [];
        foreach ($messages as $oosId => $message) {
            $oosItemData = $this->generateAuthorizeFailureOosItemEmailTemplateVars($oosId);

            $oosItemData['errorMessage'] = $message;

            $oosItemsData[] = $oosItemData;
        }

        return [
            'date' => $this->timezone->formatDate(null, \IntlDateFormatter::MEDIUM),
            'items' => $oosItemsData
        ];
    }

    /**
     * @param $oosId
     * @return array
     */
    protected function generateAuthorizeFailureOosItemEmailTemplateVars($oosId)
    {
        $result = [
            'oosId' => $oosId,
            'tradingId' => '',
            'consumerId' => '',
            'subscriptionCourseName' => ''
        ];

        try {
            /** @var OutOfStock $outOfStock */
            $outOfStock = $this->outOfStockRepository->getById($oosId);
        } catch (\Exception $e) {
            return $result;
        }

        $originalOrder = $outOfStock->getOriginalOrder();

        $result['tradingId'] = $this->paygent->canReAuthorization(
            $originalOrder->getCustomerId(),
            $originalOrder->getSubscriptionProfileId()
        );

        try {
            $customer = $this->customerRepository->getById($originalOrder->getCustomerId());
            if ($consumerDbIdAttr = $customer->getCustomAttribute('consumer_db_id')) {
                $result['consumerId'] = $consumerDbIdAttr->getValue();
            }
        } catch (\Exception $e) {
            $this->loggerHelper->getOosLogger()->warning(__(
                'Can not load customer for the OOS Id #%1. Error message: %2',
                $oosId,
                $e->getMessage()
            ));
        }

        if ($profileId = $originalOrder->getSubscriptionProfileId()) {
            try {
                $profile = $this->profileRepository->get($profileId);

                $result['subscriptionCourseName'] = $profile->getCourseName();
            } catch (\Exception $e) {
                $this->loggerHelper->getOosLogger()->warning(__(
                    'Can not load profile for the OOS Id #%1. Error message: %2',
                    $oosId,
                    $e->getMessage()
                ));
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getAuthorizeFailureEmailIdentity()
    {
        return [
            'email' => $this->scopeConfig->getValue('trans_email/ident_support/email'),
            'name' => $this->scopeConfig->getValue('trans_email/ident_support/name')
        ];
    }
}
