<?php
namespace Riki\Subscription\Model\Profile\ResourceModel\Indexer;

class Reindex
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $simulator;

    /**
     * @var \Riki\Subscription\Helper\Indexer\Data\Proxy
     */
    protected $indexerHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * Reindex constructor.
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Riki\Subscription\Helper\Indexer\Data\Proxy $indexerHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\Subscription\Helper\Indexer\Data\Proxy $indexerHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->customerRepository = $customerRepository;
        $this->simulator = $simulator;
        $this->indexerHelper = $indexerHelper;
        $this->logger = $logger;
        $this->profileFactory = $profileFactory;
        $this->resource = $resourceConnection;
        $this->connectionSales = $this->resource->getConnection('sales');
        $this->registry = $registry;
    }

    /**
     * @param \Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface $message
     */
    public function reindexProfile(\Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface $message)
    {

        $this->registry->unregister('reindex_cache_profile');
        $this->registry->register('reindex_cache_profile',true);

        $profileId = null;
        foreach ($message->getItems() as $profileObject) {
            $profileId = $profileObject->getProfileId();
        }

        if($profileId){
            /*** start get data from simulator ***/
            $simulatorOrder = null;
            try {
                $simulatorOrder = $this->simulator->createMageOrder($profileId, null, true);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->info($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }

            $dataSimulate = $this->prepareData($simulatorOrder);

            if ($dataSimulate) {
                $serializedData = \Zend\Serializer\Serializer::serialize($dataSimulate);
                $dataTable = [
                    'profile_id' => $profileId,
                    'customer_id' => $simulatorOrder->getCustomerId(),
                    'data_serialized' => $serializedData
                ];
                $profileIds[] = $profileId;
                $this->saveToTable($dataTable);
            }
            /*** end get data from simulator ***/
            $this->indexerHelper->makeCacheDataForHanpukai($profileId);
            /*update reindex flag*/
            $this->updateProfile($profileId);
        }
    }

    /**
     * @param $profileId
     */
    public function updateProfile($profileId){

        $profileModel = $this->profileFactory->create()->load($profileId);

        if($profileModel->getId()) {
            $profileModel->setData('reindex_flag',0);
            try {
                $profileModel->save();
            }catch (\Exception $e){
                $this->logger->critical($e);
            }
        }
    }
    /**
     * @param $simulatorOrder
     * @return array|bool
     */
    protected function prepareData($simulatorOrder)
    {
        if ($simulatorOrder) {
            $data = [
                'discount' => $simulatorOrder->getDiscountAmount(),
                'shipping_fee' => $simulatorOrder->getShippingInclTax(),
                'payment_method_fee' => $simulatorOrder->getFee(),
                'wrapping_fee' => $simulatorOrder->getData('gw_items_base_price_incl_tax'),
                'total_amount' => $simulatorOrder->getGrandTotal()
            ];
            return $data;
        }
        return false;
    }


    /**
     * @param $data
     * @return $this
     * @throws \Exception
     */
    protected function saveToTable($data)
    {
        $this->connectionSales->beginTransaction();

        try {
            $this->connectionSales->insertMultiple(
                $this->connectionSales->getTableName('subscription_profile_simulate_cache'),
                $data
            );
            $this->connectionSales->commit();

        } catch (\Exception $e) {
            $this->connectionSales->rollback();
            throw $e;
        }

        return $this;
    }
}