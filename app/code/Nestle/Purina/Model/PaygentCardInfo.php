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
namespace Nestle\Purina\Model;

use Nestle\Purina\Api\PaygentCardInfoInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Riki\MachineApi\Model\ApiCustomerRepository;
use Bluecom\Paygent\Model\Paygent;

/**
 * Class PaygentCardInfo
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class PaygentCardInfo implements PaygentCardInfoInterface
{
    /**
     * Customer repository
     *
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Customer repository API
     *
     * @var ApiCustomerRepository
     */
    protected $apiCustomerRepository;

    /**
     * Bluecom paygent model
     *
     * @var \Bluecom\Paygent\Model\Paygent
     */
    protected $bluecomPaygent;

    /**
     * PaygentCardInfo constructor.
     *
     * @param CustomerRepositoryInterface $customerRepository    customer repository
     * @param Paygent                     $bluecomPaygent        paygent
     * @param ApiCustomerRepository       $apiCustomerRepository customer api
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Paygent $bluecomPaygent,
        ApiCustomerRepository $apiCustomerRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->bluecomPaygent = $bluecomPaygent;
        $this->apiCustomerRepository = $apiCustomerRepository;
    }

    /**
     * Get used card info
     *
     * @param string $consumerDbId consumer_id
     *
     * @return string|mixed
     */
    public function getPaygentUsedCardInfo($consumerDbId)
    {
        $used_cc_id = 0;
        $customer = $this->apiCustomerRepository
            ->getCustomerByConsumerDbId($consumerDbId);
        if ($customer) {
            $used_cc_id = $this->bluecomPaygent
                ->canReAuthorization($customer->getId());
        }
        return $used_cc_id;
    }
}
