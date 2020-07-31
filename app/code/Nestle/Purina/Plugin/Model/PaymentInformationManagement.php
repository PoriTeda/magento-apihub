<?php
/**
 * Nestle Purina Vets
 * PHP version 7
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
namespace Nestle\Purina\Plugin\Model;

use Bluecom\Paygent\Model\ResourceModel\PaygentOption\CollectionFactory;
use Nestle\Purina\Model\PaymentInformationManagement as modelPayment;

/**
 * Class PaymentInformationManagement
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class PaymentInformationManagement
{
    /**
     * Paygent option collection
     *
     * @var CollectionFactory $collectionFactory
     */
    protected $collectionFactory;

    /**
     * PaymentInformationManagement constructor.
     *
     * @param CollectionFactory $collectionFactory paygent option
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * To append redirect URL without breaking parent's object construct
     *
     * @param modelPayment $instance payment instance
     * @param array        $result   result
     *
     * @return array
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        modelPayment $instance,
        array $result
    ) {
        if (array_key_exists('order_no', $result)) { // success case
            $customerId = $instance->quoteData->getCustomerId();
            $collection = $this->collectionFactory->create();
            $collection->addFilter('customer_id', $customerId);
            $collection->load();
            $currentOption = $collection->getLastItem();
            $redirectUrl = $currentOption->getLinkRedirect();
            $result['redirect_url'] = $redirectUrl;
            return [$result];
        }
        return $result;
    }
}
