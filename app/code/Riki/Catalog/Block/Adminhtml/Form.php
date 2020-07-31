<?php
namespace Riki\Catalog\Block\Adminhtml;
/**
 * Class Form
 * @package Riki
 */
class Form extends \Magento\Framework\View\Element\Template
{
    /**
     * @var
     */
    protected $scopeConfig;
    /**
     * @var
     */
    protected $_product;
    /**
     * Selling path config
     */
    protected $MinSellingConfig;
    /**
     * @var mixed
     */
    protected $MaxSellingConfig;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var AuthorizationInterface
     */
    protected $_auth;

    const XML_MIN_PRICE_SELLING = 'catalog/selling_price/minimum_price';
    const XML_MAX_PRICE_SELLING = 'catalog/selling_price/maximun_price';
    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Locale\Format $format
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    )
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->scopeConfig = $context->getScopeConfig();
        $this->MinSellingConfig = $this->scopeConfig->getValue(self::XML_MIN_PRICE_SELLING,$storeScope);
        $this->MaxSellingConfig = $this->scopeConfig->getValue(self::XML_MAX_PRICE_SELLING,$storeScope);
        $this->_coreRegistry = $registry;
        $this->_auth = $context->getAuthorization();
        parent::__construct($context, $data);
    }
    /**
     * @return array
     */
    public function getConfigMinMaxPrice(){
        $array = array();
        $array['min_price'] = $this->MinSellingConfig;
        $array['max_price'] = $this->MaxSellingConfig;
        return $array;
    }
    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = $this->_coreRegistry->registry('product');
        }
        return $this->_product;
    }

    /**
     * @return mixed
     */
    public function getAuthAddPrice(){
        if($this->_auth->isAllowed('Magento_Sales::add_gps_price')){
            return true;
        }else{
            return false;
        }
    }
    /**
     * @return bool
     */
    public function getAuthEditPrice(){
        if($this->_auth->isAllowed('Magento_Sales::edit_gps_price')){
            return true;
        }else{
            return false;
        }
    }/**
     * @return bool
     */
    public function getAuthDeletePrice(){
        if($this->_auth->isAllowed('Magento_Sales::delete_gps_price')){
            return true;
        }else{
            return false;
        }
    }
}