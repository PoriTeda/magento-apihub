<?php
namespace  Riki\VariationProducts\Block\Product ;


class ProductVariation extends \Magento\Catalog\Block\Product\AbstractProduct implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * @var Collection
     */
    protected $_itemCollection;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Catalog product visibility
     *
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_catalogProductVisibility;

    /**
     * Checkout cart
     *
     * @var \Magento\Checkout\Model\ResourceModel\Cart
     */
    protected $_checkoutCart;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $productStatus;
    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $productVisibility;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\ResourceModel\Cart $checkoutCart,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->_checkoutCart = $checkoutCart;
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->_checkoutSession = $checkoutSession;
        $this->moduleManager = $moduleManager;
        $this->_storeManager = $context->getStoreManager();
        $this->_scopeConfig = $context->getScopeConfig();
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->productRepository    = $productRepository;
        $this->imageHelper = $context->getImageHelper();
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @return $this
     */
    protected function _prepareData()
    {
        $product = $this->_coreRegistry->registry('product');
        $_attributeValueReference =$product->getResource()->getAttribute('master_reference')->getFrontend()->getValue($product);
        if ($_attributeValueReference) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('master_reference', $_attributeValueReference, 'eq')
                ->addFilter('entity_id', $product->getId(), 'neq')
                ->addFilter('status', $this->productStatus->getVisibleStatusIds(), 'eq')
                ->addFilter('visibility', 4, 'eq')
                ->setPageSize(20)
                ->setCurrentPage(1)
                ->create();

            $searchResults = $this->productRepository->getList($searchCriteria);
            if (!$searchResults->getTotalCount()) {
                return false;
            }
            $this->_itemCollection = $searchResults->getItems();
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->_prepareData();
        return parent::_beforeToHtml();
    }

    /**
     * @return Collection
     */
    public function getItems()
    {
        return $this->_itemCollection;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [];
        return $identities;
    }

    /**
     * Find out if some products can be easy added to cart
     *
     * @return bool
     */
    public function canItemsAddToCart()
    {
        foreach ($this->getItems() as $item) {
            if (!$item->isComposite() && $item->isSaleable() && !$item->getRequiredOptions()) {
                return true;
            }
        }
        return false;
    }

    public function getGalleryImages($item)
    {
        $product = $this->productRepository->getById($item->getId());
        $images = $product->getMediaGalleryEntries();
        foreach ($images as $image) {
            $type = $image->getTypes();
            if(in_array('swatch_image', $type)) {
                $dataImage = [];
                $dataImage['label'] = $image->getLabel();
                $dataImage['url'] = $this->imageHelper->init($product, 'swatch_image')
                    ->setImageFile($image->getFile())
                    ->getUrl();
                return $dataImage;
            }
        }

        return false;
    }

}