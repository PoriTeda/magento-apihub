<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Plugin\GoogleTagManager\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\TestFramework\Event\Magento;
use Magento\Framework\Session\SessionManagerInterface;

class SetGoogleAnalyticsOnCartAddObserver
{
	/**
	 * @var \Magento\GoogleTagManager\Helper\Data
	 */
	protected $helper;

	/**
	 * @var \Magento\Checkout\Model\Session
	 */
	protected $checkoutSession;

	/**
	 * @var \Magento\Framework\Registry
	 */
	protected $registry;
	/**
	 * @var \Magento\Framework\App\Response\RedirectInterface
	 */
	protected $redirect;

	/**
	 * @var CategoryManagement
	 */
	protected $_categoryManagement;
	/**
	 * @var Escaper
	 */
	protected $escaper;
	protected $_helperTag;
	/**
	 * @var SessionManagerInterface
	 */
	protected $sessionManager;
	/**
	 * @param \Magento\GoogleTagManager\Helper\Data $helper
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Framework\Registry $registry
	 */
	public function __construct(
		\Magento\GoogleTagManager\Helper\Data $helper,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Framework\App\Response\RedirectInterface $redirect,
		\Magento\Framework\Escaper $escaper,
		\Riki\GoogleTagManager\Helper\Data $helperTag,
		\Magento\Framework\Registry $registry,
		SessionManagerInterface $sessionManager
	) {
		$this->redirect = $redirect;
		$this->escaper = $escaper;
		$this->_helperTag = $helperTag;
		$this->sessionManager  = $sessionManager;
		parent::__construct($helper, $checkoutSession, $registry);
	}

	/**
	 * Fired by sales_quote_product_add_after event
	 *
	 * @param \Magento\Framework\Event\Observer $observer
	 * @return $this
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		if (!$this->helper->isTagManagerAvailable()) {
			return $this;
		}

		$arrItems = $observer->getEvent()->getItems();
		if (is_array($arrItems) && isset($arrItems[0])
			&& ($arrItems[0] instanceof \Magento\Framework\DataObject)) {
			if (($arrItems[0]->getQuote() instanceof \Magento\Framework\DataObject)
				&& $arrItems[0]->getQuote()->getData('is_simulator') === true) {
				return $this;
			}
		}

		// get latest qty of cart
		$lastValues = [];

		if ($this->checkoutSession->hasData(
			\Magento\GoogleTagManager\Helper\Data::PRODUCT_QUANTITIES_BEFORE_ADDTOCART
		)) {
			$lastValues = $this->checkoutSession->getData(
				\Magento\GoogleTagManager\Helper\Data::PRODUCT_QUANTITIES_BEFORE_ADDTOCART
			);
		}

		//product for one times add cart
		$itemsAdded = $observer->getEvent()->getItems();
		$this->_productAddedOneTimes($itemsAdded,$lastValues);

		// product cart default
		$products = $this->registry->registry('GoogleTagManager_products_addtocart');
		if (!$products) {
			$products = [];
		}

		$items = $observer->getEvent()->getItems();
		/** @var \Magento\Quote\Model\Quote\Item $quoteItem */
		foreach ($items as $quoteItem) {

			// default id items
			$id = $quoteItem->getProductId();
			$parentQty = 1;
			$price = $quoteItem->getProduct()->getPrice();
			switch ($quoteItem->getProductType()) {
				case 'configurable':
				case 'bundle':
					break ;
				case 'grouped':
					$id = $quoteItem->getOptionByCode('product_type')->getProductId() . '-'
						. $quoteItem->getProductId();
				// no break;
				default:
					if ($quoteItem->getParentItem()) {
						$parentQty = $quoteItem->getParentItem()->getQty();
						// id items for sub items
						$id = $quoteItem->getId() . '-' .
							$quoteItem->getParentItem()->getProductId() . '-' .
							$quoteItem->getProductId();

						if ($quoteItem->getParentItem()->getProductType() == 'configurable') {
							$price = $quoteItem->getParentItem()->getProduct()->getPrice();
						}
					}
					if ($quoteItem->getProductType() == 'giftcard') {
						$price = $quoteItem->getProduct()->getFinalPrice();
					}
					// check  qty added before
					$oldQty = (array_key_exists($id, $lastValues)) ? $lastValues[$id] : 0;
					$finalQty = ($parentQty * $quoteItem->getQty()) - $oldQty;
					if ($finalQty != 0) {
						$products[] = [
							'sku'   => $quoteItem->getSku(),
							'name'  => $quoteItem->getName(),
							'price' => $price,
							'qty'   => $finalQty
						];
					}
			}
		}
		$this->registry->unregister('GoogleTagManager_products_addtocart');
		$this->registry->register('GoogleTagManager_products_addtocart', $products);
		$this->checkoutSession->unsetData(\Magento\GoogleTagManager\Helper\Data::PRODUCT_QUANTITIES_BEFORE_ADDTOCART);

		return $this;
	}

	/**
	 * set items for add one times
	 *
	 * @param $items
	 * @param $redirectUrl
	 */
	protected function _productAddedOneTimes($items,$lastValues){


		$productsOneTimes   = $this->sessionManager->getData('GoogleTagManager_products_addtocart_session_onetimes');
		$productsOneTimesQty = $this->sessionManager->getData('GoogleTagManager_products_addtocart_onetimes_last_value');

		if (!$productsOneTimes) {
			$productsOneTimes = [];
		}
		if (!is_array($productsOneTimesQty)) {
			$this->sessionManager->setData('GoogleTagManager_products_addtocart_onetimes_last_value',$lastValues);
		}
		// items just add to cart
		if($items){
			foreach ($items as $quoteItem) {
				$productsOneTimes[] = $quoteItem->getProductType().'_'.$quoteItem->getProductId();
			}
		}
		$this->sessionManager->setData('GoogleTagManager_products_addtocart_session_onetimes',$productsOneTimes);


	}

}
