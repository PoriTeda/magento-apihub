<?php
namespace Riki\Customer\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $_paymentConfig;
    /**
     * @var CollectionFactory
     */
    protected $_groupCustomerFactory;

    /**
     * @var CollectionFactory
     */
    protected $_orderStatus;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Config constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCustomerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Sales\Model\Config\Source\Order\Status $orderStatus
     * @param \Magento\Payment\Model\Config $paymentConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCustomerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Config\Source\Order\Status $orderStatus,
        \Magento\Payment\Model\Config $paymentConfig
    )
    {
        $this->_storeManager = $storeManager;

        $this->_groupCustomerFactory = $groupCustomerFactory;

        $this->_orderStatus = $orderStatus;

        $this->_paymentConfig = $paymentConfig;

        parent::__construct($context);
    }

    /**getOptionCustomerGroupArray
     *
     * @return array
     */
    public function getOptionCustomerGroupArray(){
        $options = $this->_groupCustomerFactory->create()->toOptionArray();
        $optionsGrid = array();

        foreach($options as $option){
            $optionsGrid[$option['value']] = $option['label'];
        }

        return $optionsGrid;
    }

    /**
     * getOptionCustomerWebsiteArray
     *
     * @return array
     */
    public function getOptionCustomerWebsiteArray(){

        $websites = $this->_storeManager->getWebsites();
        $optionGrid = array();

        foreach($websites as $website){
            $optionGrid[$website->getId()] = $website->getName();
        }
        return $optionGrid;
    }

    /**
     * getOptionOrderStatusArray
     *
     * @return array
     */
    public function getOptionOrderStatusArray(){
        $orderStatuses = $this->_orderStatus->toOptionArray();

        $optionGrid = array();

        foreach($orderStatuses as $key => $orderStatus){
            if($key == 0){
                continue;
            }
            $optionGrid[$orderStatus['value']] = $orderStatus['label'];
        }
        return $optionGrid;

    }

    /**
     * GetOrderPaymentMethodArray
     *
     * @return array
     */
    public function getOrderPaymentMethodArray(){
        $paymentMethods = $this->_paymentConfig->getActiveMethods();

        $optionGrid = array();

        foreach($paymentMethods as $paymentMethodCode => $paymentMethod){
            $optionGrid[$paymentMethodCode] = $this->getPaymentTitle($paymentMethodCode);
        }
        return $optionGrid;

    }

    /**
     * @param $paymentCode
     * @return mixed
     */
    public function getPaymentTitle($paymentCode)
    {
        $configPath = 'payment/'.$paymentCode.'/title';
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($configPath, $storeScope);
    }
}