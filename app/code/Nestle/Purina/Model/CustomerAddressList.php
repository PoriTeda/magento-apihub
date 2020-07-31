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

use Nestle\Purina\Api\CustomerGetAllAddressInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\RegionFactory;
use Riki\MachineApi\Model\ApiCustomerRepository;

/**
 * Class CustomerAddressList
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class CustomerAddressList implements CustomerGetAllAddressInterface
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
     * Customer Repository Api
     *
     * @var ApiCustomerRepository
     */
    protected $apiCustomerRepository;

    /**
     * CustomerAddressList constructor.
     *
     * @param CustomerRepositoryInterface $customerRepository    customer repository
     * @param RegionFactory               $regionFactory         region
     * @param ApiCustomerRepository       $apiCustomerRepository customer repo api
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        RegionFactory $regionFactory,
        ApiCustomerRepository $apiCustomerRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->regionFactory = $regionFactory;
        $this->apiCustomerRepository = $apiCustomerRepository;
    }

    /**
     * Retrieve All address of customer
     *
     * @param string $consumerDbId consumer_id
     *
     * @return array|mixed
     */
    public function getCustomerAllAddress($consumerDbId)
    {
        $customerAddress = [];
        $customer = $this->apiCustomerRepository->getCustomerByConsumerDbId(
            $consumerDbId
        );
        if ($customer) {
            $addresses = $customer->getAddresses();
            foreach ($addresses as $address) {
                $addressData = $customAttribute = [];
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

                $customerAddress[$address->getId()] = $addressData;
            }
        }
        return $customerAddress;
    }

    /**
     * Prepare custom attributes for customer
     *
     * @param mixed $customerObject customer_object
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
}
