<?php
namespace Riki\AdvancedInventory\Api;

use Riki\AdvancedInventory\Model\OutOfStock;

interface OutOfStockManagementInterface
{
    /**
     * Get oos quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getOosQuote();

    /**
     * @param $orderId
     * @return bool
     */
    public function isOosGeneratedOrder($orderId);

    /**
     * @param $productId
     * @return array
     */
    public function getOutOfStockIdsByProductId($productId);

    /**
     * @param array $messages
     * @return mixed
     */
    public function sendAuthorizeFailureEmail(array $messages);
}
