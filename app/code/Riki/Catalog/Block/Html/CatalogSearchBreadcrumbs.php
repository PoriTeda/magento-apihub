<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Catalog\Block\Html;

class CatalogSearchBreadcrumbs extends \Magento\Theme\Block\Html\Breadcrumbs
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlInterface;
    /**
     * @var \Magento\CatalogSearch\Helper\Data
     */
    protected $_catalogSearchData;
    /**
     * @var \Riki\CatalogSearch\Helper\Data
     */
    protected $dataHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\CatalogSearch\Helper\Data $catalogSearch,
        array $data = [],
        \Riki\CatalogSearch\Helper\Data $dataHelper
    ) {
        $this->_productRepository = $productRepository;
        $this->_urlInterface      = $context->getUrlBuilder();
        $this->_catalogSearchData     = $catalogSearch;
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
    }

    /**
     * get string query search
     *
     * @return \Magento\Framework\Phrase
     */
    public function getSearchQueryText()
    {
        return __("Search results for: '%1'", $this->_catalogSearchData->getEscapedQueryText());
    }

    protected function _prepareLayout()
    {
        $this->addCrumb(
            'home',
            [
                'label' => __('HomePage'),
                'title' => __('Go to Home Page'),
                'link' => $this->_storeManager->getStore()->getBaseUrl()
            ]
        );
        $this->addCrumb(
            'product',
            [
                'label' => $this->getSearchQueryText(),
                'title' => $this->getSearchQueryText(),
                'link'  => $this->cleanSearchUrl($this->_urlInterface->getCurrentUrl())
            ]
        );

        return parent::_prepareLayout();
    }

    /**
     * Clean search URL
     *
     * @param string $url
     *
     * @return string
     */
    protected function cleanSearchUrl($url)
    {
        if (strpos($url, '?q=') !== false) {
            // Pick the query string
            $explodeUrl = explode('?q=', $url);
            return $explodeUrl[0] . '?q=' . $this->dataHelper->clean($explodeUrl[1]);
        }

        return $url;
    }
}