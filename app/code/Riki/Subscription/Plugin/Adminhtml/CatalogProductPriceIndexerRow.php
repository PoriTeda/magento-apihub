<?php

namespace Riki\Subscription\Plugin\Adminhtml;

class CatalogProductPriceIndexerRow
{
    protected $_productFactory;

    protected $_profileResource;

    protected $_processor;

    protected $_profile;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile $profileResource,
        \Riki\Subscription\Model\Indexer\ProfileSimulator\Processor $processor,
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $profile
    ) {
        $this->_productFactory = $productFactory;
        $this->_profileResource = $profileResource;
        $this->_profile = $profile;
        $this->_processor = $processor;
    }

    public function aroundExecute(
        \Magento\Catalog\Model\Indexer\Product\Price\Action\Row $subject,
        \Closure $process,
        $id = null
    ) {
        $profileIds = $this->_profileResource->getProfileIdByProductId($id);
        if ($profileIds) {
            $this->_profile->removeDataProfile($profileIds);
            $this->_processor->reindexList($profileIds);
        }
        $result = $process($id);

        return $result;
    }

}