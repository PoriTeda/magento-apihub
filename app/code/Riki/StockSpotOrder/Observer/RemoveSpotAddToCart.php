<?php
/**
 * StockSpotOrder
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\StockSpotOrder
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\StockSpotOrder\Observer;

use Magento\Bundle\Model\ResourceModel\Indexer\Stock;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Riki\ProductStockStatus\Model\StockStatusFactory;
use Magento\CatalogInventory\Model\Stock\ItemFactory;
use Magento\Checkout\Model\Session ;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

/**
 * StockSpotOrder
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\StockSpotOrder
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

class RemoveSpotAddToCart implements ObserverInterface
{
    /**
     * Object Model Session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Object UrlInterface
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * Object ManagerInterface
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * RemoveSpotAddToCart constructor.
     *
     * @param Session               $checkoutSession  checkoutSession
     * @param ScopeConfigInterface  $scopeConfig      scopeConfig
     * @param ManagerInterface      $managerInterface managerInterface
     * @param StoreManagerInterface $storeManager     storeManager
     * @param ManagerInterface      $messageManager   messageManager
     * @param UrlInterface          $urlInterface     urlInterface
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $managerInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $urlInterface
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->messageManager   = $messageManager;
        $this->urlInterface     = $urlInterface;
    }


    /**
     * Process data
     *
     * @param \Magento\Framework\Event\Observer $observer observer
     *
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //check quote exist
        $quote = $this->checkoutSession->getQuote();
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return false;
        }

        //get all cart item
        $allItems = $quote->getAllItems();
        foreach ($allItems as $quoteItem) {
            $productItem = $quoteItem->getProduct();
            $mProduct    = $productItem->load($productItem->getId());
            $allowSaleSpot = $mProduct->getAllowSpotOrder();
            if (!$productItem->getData(\Riki\StockSpotOrder\Helper\Data::SKIP_CHECk_ALLOW_SPOT_ORDER_NAME) && $mProduct && !$allowSaleSpot) {
                $this->messageManager->addError(__('You cannot add "%1" to the cart.', $quoteItem->getName()));
                $url = $this->urlInterface->getUrl('checkout/cart');
                return $observer->getControllerAction()
                    ->getResponse()
                    ->setRedirect($url);
            }
        }
        return $this;
    }

}