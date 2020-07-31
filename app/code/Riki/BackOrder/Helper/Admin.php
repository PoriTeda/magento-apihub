<?php
namespace Riki\BackOrder\Helper;

use \Riki\BackOrder\Helper\Data as BackOrderHelper;
use \Magento\Framework\Exception\LocalizedException;

class Admin extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var \Magento\Backend\Model\Session\Quote  */
    protected $_session;

    /** @var Data  */
    protected $_helper;

    protected $_currentBackOrderType;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Data $helper
     * @param \Magento\Framework\Message\ManagerInterface $message
     * @param \Magento\Backend\Model\Session\Quote $session
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        BackOrderHelper $helper,
        \Magento\Framework\Message\ManagerInterface $message,
        \Magento\Backend\Model\Session\Quote $session
    )
    {
        $this->_session = $session;
        $this->_helper = $helper;
        $this->_messageManager = $message;

        parent::__construct($context);
    }

    /**
     * validate a product with back order rule
     *
     * @param $product
     * @param int $requestQty
     * @return bool|string
     * @throws LocalizedException
     */
    public function validateProduct($product, $requestQty = 0)
    {
        $needToValidateProduct = true;
        //validate machine product
        if ($product instanceof \Magento\Catalog\Model\Product) {
            if ($product->getData('ampromo_rule_id')
                || $product->getCustomOption('prize_id')
                || $product->getCustomOption('is_free_machine')
            ) {
                return true;
            }
            $productCustomOption = $product->getCustomOption('machine_type_id');
            if ($productCustomOption && $productCustomOption->getValue()) {
                $needToValidateProduct = false;
            }

            $product = $product->getId();
        }

        $productBackOrderStatus = $this->_helper->getBackOrderStatusByProductId($product, $requestQty);

        if ($needToValidateProduct && !$this->_helper->isAvailableStock($productBackOrderStatus)) {
            return BackOrderHelper::BACK_ORDER_OVER_LIMIT_MESSAGE;
        }

        if ($this->getBackOrderTypeOfCurrentCart()) {
            $quote = $this->_session->getQuote();

            $buyQty = $requestQty;

            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($quote->getAllItems() as $item) {
                if ($item->getProductId() == $product) {
                    $buyQty += $item->getQty();
                }
            }

            $productBackOrderStatus = $this->_helper->getBackOrderStatusByProductId($product, $buyQty);

            if ($needToValidateProduct && !$this->_helper->isAvailableStock($productBackOrderStatus)) {
                return BackOrderHelper::BACK_ORDER_OVER_LIMIT_MESSAGE;
            }
        }

        return true;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param $qty
     * @return bool|string
     */
    public function validateItemUpdate(\Magento\Quote\Model\Quote\Item $item, $qty){

        $currentQty = $item->getQty();

        return $this->validateItem($item, $qty - $currentQty);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item $item
     * @param $qty
     * @return bool|string
     */
    public function validateItem($item, $qty)
    {
        $buyRequest = $item->getBuyRequest();

        if (
            isset($buyRequest['options']['ampromo_rule_id']) ||
            $item->getData('prize_id') ||
            isset($buyRequest['options']['free_machine_item'])
        ) {
            return true;
        }

        return $this->validateProduct($item->getProductId(), $qty);
    }


    /**
     * get back order type of the current cart
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getBackOrderTypeOfCurrentCart(){

        if(is_null($this->_currentBackOrderType)){
            $quote = $this->_session->getQuote();

            if($quote->getItemsCount()){

                $this->_currentBackOrderType = $this->getBackOrderStatusByQuote($quote);
            }
        }

        return $this->_currentBackOrderType;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool|int
     * @throws LocalizedException
     */
    public function getBackOrderStatusByQuote(\Magento\Quote\Model\Quote $quote)
    {
        $type = false;

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            $buyRequest = $quoteItem->getBuyRequest();

            if (isset($buyRequest['options']['ampromo_rule_id'])
                || $quoteItem->getData('prize_id')
                || isset($buyRequest['options']['free_machine_item'])
            ) {
                continue;
            }

            $backOrderStatus = $this->_helper->getBackOrderStatusByQuoteItem($quoteItem);
            $productOptionFromQuote = $quoteItem->getProduct()->getCustomOption('machine_type_id');
            $needToCheckStock = true;
            if ($productOptionFromQuote && $productOptionFromQuote->getValue()) {
                $needToCheckStock = false;
            }
            if ($needToCheckStock && !$this->_helper->isAvailableStock($backOrderStatus)) {
                throw new LocalizedException(__(BackOrderHelper::BACK_ORDER_OVER_LIMIT_MESSAGE));
            }

            if ($type === false || $type == BackOrderHelper::NO_BACK_ORDER) {
                $type = $backOrderStatus;
            }
        }

        return $type;
    }

    /**
     * @param $requestData
     * @return bool|\Exception|LocalizedException
     */
    public function validateForAddAction($requestData)
    {
        $noBackOrderItems = [];

        foreach ($requestData as $productId => $qty) {
            try {
                // validate with cart items
                $validateResult = $this->validateProduct($productId, $qty);

                if (is_string($validateResult)) {
                    throw new LocalizedException(__($validateResult));
                }

                // validate with other added products
                $backOrderStatus = $this->_helper->getBackOrderStatusByProductId($productId, $qty);

                switch ($backOrderStatus) {
                    case BackOrderHelper::NO_BACK_ORDER:
                        $noBackOrderItems[] = $productId;
                        break;
                    default:
                        if (!$this->_helper->isAvailableStock($backOrderStatus)) {
                            throw new LocalizedException(__(BackOrderHelper::BACK_ORDER_OVER_LIMIT_MESSAGE));
                        }
                        break;
                }
            } catch (LocalizedException $e) {
                $this->_messageManager->addError($e->getMessage());
                return $e;
            } catch (\Exception $e) {
                $this->_messageManager->addError(__('Process error, please try again.'));
                return $e;
            }
        }

        return true;
    }
}
