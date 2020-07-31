<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\CatalogSearch\Controller\Result;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Search\Model\QueryFactory;

class Index extends \Magento\CatalogSearch\Controller\Result\Index
{
    /**
     * @var QueryFactory
     */
    private $_queryFactory;

    /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
    private $layerResolver;

    /**
     * Data helper
     *
     * @var \Riki\CatalogSearch\Helper\Data
     */
    protected $dataHelper;


    public function __construct(
        Context $context,
        Session $catalogSession,
        StoreManagerInterface $storeManager,
        QueryFactory $queryFactory,
        Resolver $layerResolver,
        \Riki\CatalogSearch\Helper\Data $dataHelper
    ) {
        parent::__construct(
            $context,
            $catalogSession,
            $storeManager,
            $queryFactory,
            $layerResolver
        );
        $this->_queryFactory = $queryFactory;
        $this->layerResolver = $layerResolver;
        $this->dataHelper = $dataHelper;
    }


    /**
     * Display search result
     *
     * @return void
     */
    public function execute()
    {
        $this->layerResolver->create(Resolver::CATALOG_LAYER_SEARCH);
        /* @var $query \Magento\Search\Model\Query */
        $query = $this->_queryFactory->get();

        $query->setStoreId($this->_storeManager->getStore()->getId());

        $queryText = $this->dataHelper->clean($query->getQueryText()); // Strip data

        if ($queryText != '') {
            if ($this->_objectManager->get('Magento\CatalogSearch\Helper\Data')->isMinQueryLength()) { // @codingStandardsIgnoreLine
                $query->setId(0)->setIsActive(1)->setIsProcessed(1);
            } else {
                $query->saveIncrementalPopularity();

                if ($query->getRedirect()) {
                    $this->getResponse()->setRedirect($query->getRedirect());
                    return;
                }
            }

            $this->_objectManager->get('Magento\CatalogSearch\Helper\Data')->checkNotes(); // @codingStandardsIgnoreLine

            $this->_view->loadLayout();
            $this->_view->renderLayout();
        } else {
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
        }
    }
}
