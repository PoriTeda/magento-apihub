<?php
namespace Riki\GoogleTagManager\Block;
use Riki\GoogleTagManager\Helper\Data ;
/**
 * Class ListJson
 * @package Riki\GoogleTagManager\Block
 */
class ListJson extends \Magento\GoogleTagManager\Block\ListJson {
    protected $_googleTagHelper;

    protected $_escaper;
    /**
     * ListJson constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Helper\Cart $checkoutCart
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory
     * @param \Magento\GoogleTagManager\Model\Banner\Collector $bannerCollector
     * @param Data $googleTagHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Helper\Cart $checkoutCart,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory,
        \Magento\GoogleTagManager\Model\Banner\Collector $bannerCollector,
        Data $googleTagHelper,
        array $data = []
    ) {

        parent::__construct($context,$helper,$jsonHelper,$registry,$checkoutSession, $customerSession,$checkoutCart,$layerResolver,$moduleManager,$request,$bannerColFactory,$bannerCollector,$data);
        $this->_googleTagHelper = $googleTagHelper;
        $this->_escaper = $context->getEscaper();
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getProductPrice(\Magento\Catalog\Model\Product $product)
    {
        return $this->_googleTagHelper->getProductPrice($product);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getProductCategoriesName(\Magento\Catalog\Model\Product $product)
    {
        return $this->_googleTagHelper->getCategoryNames($product);
    }

    /**
     * @param $str
     * @return string
     */
    public function escapeJsQuoteCustom($str)
    {
        //escape single quote
        $encodedStr = $this->_escaper->escapeJsQuote($str);
        //escape double quote
        $encodedStr = str_replace(chr(34), chr(92).chr(34), $encodedStr );
        return mb_strcut($encodedStr,0,120);
    }

    /**
     * @return string
     */
    public function getJsonData()
    {
        $product = $this->getCurrentProduct();
        $price = $this->getProductPrice($product);
        $dimension40 = intval($price) ? 'NO' : 'YES';
        $dimension41 = intval($price) ? 'NO' : 'YES';
        $dimension24 = 'SPOT Product Purchase'; // always spot in product detail
        $quantity = 1;
        $category = $this->getProductCategoriesName($product);
        $data = [
                "name"=> $product->getName(),
                "id"=> $product->getSku(),
                "dimension24"=> $dimension24,
                "dimension40"=> $dimension40,
                "dimension41"=> $dimension41,
                "dimension56"=> $product->getTypeId(),
                "quantity"=>$quantity,
                "price"=>  number_format(intval($price), 2,'.',''),
                "category"=> $category,
                "brand" => "",
                "variant"=>""
        ];
        return \Zend_Json::encode($data);
    }

    /**
     * Get product collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection|null
     */
    public function _getProductCollection()
    {
        if($this->getListBlock()) {
            return parent::_getProductCollection();
        }else {
            return $this->_productCollection;
        }
    }
}