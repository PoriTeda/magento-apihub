<?php
namespace Bluecom\Paygent\Model\Email;

class ReauthorizeFailureBusiness extends \Riki\Framework\Model\Email\AbstractEmail
{
    const CONFIG_SENDER = 'paygent_config/authorisation/identity';
    const CONFIG_TEMPLATE = 'paygent_config/authorisation/template_business';
    const CONFIG_RECEIVER = 'paygent_config/authorisation/receiver';

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Magento\Framework\App\AreaList
     */
    protected $areaList;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * ReauthorizeFailureBusiness constructor.
     *
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\App\AreaList $areaList
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\AreaList $areaList,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ) {
        $this->appState = $appState;
        $this->areaList = $areaList;
        $this->profileRepository = $profileRepository;
        $this->customerRepository = $customerRepository;
        $this->timezone = $timezone;
        parent::__construct($dataObjectFactory, $storeManager, $scopeConfig, $logger, $inlineTranslation, $transportBuilder);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->template && !parent::getTemplate()) {
            $this->template = 'paygent_config_authorisation_template_business';
        }
        return $this->template;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $params
     *
     * @return bool
     */
    public function send($params = [])
    {
        if (!isset($params['items'])) {
            $params['items'] = $this->items;
        }
        if (!isset($params['date'])) {
            $params['date'] = $this->timezone->formatDate($this->timezone->date());
        }

        if (!$params['items']) {
            return true;
        }

        $area = $this->areaList->getArea($this->appState->getAreaCode());
        $area->load(\Magento\Framework\App\AreaInterface::PART_TRANSLATE);

        $result = parent::send($params);
        $this->items = [];

        return $result;
    }



    /**
     * Add item
     *
     * @param array $params
     *
     * @return $this
     */
    public function addItem($params = [])
    {
        $item = [
            'errorMessage' => '',
            'orderIncrementId' => '',
            'tradingId' => '',
            'consumerId' => '',
            'subscriptionCourseName' => '',
        ];

        if (isset($params['errorMessage'])) {
            $item['errorMessage'] = $params['errorMessage'];
        }
        $order = isset($params['order']) ? $params['order'] : null;
        if ($order instanceof \Magento\Sales\Model\Order) {
            $item['orderIncrementId'] = $order->getIncrementId();
            $item['tradingId'] = $order->getIncrementId();

            try {
                $customer = $this->customerRepository->getById($order->getCustomerId());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->warning($e);
                $customer = null;
            }

            if ($customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
                $consumerIdAttr = $customer->getCustomAttribute('consumer_db_id');
                if ($consumerIdAttr) {
                    $item['consumerId'] = $consumerIdAttr->getValue();
                }
            }

            if ($order->getData('subscription_profile_id')) {
                try {
                    $profile = $this->profileRepository->get($order->getData('subscription_profile_id'));
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $this->logger->warning($e);
                    $profile = null;
                }

                if ($profile instanceof \Riki\Subscription\Api\Data\ApiProfileInterface) {
                    $item['subscriptionCourseName'] = $profile->getCourseName();
                }
            }
        }

        $this->items[] = $item;
        return $this;
    }
}