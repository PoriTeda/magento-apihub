<?php

namespace Riki\Checkout\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;

class CombineCartItems implements ObserverInterface
{
    const IS_SKIP_COMBINE_QTY = 'is_skip_combine_qty';

    /**
     * @var \Magento\Checkout\Model\Cart $_checkoutSession
     */
    protected $_cartModel = null;
    /**
     * @var Quote|null
     */
    protected $_quote = null;
    /**
     * @var \Magento\Customer\Model\Session $_customerSession
     */
    protected $_customerSession = null;


    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->_cartModel = $cart;
        $this->_customerSession = $customerSession;
    }

    /**
     * Get active quote
     *
     * @return Quote
     */
    public function getQuote()
    {
        if (null === $this->_quote) {
            $this->_quote = $this->_cartModel->getQuote();
        }
        return $this->_quote;
    }

    /**
     *  Combine cart items which already slip on multi checkout
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* do nothing when customer does not login */
        if (!$this->_customerSession->isLoggedIn()) {
            if ('checkout_cart_index' == $observer->getRequest()->getFullActionName()) {
                $quote = $this->getQuote();
                $quote->getBillingAddress();
                $quote->getShippingAddress()->setCollectShippingRates(true);
                $quote->collectTotals();
            }
            return $this;
        }
        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->getQuote();
            if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
                return false;
            }
        } catch (\Exception $exception) {
            throw new LocalizedException(
                __("Unable to load active quote in combine cart items , detail :" . $exception->getMessage())
            );
        }
        $quote->setData('is_multiple_shipping', 0);
        $arrBundle = [];
        /** @var \Magento\Quote\Model\Quote\Item  $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            if (!$this->isNeedToCombineQty($quoteItem)) {
                continue;
            }

            $productType = $quoteItem->getProduct()->getTypeId();

            // combine bundle item
            if ($productType == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                if (!isset($arrBundle[$quoteItem->getProductId()]) || $arrBundle[$quoteItem->getProductId()] == null) {
                    $arrBundle[$quoteItem->getProductId()] = $quoteItem->getId();
                } else {
                    $idItem = $arrBundle[$quoteItem->getProductId()];
                    $parentItem = $quote->getItemById($idItem);
                    $combineQty = (int) $parentItem->getQty() + (int) $quoteItem->getQty();
                    $parentItem->setQty($combineQty);
                    $parentItem->setData('qty_combine', $parentItem->getQty());

                    try {
                        $parentItem->save();
                        $quoteItem->delete();
                    } catch (\Exception $e) {
                        throw new LocalizedException(
                            __("Unable to remove unnessary quote item , detail :" . $e->getMessage())
                        );
                    }
                }
                continue ;
            }

            //combine cart for simple
            if (!$quoteItem->hasData('slip_parent_item_id') || $quoteItem->getData('slip_parent_item_id') == null) {
                continue ;
            } else {
                /** may be we will detect parent id from here */
                /** @var \Magento\Quote\Model\Quote\Item $parentItem */
                try {
                    $parentItem = $quote->getItemById($quoteItem->getData('slip_parent_item_id'));
                    if ($parentItem) {
                        $parentProductType = $parentItem->getProduct()->getTypeId();
                    } else {
                        continue;
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    throw new LocalizedException(
                        __("Could not load parent item from split parent item id , detail :" . $e->getMessage())
                    );
                }

                switch ($parentProductType) {
                    case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
                        /* good that is slip product , need to be deleted */
                        if ($parentItem->getParentItemId() == null) {
                            $parentItem->setQty(floatval($parentItem->getQty()) + floatval($quoteItem->getQty()));
                            try {
                                $parentItem->setData('qty_combine', $parentItem->getQty());
                                $parentItem->save();
                                $quoteItem->delete();
                            } catch (\Exception $e) {
                                throw new LocalizedException(
                                    __("Unable to remove unnessary quote item , detail :" . $e->getMessage())
                                );
                            }
                        }
                        break;
                    default:
                        /* this quote item is belong to configurable / bundle / group product , so no need to delete them */
                        break;
                }
            }
        }
        try {
            $quote->getBillingAddress();
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->collectTotals();
            $quote->save();
            //clean shipping address (multi)
            $this->cleanShippingAddress($quote);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Unable to recollect total , detail :" . $e->getMessage()));
        }
    }

    /**
     * @param Quote\Item $quoteItem
     * @return bool
     */
    public function isNeedToCombineQty(\Magento\Quote\Model\Quote\Item $quoteItem)
    {
        if ($quoteItem->getData(self::IS_SKIP_COMBINE_QTY)) {
            return false;
        }

        /* detect quote item is split from multi checkout or not */
        if ($quoteItem->getProduct()->getIsVirtual()) {
            return false;
        }
        /** don't check case configurable  */
        $productType = $quoteItem->getProduct()->getTypeId();
        if ($productType != \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
            && $productType != \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE
        ) {
            return false;
        }

        return true;
    }

    /**
     * Clean shipping address
     *
     * @param $quote
     *
     * @throws \Exception
     */
    protected function cleanShippingAddress($quote)
    {
        /** @var  $quoteAddressObject \Magento\Quote\Model\Quote\Address */
        foreach ($quote->getAddressesCollection() as $quoteAddressObject) {
            try {
                if (!$quoteAddressObject->getAddressType()) {
                    $quoteAddressObject->delete();
                }
            } catch (\Exception $exception) {
                $this->logger->critical(__("Could not clean quote address for quote id {$quote->getId()}"));
                throw $exception;
            }
        }
    }
}
