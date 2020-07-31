<?php
/**
 * User Helper
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShipmentExporter\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ShipmentExporter\Helper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
/**
 * Class User Helper
 *
 * @category  RIKI
 * @package   Riki\ShipmentExporter\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class User extends AbstractHelper
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepositiory;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;
    /**
     * User constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        Context $context,
        CustomerRepository $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerFactory $customerFactory

    ) {
        $this->customerRepositiory = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerFactory = $customerFactory;
        parent::__construct($context);

    }

    /**
     * Get Detail Customer
     *
     * @param   $customerId
     * @return  \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getDetailCustomer($customerId)
    {
        try{
            $customer = $this->customerFactory->create()->load($customerId);
            return $customer;
        }catch(\Exception $e)
        {
            $this->_logger->info($e->getMessage());
            return false;
        }

    }
}