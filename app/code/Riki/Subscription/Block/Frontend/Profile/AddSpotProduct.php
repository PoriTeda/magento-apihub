<?php

namespace Riki\Subscription\Block\Frontend\Profile;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Layer;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Framework\Registry;
use Magento\Catalog\Block\Product\Context;
use Magento\Search\Model\QueryFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\Layer\FilterList;

class AddSpotProduct extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var string
     */
    protected $_toolbarBlockName = 'spot_product_list_toolbar';
    /**
     * @var Layer
     */
    protected $_catalogLayer;

    /**
     * Product Collection
     *
     * @var AbstractCollection
     */
    protected $_productCollection;

    /**
     * @var UrlHelper
     */
    protected $_urlHelper;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var QueryFactory
     */
    protected $_queryFactory;

    protected $_categoryFilter;

    protected $_filterList;

    /**
     * @var \Magento\Catalog\Model\Layer\Filter\AbstractFilter[]
     */
    protected $filters = [];

    public function __construct(
        Context $context,
        Resolver $layerResolver,
        UrlHelper $urlHelper,
        QueryFactory $queryFactory,
        FilterList $filterList,
        array $data = []
    ) {
        $this->_filterList = $filterList;
        $this->_registry = $context->getRegistry();
        $this->_urlHelper = $urlHelper;
        $this->_catalogLayer = $layerResolver->get();
        $this->_queryFactory = $queryFactory;
        parent::__construct($context, $data);
    }

    public function getProductCollection()
    {
        if ($this->_productCollection === null) {
            $this->_productCollection = $this->_catalogLayer->getProductCollection();
            $this->_productCollection->addFieldToFilter('spot_allow_subscription', ['eq' => 1]);

            if ($this->getRequest()->getParam('q', false)) {
                $query = $this->_queryFactory->get()->getQueryText();
                $this->_productCollection->addAttributeToFilter('name', ['like' => '%' . $query . '%']);
            }
            $filters = $this->getFilterList();
            if ($filters) {
                foreach ($filters as $filter) {
                    $key = $filter->getAttributeModel()->getAttributeCode();
                    $value = $this->_request->getParam($key, false);
                    if ($value) {
                        $filter->apply($this->getRequest());
                    }
                }
                $this->getLayer()->apply();
            }
        }

        return $this->_productCollection;
    }

    /**
     * Retrieve list toolbar HTML
     *
     * @return string
     */
    public function getToolbarHtml()
    {
        return $this->getChildHtml($this->_toolbarBlockName, false);
    }

    protected function _beforeToHtml()
    {
        $toolbar = $this->getLayout()->getBlock($this->_toolbarBlockName);

        // called prepare sortable parameters
        $collection = $this->getProductCollection();
        $collection = $collection->addAttributeToFilter('spot_allow_subscription', ['eq' => 1]);
        $size = $collection->getSize();
        // use sortable parameters
        $orders = $this->getAvailableOrders();
        if ($orders) {
            $toolbar->setAvailableOrders($orders);
        }
        $sort = $this->getSortBy();
        if ($sort) {
            $toolbar->setDefaultOrder($sort);
        }
        $dir = $this->getDefaultDirection();
        if ($dir) {
            $toolbar->setDefaultDirection($dir);
        }
        $modes = $this->getModes();
        if ($modes) {
            $toolbar->setModes($modes);
        }

        // set collection to toolbar and apply sort
        $toolbar->setCollection($collection);

        //$this->getProductCollection()->load();

        return parent::_beforeToHtml();
    }

    /**
     * Get post parameters
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getAddSpotToSubPostParams(\Magento\Catalog\Model\Product $product)
    {
        $url = $this->getUrl('subscriptions/profile/confirmspotproduct');
        return [
            'action' => $url,
            'data' => [
                'product_id' => $product->getEntityId(),
                \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED =>
                    $this->_urlHelper->getEncodedUrl($url),
            ]
        ];
    }

    public function getProfileId()
    {
        return $this->_registry->registry('profile_id');
    }

    public function getFilterList()
    {
        return $this->_filterList->getFilters($this->_catalogLayer);
    }

    /**
     * Get layer object
     *
     * @return \Magento\Catalog\Model\Layer
     */
    public function getLayer()
    {
        return $this->_catalogLayer;
    }
}