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

use Nestle\Purina\Api\CustomerGetPointInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Riki\MachineApi\Model\ApiCustomerRepository;
use Riki\Loyalty\Model\RewardManagement;

/**
 * Class CustomerRewardBalancePoint
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class CustomerRewardBalancePoint implements CustomerGetPointInterface
{
    /**
     * Customer Repository
     *
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Customer Repository API
     *
     * @var ApiCustomerRepository
     */
    protected $apiCustomerRepository;

    /**
     * Custom reward
     *
     * @var RewardManagement
     */
    protected $rewardManagement;

    /**
     * CustomerRewardBalancePoint constructor.
     *
     * @param CustomerRepositoryInterface $customerRepository    Customer_repo
     * @param RewardManagement            $rewardManagemnt       reward_point
     * @param ApiCustomerRepository       $apiCustomerRepository customer_repo_api
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        RewardManagement $rewardManagemnt,
        ApiCustomerRepository $apiCustomerRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->rewardManagement = $rewardManagemnt;
        $this->apiCustomerRepository = $apiCustomerRepository;
    }

    /**
     * Get customer reward point balance
     *
     * @param string $consumerDbId consumer_id
     *
     * @return int|mixed
     */
    public function getCustomerRewardBalance($consumerDbId)
    {
        $point = 0;
        $customer = $this->apiCustomerRepository->getCustomerByConsumerDbId(
            $consumerDbId
        );
        if ($customer) {
            $point = $this->rewardManagement->getPointBalance($consumerDbId);
        }
        return $point;
    }
}
