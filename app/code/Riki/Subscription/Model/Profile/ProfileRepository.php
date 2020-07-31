<?php
namespace Riki\Subscription\Model\Profile;

use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Subscription\Api\Data\ProfileSearchResultsInterfaceFactory;
use Riki\Subscription\Api\ProfileRepositoryInterface;
use Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection as ProfileCollection;
use Riki\Subscription\Model\Profile\ResourceModel\Profile as ProfileResource;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ProductFactory;
use Riki\Subscription\Model\ProductCart\ProductCartFactory;
use Magento\Framework\Api\SortOrder;

class ProfileRepository implements ProfileRepositoryInterface
{
    /**
     * @var ProfileSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var ProfileResource
     */
    protected $resource;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ProductCartFactory
     */
    protected $productCartFactory;
    /**
     * @var \Riki\Subscription\Model\Version\VersionFactory
     */
    protected $versionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Riki\Subscription\Api\ProfileProductCartRepositoryInterface
     */
    protected $productCartRepository;
    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * ProfileRepository constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param ProfileSearchResultsInterfaceFactory $searchResultsFactory
     * @param ProfileFactory $profileFactory
     * @param ProfileResource $resource
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductCartFactory $productCartFactory
     * @param \Riki\Subscription\Model\Version\VersionFactory $versionFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Api\ProfileProductCartRepositoryInterface $productCartRepository
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        ProfileSearchResultsInterfaceFactory $searchResultsFactory,
        ProfileFactory $profileFactory,
        ProfileResource $resource,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductCartFactory $productCartFactory,
        \Riki\Subscription\Model\Version\VersionFactory $versionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Api\ProfileProductCartRepositoryInterface $productCartRepository,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->functionCache = $functionCache;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->profileFactory = $profileFactory;
        $this->resource = $resource;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productCartFactory = $productCartFactory;
        $this->versionFactory = $versionFactory;
        $this->logger = $logger;
        $this->productCartRepository =  $productCartRepository;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     *
     * @param $id
     *
     * @return \Riki\Subscription\Api\Data\ApiProfileInterface
     *
     * @throws NoSuchEntityException
     */
    public function get($id)
    {
        $profileId = $id;
        if ($id instanceof \Riki\Subscription\Api\Data\ApiProfileInterface) {
            $profileId = $id->getProfileId();
        }

        if ($this->functionCache->has($profileId)) {
            if ($id instanceof \Riki\Subscription\Api\Data\ApiProfileInterface) {
                return $this->functionCache->load($profileId);
            }

            return $this->functionCache->load($profileId)->getDataModel();
        }

        /** @var \Riki\Subscription\Model\Profile\Profile $result */
        $result = $this->profileFactory->create()->load($profileId);

        if (!$result->getId()) {
            throw new NoSuchEntityException(
                __(
                    NoSuchEntityException::MESSAGE_SINGLE_FIELD,
                    [
                        'fieldName' => 'profile_id',
                        'fieldValue' => $profileId
                    ]
                )
            );
        }

        $this->functionCache->store($result, $profileId);

        return $result->getDataModel();
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @return \Riki\Subscription\Api\Data\ProfileSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria)
    {
        //@TODO: fix search logic
        /** @var \Riki\Subscription\Api\Data\ProfileSearchResultsInterface $searchResult */
        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);

        /** @var ProfileCollection $collection */
        $collection = $this->profileFactory->create()->getCollection();
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $fields = [];
            $conditions = [];
            foreach ($group->getFilters() as $filter) {
                $fields[] = $filter->getField();
                $condition = $filter->getConditionType() ?: 'eq';
                $conditions[] = [$condition => $filter->getValue()];
            }
            $collection->addFieldToFilter($fields, $conditions);
        }
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection() == SortOrder::SORT_ASC ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setItems($collection->getItems());

        return $searchResult;
    }

    /**
     * @param $profile
     * @param $profile_id
     * @param bool $is_update
     */
    public function validate(\Riki\Subscription\Api\Data\ApiProfileInterface $profile)
    {
    }

    /**
     * Save a profile
     *
     * @param \Riki\Subscription\Api\Data\ApiProfileInterface $profileDataModel
     *
     * @return mixed
     */
    public function save(\Riki\Subscription\Api\Data\ApiProfileInterface $profileDataModel)
    {
        $profileModel = $this->profileFactory->create();

        if ($profileDataModel->getProfileId()) {
            $profileModel->load($profileDataModel->getProfileId());
        }

        $profileModel->addData(
            $this->extensibleDataObjectConverter->toNestedArray(
                $profileDataModel,
                [],
                '\Riki\Subscription\Api\Data\ApiProfileInterface'
            )
        );

        $this->resource->save($profileModel);

        return $profileModel->getDataModel();
    }

    /**
     * Delete a profile by profileDataModel
     *
     * @param \Riki\Subscription\Api\Data\ApiProfileInterface $profile
     * @return bool|mixed
     */
    public function delete(\Riki\Subscription\Api\Data\ApiProfileInterface $profile)
    {
        return $this->deleteById($profile->getProfileId());
    }

    /**
     * Delete a profile by profile_id
     *
     * @param $profileId
     * @return bool
     */
    public function deleteById($profileId)
    {
        $profileModel = $this->profileFactory->create()->load($profileId);
        try {
            $profileModel->delete();
        }catch (\Exception $e){
            $this->logger->critical($e);
            throw $e;
        }
        return true;
    }

    /**
     * Get All product of a profile by profile_id
     *
     * @param $profileId
     * @return mixed
     */
    public function getListProductCart($profileId){
        /** $profileId always is main_profile_id*/
        $profileModel = $this->get($profileId);
        /** $profileVersionId is profile_version_id if this profile have a available version
         * If this profile does not have version then $profileVersionId == $profileId == main_profile_id
         */
        if($profileModel->getProfileId()) {
            $profileId = $profileModel->getProfileId();
        }
        $profileVersionId = $this->getProfileIdVersion($profileId);
        $searchBuilder = $this->searchCriteriaBuilder->addFilter('profile_id',$profileVersionId,'eq')->create();
        $productCartModel = $this->productCartRepository->getList($searchBuilder);
        return $productCartModel;

    }

    /**
     * Get profile_version_id if this profile has a version is active
     *
     * @param $profileId
     * @return int
     */
    public function getProfileIdVersion($profileId){
        $versionModel = $this->versionFactory->create()->getCollection();
        $versionModel->addFieldToFilter('rollback_id',$profileId);
        $versionModel->addFieldToFilter('status',1);
        $versionModel->setOrder('id','DESC');
        if(sizeof($versionModel) > 0){
            $profileVersionId = $versionModel->setPageSize(1)->getFirstItem()->getData('moved_to');
            return $profileVersionId;
        }
        return $profileId;
    }

    /**
     * @param string $stockPointId
     * @return ProfileCollection
     */
    public function getProfilesByStockPointId($stockPointId)
    {
        /** @var ProfileCollection $collection */
        $collection = $this->profileFactory->create()->getCollection();
        $collection->setFlag('original', 1);
        $collection->getSelect()->join(
            ['sppb' => $collection->getTable('stock_point_profile_bucket')],
            'main_table.stock_point_profile_bucket_id = sppb.profile_bucket_id',
            ''
        )->join(
            ['sp' => $collection->getTable('stock_point')],
            'sp.stock_point_id = sppb.stock_point_id AND sp.external_stock_point_id = ' . $stockPointId,
            ''
        );
        return $collection;
    }
}
