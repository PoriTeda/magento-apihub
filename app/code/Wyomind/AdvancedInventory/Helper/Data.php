<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_indexer = false;
    protected $_productTypes = ['simple', 'virtual', 'downloadable', 'virtual', 'giftcard'];
    protected $_productRepository = null;
    protected $_envConfig = [];
    protected $_configReader = null;

    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\DeploymentConfig\Reader $configReader,
        \Wyomind\Core\Helper\Data $coreHelper
    ) {
    
        $this->_productRepository = $productRepository;
        $this->_configReader = $configReader;
        $this->_coreHelper = $coreHelper;
    }

    public function getIndexer()
    {
        return $this->_indexer;
    }

    public function setIndexer($indexer)
    {
        $this->_indexer = $indexer;
    }

    public function getProductTypes()
    {
        return $this->_productTypes;
    }

    public function isProductAllowed($productId)
    {
        $product = $this->_productRepository->getById($productId);
        return in_array($product->getTypeId(), $this->_productTypes);
    }

    public function qtyFormat(
        $qty,
        $useDecimal = false
    ) {
    
        if (!$useDecimal) {
            return number_format($qty, 0, ".", "");
        }
        return $qty;
    }

    public function shorten($string)
    {
        if (strlen($string) <= 10) {
            return $string;
        }

        return substr($string, 0, 10) . '&hellip;';
    }

    public function getConnectionConfig()
    {
        if ($this->_coreHelper->moduleIsEnabled("Magento_Enterprise")) {
            if (empty($this->_envConfig)) {
                $this->_envConfig = $this->_configReader->load(\Magento\Framework\Config\File\ConfigFilePool::APP_ENV);
            }
            return $this->_envConfig['db']['connection'];
        } else {
            return [];
        }
    }

    public function getConnection($db)
    {
        if (isset($this->getConnectionConfig()[$db])) {
            if ($this->getConnectionConfig()[$db]['active'] == 1) {
                return $this->getConnectionConfig()[$db]['dbname'].".";
            } else {
                return "";
            }
        } else {
            return "";
        }
    }
    
    public function getDefaultConnection()
    {
        return $this->getConnection("default");
    }
    
    public function getSalesConnection()
    {
        return $this->getConnection("sales");
    }
    
    public function getCheckoutConnection()
    {
        return $this->getConnection("checkout");
    }
}
