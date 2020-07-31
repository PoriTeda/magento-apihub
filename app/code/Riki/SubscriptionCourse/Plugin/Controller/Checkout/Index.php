<?php
namespace Riki\SubscriptionCourse\Plugin\Controller\Checkout;

use Magento\Framework\Controller\ResultFactory;

class Index
{
    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $response;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $url;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    protected $checkoutSession;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Riki\Catalog\Model\StockState $stockState
     */
    public function __construct(
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Riki\Catalog\Model\StockState $stockState
    ) {
        $this->response = $response;
        $this->messageManager = $messageManager;
        $this->url = $urlInterface;
        $this->stockState = $stockState;
        $this->checkoutSession = $checkoutSession;
    }

    public function afterDispatch(
        \Riki\Checkout\Controller\Index\Single $subject,
        $result
    ) {
        /*flag to check quote item is out of stock*/
        $outOfStock = false;

        /*out of stock production*/
        $outOfStockProductName = '';

        $quote = $subject->getOnepage()->getQuote();

        foreach ($quote->getAllVisibleItems() as $item) {
            $buyRequest = $item->getBuyRequest();

            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }

            if ($item->getData('is_riki_machine')) {
                continue;
            }

            $product = $item->getProduct();

            if (!$this->stockState->canAssigned($product, $item->getQty(), $this->stockState->getPlaceIds())) {
                $outOfStock = true;
                $outOfStockProductName = $product->getName();
                break;
            }
        }

        if (!$outOfStock) {
            return $result;
        }

        $message = 'I am sorry. ';
        $message.= 'Before you finish placing order, ';
        $message.= '%1 has become out of stock. If you do not mind, please consider another product.';

        $this->messageManager->addError(
            __($message, $outOfStockProductName)
        );

        $url = $this->checkoutSession->getCartRefererUrl();
        if($url === null || $url === '') {
            $this->response->setRedirect(
                $this->url->getUrl('checkout/cart')
            );
        } else{
            $this->response->setRedirect($url);
        }

        return $this->response;
    }
}
