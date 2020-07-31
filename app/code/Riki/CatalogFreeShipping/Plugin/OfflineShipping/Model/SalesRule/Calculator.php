<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  RIKI
 * @package   Riki_CatalogFreeShipping
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\CatalogFreeShipping\Plugin\OfflineShipping\Model\SalesRule;

/**
 * Class Calculator
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Calculator
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Rule collection
     *
     * @var \Riki\CatalogFreeShipping\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $ruleCollection;

    protected $_productAttributeRepository;

    protected $phCodeArr = [];

    /**
     * Constructor
     *
     * @param \Riki\CatalogFreeShipping\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory Rule collection
     * @param \Magento\Store\Model\StoreManagerInterface                           $storeManager          Store manager
     *
     * @return self
     */
    public function __construct(
        \Riki\CatalogFreeShipping\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\Attribute\Repository $productAttributeRepository
    ) {
        $this->storeManager   = $storeManager;
        $this->ruleCollection = $ruleCollectionFactory;
        $this->_productAttributeRepository = $productAttributeRepository;

    }//end __construct()


    /**
     * Check quote item is free shipping
     *
     * @param \Magento\OfflineShipping\Model\SalesRule\Calculator $subject Sales Rule Calculator
     * @param \Closure                                            $proceed proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem        $item    Quote item
     *
     * @return mixed
     */
    public function aroundProcessFreeShipping(
        \Magento\OfflineShipping\Model\SalesRule\Calculator $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item
    ) {

        $result = $proceed($item);

        if($item->getFreeShipping())
            return $result;

        $quote           = $item->getQuote();
        $store           = $this->storeManager->getStore($quote->getStoreId());
        $websiteId       = $store->getWebsiteId();
        $customerGroupId = $quote->getCustomerGroupId();
        $membership      = '';
        $product         = $item->getProduct();
        $phCodesId         = $product->getPhCode();
        $phCodes = [];

        if(!empty($phCodesId)){

            $phCodesId = explode(',', $phCodesId);

            /** @var \Magento\Eav\Api\Data\AttributeOptionInterface[] $phCodeOptions */
            $phCodeOptions = $this->_productAttributeRepository->get('ph_code')->getOptions();

            foreach($phCodeOptions as $phCodeOption){
                if(in_array($phCodeOption->getValue(), $phCodesId)){
                    $phCodes[] = $phCodeOption->getLabel();
                }
            }
        }

        $sku = $product->getSku();

        if (!$quote->getCustomerIsGuest()) {
            $customer = $quote->getCustomer();

            $membershipAttribute = $customer->getCustomAttribute('membership');
            if (!is_null($membershipAttribute)) {
                $membership = $membershipAttribute->getValue();
            }
        }

        /** @var \Riki\CatalogFreeShipping\Model\ResourceModel\Rule\Collection $collection */
        $collection = $this->ruleCollection->create();
        $collection->addActiveFilter()
            ->addWebsiteToFilter($websiteId)
            ->addCustomerGroupToFilter($customerGroupId)
            ->addMembershipToFilter(explode(',', $membership))
            ->addProductAttributeToFilter('ph_code', $phCodes)
            ->addProductAttributeToFilter('sku', $sku)
            ->setPageSize(1);

        if ($collection->getSize() > 0){
            $item->setFreeShipping(true);
            $item->setFreeDeliveryWbs($collection->getFirstItem()->getWbs());
        }

        return $result;

    }//end aroundProcessFreeShipping()


}//end class
