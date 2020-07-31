<?php
namespace Riki\Customer\Helper;

class ShoshaHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SHOSHAATTRIBUTE = 'shosha_business_code';
    const BLOCKORDERSATTRIBUTE = 'block_orders';
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;
    /**
     * @var \Riki\Customer\Model\ShoshaFactory
     */
    protected $_shoshaModel;
    /**
     * @var \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory
     */
    protected $_shoshaCollection;

    /**
     * ShoshaHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Customer\Model\ShoshaFactory $shoshaFactory
     * @param \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $shoshaCollection
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Customer\Model\ShoshaFactory $shoshaFactory,
        \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $shoshaCollection
    ) {
        parent::__construct($context);
        $this->_customerRepository = $customerRepository;
        $this->_shoshaModel = $shoshaFactory;
        $this->_shoshaCollection = $shoshaCollection;
    }

    /**
     * @param $id
     * @return bool|\Riki\Customer\Model\Shosha
     */
    public function getBusinessDataById($id)
    {
        try {
            return $this->_shoshaModel->create()->load($id);
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * @param $customerId
     * @return bool|\Magento\Framework\DataObject
     */
    public function getBusinessDataByCustomerId($customerId)
    {
        $customer = $this->getCustomerById($customerId);
        return $this->getBusinessDataByCustomer($customer);
    }

    /**
     * @param $customer
     * @return bool|\Magento\Framework\DataObject
     */
    public function getBusinessDataByCustomer($customer)
    {
        if ($customer) {
            $shoshaCustomer = $customer->getCustomAttribute(self::SHOSHAATTRIBUTE);
            if (!empty($shoshaCustomer)) {
                return $this->getBusinessDataByShoshaCode($shoshaCustomer->getValue());
            }
        }
        return false;
    }

    /**
     * @param $shoshaCode
     * @return bool|\Magento\Framework\DataObject
     */
    public function getBusinessDataByShoshaCode($shoshaCode)
    {
        $shoshaCollection = $this->_shoshaCollection->create();

        $shoshaCollection->addFieldToFilter(
            'shosha_business_code', $shoshaCode
        );

        if ($shoshaCollection->getSize()) {
            return $shoshaCollection->setPageSize(1)->getFirstItem();
        } else {
            return false;
        }
    }

    /**
     * Check business Data is Cedyna
     *
     * @param \Riki\Customer\Model\Shosha $shosha
     * @return bool
     */
    public function isCedynaBusinessByData(\Riki\Customer\Model\Shosha $shosha)
    {
        if ($shosha && $shosha->getShoshaCode() == \Riki\Customer\Model\Shosha\ShoshaCode::CEDYNA) {
            return true;
        }
        return false;
    }

    /**
     * @param $customerId
     * @return bool
     */
    public function isCedynaCustomer($customerId)
    {
        $customer = $this->getCustomerById($customerId);
        return $this->isCedynaCustomerByData($customer);
    }

    /**
     * @param $customer
     * @return bool
     */
    public function isCedynaCustomerByData($customer)
    {
        if ($customer) {
            $shoshaCustomer = $customer->getCustomAttribute(self::SHOSHAATTRIBUTE);
            if( !empty($shoshaCustomer) ){
                return $this->isCedynaCustomerByCode( $shoshaCustomer->getValue() );
            }
        }
        return false;
    }

    /**
     * @param $shoshaCode
     * @return bool
     */
    public function isCedynaCustomerByCode($shoshaCode)
    {
        $shoshaCollection = $this->_shoshaCollection->create();
        $shoshaCollection->addFieldToFilter(
            'shosha_business_code', $shoshaCode
        )->addFieldToFilter(
            'shosha_code', \Riki\Customer\Model\Shosha\ShoshaCode::CEDYNA
        );

        if ($shoshaCollection->getSize()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $customerId
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomerById($customerId)
    {
        try {
            return $this->_customerRepository->getById($customerId);
        } catch (\Exception $e){
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param mixed $paymentMethod
     * @return bool
     */
    public function isBlockInvoiceOrder(\Magento\Sales\Model\Order $order, $paymentMethod = false)
    {
        if (!$paymentMethod && !empty($order->getPayment())) {
            $paymentMethod = $order->getPayment()->getMethod();
        }

        /* condition 1 - block invoice order*/
        if ($paymentMethod == \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE) {

            /* get business data of this order customer*/
            $businessData = $this->getBusinessDataByCustomerId($order->getCustomerId());

            /* condition 2: shosha customer (!empty $businessData)
             * condition 3: business data - block order is yes
             */
            if ($businessData && $businessData->getData(self::BLOCKORDERSATTRIBUTE) == \Riki\Customer\Model\Shosha::BLOCKORDERS_YES) {
                return true;
            }
        }

        return false;
    }
}