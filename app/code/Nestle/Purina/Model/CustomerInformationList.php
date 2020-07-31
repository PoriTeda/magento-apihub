<?php
/**
 * Nestle Purina Vets
 * PHP version 7
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
namespace Nestle\Purina\Model;

use Nestle\Purina\Api\CustomerGetInformationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\RegionFactory;
use Riki\Loyalty\Model\RewardManagement;
use Riki\MachineApi\Model\ApiCustomerRepository;
use Bluecom\Paygent\Model\Paygent;

/**
 * Class CustomerInformationList
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class CustomerInformationList implements CustomerGetInformationInterface
{

    /**
     * Customer Repository
     *
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Region
     *
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * Custom reward
     *
     * @var RewardManagement
     */
    protected $rewardManagement;

    /**
     * Customer Repository Api
     *
     * @var ApiCustomerRepository
     */
    protected $apiCustomerRepository;

    /**
     * Bluecom paygent model
     *
     * @var \Bluecom\Paygent\Model\Paygent
     */
    protected $bluecomPaygent;


    /**
     * CustomerInformationList constructor.
     *
     * @param CustomerRepositoryInterface $customerRepository    Customer repository.
     * @param RegionFactory               $regionFactory         Region.
     * @param RewardManagement            $rewardManagemnt       Reward point.
     * @param ApiCustomerRepository       $apiCustomerRepository Customer repo api.
     * @param Paygent                     $bluecomPaygent        Paygent.
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        RegionFactory $regionFactory,
        RewardManagement $rewardManagemnt,
        ApiCustomerRepository $apiCustomerRepository,
        Paygent $bluecomPaygent
    ) {
        $this->customerRepository = $customerRepository;
        $this->regionFactory = $regionFactory;
        $this->rewardManagement = $rewardManagemnt;
        $this->apiCustomerRepository = $apiCustomerRepository;
        $this->bluecomPaygent = $bluecomPaygent;
    }

    /**
     * Retrieve All address of customer
     *
     * @param string $consumerDbId Consumer_id.
     *
     * @return array|mixed
     */
    public function getCustomerInformation($consumerDbId)
    {
        $customerAddress = [];
        $customer = $this->apiCustomerRepository->getCustomerByConsumerDbId(
            $consumerDbId
        );
        if ($customer !== false) {
            $customerData['reward_point']       = $this->getCustomerRewardBalance($consumerDbId);
            $customerData['used_cc_flag']       = $this->getPaygentUsedCardInfo($customer);
            $customerAddress[]['customer_data'] = $customerData;
            $addresses = $customer->getAddresses();
            foreach ($addresses as $address) {
                $customAttribute = $addressData = [];
                $addressType = __('shipping address');
                if ($address->getId() == $customer->getDefaultBilling()) {
                    $addressType = __('billing address');
                }
                $addressData['addressId'] = $address->getId();
                $addressData['countryId'] = $address->getCountryId();
                $addressData['addressType'] = $addressType;

                $region = $this->getRegionData($address->getRegionId());
                $addressData['regionCode'] = $region->getData('code');
                $addressData['region'] = $region->getData('region_id');

                $addressData['street'] = $address->getStreet();
                $addressData['telephone'] = $address->getTelephone();
                $addressData['postcode'] = $address->getPostcode();
                $addressData['city'] = $address->getCity();
                $addressData['firstname'] = $address->getFirstname();
                $addressData['lastname'] = $address->getLastname();

                $customAttribute[] = $this->customAttributeValues(
                    $address->getCustomAttribute('firstnamekana')
                );
                $customAttribute[] = $this->customAttributeValues(
                    $address->getCustomAttribute('lastnamekana')
                );
                $customAttribute[] = $this->customAttributeValues(
                    $address->getCustomAttribute('riki_nickname')
                );
                $addressData['customAttributes'] = $customAttribute;
//                $customerAddresses['customer_address'][] = $addressData;
                $customerAddress[] = $addressData;
            }
        }
//         $customerAddress[] = $customerAddresses;
        return $customerAddress;
    }

    /**
     * Prepare custom attributes for customer
     *
     * @param mixed $customerObject Customer object.
     *
     * @return array
     */
    protected function customAttributeValues($customerObject)
    {
        $customObj = [];
        $customObj["attributeCode"] = $customerObject->getAttributeCode();
        $customObj["value"] = $customerObject->getValue();
        return $customObj;
    }

    /**
     * Region names in Japanese
     *
     * @param int $regionId region_id
     *
     * @return \Magento\Directory\Model\Region
     */
    protected function getRegionData($regionId)
    {
        return $this->regionFactory->create()->load($regionId);
    }

    /**
     * Get customer reward point balance
     *
     * @param string $consumerDbId consumer_id
     *
     * @return int|mixed
     */
    public function getCustomerRewardBalance($consumerDbId)
    {
        $point = 0;
        $point = $this->rewardManagement->getPointBalance($consumerDbId);
        return $point;
    }

    /**
     * Get used card info
     *
     * @param mixed $customer customer_object
     *
     * @return string|mixed
     */
    public function getPaygentUsedCardInfo($customer)
    {
        $usedCCId = 0;
        $usedCCId = $this->bluecomPaygent
                ->canReAuthorization($customer->getId());
        return $usedCCId;
    }
}
