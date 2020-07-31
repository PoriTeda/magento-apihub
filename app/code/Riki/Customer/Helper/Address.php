<?php
namespace Riki\Customer\Helper;


use Magento\Customer\Model\Address\Mapper;

class Address extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $addressRepository;

    /**
     * Filter builder
     *
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * Search criteria builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /** @var \Magento\Customer\Helper\Address  */
    protected $addressHelper;

    /** @var Mapper  */
    protected $addressMapper;

    /** @var \Magento\Customer\Api\AddressRepositoryInterface  */
    protected $addressService;

    /**
     * Address constructor.
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressService
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param Mapper $addressMapper
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Customer\Api\AddressRepositoryInterface $addressService,
        \Magento\Customer\Helper\Address $addressHelper,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Framework\App\Helper\Context $context
    ){
        $this->addressRepository = $addressRepositoryInterface;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $criteriaBuilder;
        $this->addressService = $addressService;
        $this->addressHelper = $addressHelper;
        $this->addressMapper = $addressMapper;

        parent::__construct(
            $context
        );
    }

    /**
     * @param $customerId
     * @param null $type
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    public function getAddressListByCustomerId($customerId, $type = null){
        $filterCustomer = $this->filterBuilder
            ->setField('parent_id')
            ->setValue($customerId)
            ->setConditionType('eq')
            ->create();
        $this->searchCriteriaBuilder->addFilters([$filterCustomer]);

        if(!is_null($type)){
            $filterType = $this->filterBuilder
                ->setField('riki_type_address')
                ->setValue($type)
                ->setConditionType('eq')
                ->create();

            $this->searchCriteriaBuilder->addFilters([$filterType]);
        }

        $this->searchCriteriaBuilder->setCurrentPage(0);
        $this->searchCriteriaBuilder->setPageSize(1);

        try{
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $result = $this->addressService->getList($searchCriteria);
            $result = $result->getItems();

            if(count($result))
                return $result[0];
        }catch (\Exception $e){
            return null;
        }

        return null;
    }

    /**
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param string $format
     * @return string
     */
    public function formatCustomerAddressToString(\Magento\Customer\Api\Data\AddressInterface $address, $format = 'html'){
        $formatTypeRenderer = $this->addressHelper->getFormatTypeRenderer($format);
        $result = '';
        if ($formatTypeRenderer) {
            $result = $formatTypeRenderer->renderArray($this->addressMapper->toFlatArray($address));
        }

        return $result;
    }
}
