<?php

namespace Riki\NpAtobarai\Gateway\Request\TransactionRegistration;

use Magento\Framework\Exception\LocalizedException;
use Riki\Customer\Model\Address\AddressType;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region as RegionResourceModel;

/**
 * Class ShippingDataBuilder
 */
class ShippingDataBuilder implements BuilderInterface
{
    /**
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * @var RegionResourceModel
     */
    private $regionResource;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * ShippingDataBuilder constructor.
     * @param RegionFactory $regionFactory
     * @param RegionResourceModel $regionResourceModel
     */
    public function __construct(
        RegionFactory $regionFactory,
        RegionResourceModel $regionResourceModel,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->regionFactory = $regionFactory;
        $this->regionResource = $regionResourceModel;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param \Riki\NpAtobarai\Api\Data\TransactionInterface[] $transactions
     * @return mixed
     * @throws LocalizedException
     */
    public function build(array $transactions)
    {
        $transaction = isset($transactions['transaction']) ? $transactions['transaction'] : '';

        if (!$transaction instanceof \Riki\NpAtobarai\Api\Data\TransactionInterface) {
            throw new LocalizedException(__('Transaction must be an instance of NpTransaction'));
        }

        $shippingAddress = $transaction->getShippingAddress();
        $order = $transaction->getOrder();
        $customerId = $order->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $ambCompanyName = '';
        if ($customer->getCustomAttribute('amb_com_name')) {
            $ambCompanyName = $customer->getCustomAttribute('amb_com_name')->getValue();
        }
        $companyDeptName = '';
        if ($customer->getCustomAttribute('amb_com_division_name')) {
            $companyDeptName = $customer->getCustomAttribute('amb_com_division_name')->getValue();
        }
        $companyName = '';
        $departmentName = '';
        switch ($shippingAddress->getData('riki_type_address')) {
            case AddressType::HOME:
                $customerName = $shippingAddress->getLastname() . ' ' . $shippingAddress->getFirstname();
                break;
            case AddressType::OFFICE:
                $customerName = $shippingAddress->getFirstname();
                $customerName = str_replace('（ご担当：', '', $customerName);
                $customerName = str_replace('様）', '', $customerName);
                $companyName = $ambCompanyName;
                $departmentName = $companyDeptName;
                break;
            default:
                $customerName = $shippingAddress->getLastname() . ' ' . $shippingAddress->getFirstname();
        }

        $customerNameKana = $shippingAddress->getLastnamekana() . ' ' . $shippingAddress->getFirstnamekana();
        $street = $shippingAddress->getStreet();
        if (is_array($street)) {
            $street = array_shift($street);
        }

        $telephone = str_replace('-', '', $shippingAddress->getTelephone());
        $telephone = (!preg_match('/^0/', $telephone)) ? '0' . $telephone : $telephone;
        if (preg_match('/^(020|050|060|070|080|090)/m', $telephone)) {
            $telephone = preg_replace('/^(\\d{3})(\\d{4})(\\d{3,})$/', '\1-\2-\3', $telephone, 1);
        } else {
            $telephone = preg_replace('/^(\\d{2})(\\d{4})(\\d{3,})$/', '\1-\2-\3', $telephone, 1);
        }

        $region = $this->regionFactory->create(['resource' => $this->regionResource])
            ->load($shippingAddress->getRegionId());
        if (!$region->getId()) {
            throw new LocalizedException(__('The region does not exist'));
        }

        $shippingInformation = [
            'customer_name' => mb_substr($customerName, 0, 20),
            'customer_name_kana' => mb_substr($customerNameKana, 0, 24),
            'company_name' => mb_substr($companyName, 0, 30),
            'department_name' => mb_substr($departmentName, 0, 30),
            'zip_code' => $shippingAddress->getPostcode(),
            'address' => mb_substr($region->getName() . ' ' . $street, 0, 54),
            'tel' => $telephone
        ];

        return ['dest_customer' => $shippingInformation];
    }
}
