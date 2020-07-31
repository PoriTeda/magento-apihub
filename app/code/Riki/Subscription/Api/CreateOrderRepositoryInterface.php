<?php
namespace Riki\Subscription\Api;
use Magento\Framework\Api\SearchCriteriaInterface;
/**
 * @api
 */
interface CreateOrderRepositoryInterface
{
    public function saveQuoteItemAddress($quote,$quoteItem,$addressId);

    public function saveOrderAddressItem($quote,$order);
}