<?php
namespace Riki\Subscription\Model\Email;

class ProfilePaymentMethodErrorBusiness extends \Bluecom\Paygent\Model\Email\ReauthorizeFailureBusiness
{
    const CONFIG_SENDER = 'subcreateorder/payment_method_error/sender';
    const CONFIG_TEMPLATE = 'subcreateorder/payment_method_error/email_template_business';
    const CONFIG_RECEIVER = 'subcreateorder/payment_method_error/receiver';

    const CONFIG_DIR_NAME = 'ProfilePaymentMethodErrorBusiness';

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $dir;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Bluecom\Paygent\Api\PaygentManagementInterface
     */
    protected $paygentManagement;

    /**
     * ProfilePaymentMethodErrorBusiness constructor.
     *
     * @param \Bluecom\Paygent\Api\PaygentManagementInterfaceFactory $paygentManagementFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
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
        \Bluecom\Paygent\Api\PaygentManagementInterfaceFactory $paygentManagementFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\AreaList $areaList,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation, \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ) {
        $this->paygentManagement = $appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB, [$paygentManagementFactory, 'create']);
        $this->filesystem = $filesystem;
        $this->dir = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($appState, $areaList, $profileRepository, $customerRepository, $timezone, $dataObjectFactory, $storeManager, $scopeConfig, $logger, $inlineTranslation, $transportBuilder);
    }

    /**
     * Get relative path
     *
     * @return string
     */
    public function getRelativePath()
    {
        $path = static::CONFIG_DIR_NAME
            . DIRECTORY_SEPARATOR
            . $this->timezone->date()->format('Y-m-d');

        if (!$this->dir->isDirectory($path)) {
            $this->dir->create($path);
        }

        return $path;
    }

    /**
     * Batch mode enable
     *
     * @return bool
     */
    public function getIsBatchMode()
    {
        return $this->getVariables()->getData('batchMode') == 1;
    }

    /**
     * Insert into temp storage
     *
     * @param array $item
     *
     * @return $this
     */
    public function queue($item = [])
    {
        $tmpPath = $this->getRelativePath() . DIRECTORY_SEPARATOR . uniqid(spl_object_hash($this));
        $this->dir->writeFile($tmpPath, \Zend_Json::encode($item));

        return $this;
    }

    /**
     * Get items from storage
     *
     * @return array
     */
    public function getItemsFromQueue()
    {
        $items = [];

        $files = $this->dir->search('*', $this->getRelativePath());
        foreach ($files as $file) {
            try {
                $items[] = \Zend_Json::decode($this->dir->readFile($file));
            } catch (\Zend_Json_Exception $e) {
                $this->logger->warning($e);
            }

        }

        return $items;
    }

    /**
     * {@inheritdoc}
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

        $profile = isset($params['profile']) ? $params['profile'] : null;
        if ($profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            $item['tradingId'] = $profile->getData('trading_id');
            $item['errorMessage'] = $this->paygentManagement->getErrorMessage(['errorCode' => (string) $profile->getData('paymentErrorCode')]);

            try {
                $customer = $this->customerRepository->getById($profile->getCustomerId());
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

            $item['subscriptionCourseName'] = $profile->getCourseName();
        }

        if ($this->getIsBatchMode()) {
            $this->queue($item);
        } else {
            $this->items[] = $item;
        }

        return $this;
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
        if ($this->getIsBatchMode()) {
            $query = $this->searchCriteriaBuilder
                ->addFilter('publish_message', 1)
                ->setPageSize(1)
                ->create();
            if ($this->profileRepository->getList($query)->getItems()) {
                return true;
            }

            $this->items = $this->getItemsFromQueue();
            $this->dir->delete($this->getRelativePath());
        }

        return parent::send($params);
    }
}