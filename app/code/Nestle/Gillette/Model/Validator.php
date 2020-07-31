<?php


namespace Nestle\Gillette\Model;


use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Validator
{
    const CART_API = 'cart';
    const ORDER_API = 'order';
    const VALIDATION_RULES_CART_ESTIMATION = [
        'required' => [
            'fields' => [],
        ]
    ];

    const VALIDATION_RULES_ORDER = [
        'required' => [
            'fields' => [
                'payment_method',
                'consumer_db_id'
            ],
        ],
        'address' => [
            'country_id',
            'region_code',
            'region',
            'street',
            'telephone',
            'postcode',
            'city',
            'firstname',
            'lastname',
            'firstnamekana',
            'lastnamekana',
            'riki_nickname'
        ]
    ];
    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\MachineApi\Model\ApiCustomerRepository
     */
    protected $apiCustomerRepository;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $giftWrappingHelper;

    /**
     * @var \Bluecom\Paygent\Model\PaygentHistory
     */
    protected $paygentHistory;

    public function __construct(
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\GiftWrapping\Helper\Data $giftWrappingHelper,
        \Riki\MachineApi\Model\ApiCustomerRepository $apiCustomerRepository,
        \Bluecom\Paygent\Model\PaygentHistory $paygentHistory
    )
    {
        $this->courseRepository = $courseRepository;
        $this->scopeConfig = $scopeConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->giftWrappingHelper =$giftWrappingHelper;
        $this->apiCustomerRepository = $apiCustomerRepository;
        $this->paygentHistory = $paygentHistory;
    }

    /**
     * Get customer by consumer db id
     *
     * @param string $consumerDbId
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomerByConsumerDbId($consumerDbId)
    {
        $customerData = $this->apiCustomerRepository->createNewCustomerWithConsumerDb($consumerDbId);
        return $customerData;

    }

    /**
     * @param \Nestle\Gillette\Api\Data\CartEstimationInterface $data
     * @param $type
     * @throws $inputException
     * @throws LocalizedException
     */
    public function validateData($data, $type) {
        $inputException = new InputException();
        switch ($type) {
            case self::CART_API :
            {
                $this->_validate(self::VALIDATION_RULES_CART_ESTIMATION, $data);
                break;
            }
            case self::ORDER_API :
            {
                $this->_validate( self::VALIDATION_RULES_ORDER, $data);
                break;
            }
            default:
                break;
        }
    }
    private function _validate(array $ruleGroup, $data)
    {
        $inputException = new InputException();
        foreach ($ruleGroup as $key => $rules) {
            switch ($key) {
                case 'required':
                    $this->_validateRequired($data, $rules,$inputException);
                    break;
                case 'address':
                    $this->_validateAddress($data, $rules, $inputException);
                    break;
                case 'must_exist':
                    $this->_validateExistence($data, $rules);
                    break;
                case 'other':
                    $this->_validateOther($data, $rules);
                    break;
                default:
                    throw new LocalizedException(__('Invalid %ruleGroup', ['ruleGroup' => $key]));
            }
        }
    }

    /**
     * @param $data
     * @param $rules
     * @param $inputException
     * @return bool
     * @throws \Zend_Validate_Exception
     */
    private function _validateRequired($data, $rules, &$inputException) {

        /*Validate product*/
        foreach ($rules as $rule) {
            if (isset($rules['fields'])) {
                foreach ($rules['fields'] as $key => $field) {
                    if (!\Zend_Validate::is($data->getData($field), 'NotEmpty')) {
                        $inputException->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => $field]));
                    }
                }
            }
        }
        if (!$data->getProducts()) {
            $inputException->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'products']));
        } else {
            /** @var \Nestle\Gilloette\Api\Data\ProductInfoInterface $products */
            foreach ($data->getProducts() as $product) {
                if (!$product->getSku()) {
                    $inputException->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'sku']));
                }
                if (!$product->getQty()) {
                    $inputException->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'qty']));
                }
            }
        }
        if ($inputException->wasErrorAdded()) {
            throw $inputException;
        }
        return true;
    }

    /**
     * @param $data
     * @param $rules
     * @param $inputException
     * @return bool
     * @throws \Zend_Validate_Exception
     */
    private function _validateAddress($data, $rule, &$inputException) {

        if (isset($data['shipping_address_id'])) {
            if (!is_numeric($data['shipping_address_id'])) {
                $inputException->addError(__(InputException::INVALID_FIELD_VALUE, ['fieldName' => 'shipping_address_id']));
            }
        } else {
            if (isset($data['address']) and is_array($data['address'])) {
                $address = $data['address'];
                foreach ($rule as $field) {
                    if (!isset($address[$field]) or !\Zend_Validate::is($address[$field], 'NotEmpty')) {
                        $inputException->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => $field]));
                    }
                }
            } else {
                $inputException->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'address']));
            }
        }

        if ($inputException->wasErrorAdded()) {
            throw $inputException;
        }
        return true;
    }

    /**
     * Get previous card for Paygent
     * @param $customerId
     * @return array
     */
    public function getPreviousCard($customerId) {
        $collection = $this->paygentHistory->getCollection()
            ->addFieldToFilter('customer_id', ['eq' => $customerId])
            //->addFieldToFilter('order_number', ['neq' => ''])
            ->addFieldToFilter('type', ['eq' => 'authorize'])
            ->setOrder('id', 'desc')
            ->setPageSize(1);
        if (!$collection->getSize()) {
            return null;
        }
        return $collection->getFirstItem()->getUsedDate();
    }
}