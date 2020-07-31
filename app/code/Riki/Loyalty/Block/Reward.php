<?php

namespace Riki\Loyalty\Block;

use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;
use Riki\Loyalty\Model\ConsumerDb\CustomerSub;

class Reward extends \Magento\Framework\View\Element\Template
{
    const USE_POINT_AMOUNT = 'USE_POINT_AMOUNT';

    const USE_POINT_TYPE = 'USE_POINT_TYPE';

    /**
     * CMS page for shopping point setting config path
     */
    const XPATH_POINT_SETTING_CMS_PAGE = 'riki_loyalty/point/point_setting_cms_page';

    /**
     * CMS page for shopping point history config path
     */
    const XPATH_POINT_HISTORY_CMS_PAGE = 'riki_loyalty/point/point_history_cms_page';

    /**
     * @var \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint
     */
    protected $_shoppingPoint;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Riki\Loyalty\Model\ResourceModel\RewardFactory
     */
    protected $_rewardResourceFactory;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchBuilder;

    /**
     * @var \Magento\Cms\Helper\Page
     */
    protected $_pageHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\Loyalty\Helper\Api
     */
    protected $apiHelper;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $consumerCustomerRepository;

    /**
     * Reward constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param ShoppingPoint $shoppingPoint
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Loyalty\Model\ResourceModel\RewardFactory $rewardModel
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Cms\Helper\Page $pageHelper
     * @param \Magento\Customer\Model\Session $session
     * @param \Riki\Loyalty\Helper\Api $apiHelper
     * @param \Riki\Customer\Model\CustomerRepository $consumerCustomerRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $shoppingPoint,
        \Magento\Framework\Registry $registry,
        \Riki\Loyalty\Model\ResourceModel\RewardFactory $rewardModel,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Cms\Helper\Page $pageHelper,
        \Magento\Customer\Model\Session $session,
        \Riki\Loyalty\Helper\Api $apiHelper,
        \Riki\Customer\Model\CustomerRepository $consumerCustomerRepository,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_shoppingPoint = $shoppingPoint;
        $this->_registry = $registry;
        $this->_rewardResourceFactory = $rewardModel;
        $this->_orderRepository = $orderRepository;
        $this->_searchBuilder = $searchCriteriaBuilder;
        $this->_pageHelper = $pageHelper;
        $this->_customerSession = $session;
        $this->scopeConfig = $context->getScopeConfig();
        $this->apiHelper = $apiHelper;
        $this->consumerCustomerRepository = $consumerCustomerRepository;
    }

    /**
     * Set page title
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('My Rewards'));
    }

    /**
     * Get current customer
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        $currentCustomer = $this->_registry->registry('current_customer');
        if (!$currentCustomer) {
            return $this->_customerSession->getCustomer();
        }
        return $currentCustomer;
    }

    /**
     * Get ConsumerDB ID
     *
     * @return string
     */
    public function getCustomerCode()
    {
        if (!$this->hasData('customer_code')) {
            $this->setData('customer_code', $this->getCustomer()->getData('consumer_db_id'));
        }
        return $this->getData('customer_code');
    }

    /**
     * Get shopping point balance (points)
     *
     * @return integer
     */
    public function getPointBalance()
    {
        if (!$this->hasData('point_balance')) {
            $customerCode = $this->getCustomerCode();
            $response = $this->_shoppingPoint->getPoint($customerCode, ShoppingPoint::TYPE_POINT);
            $balance = 0;
            if (!$response['error']) {
                $balance = $response['return']['REST_POINT'];
            }
            $this->setData('point_balance', $balance);
        }
        return $this->getData('point_balance');
    }

    /**
     * Get shopping point tentative
     *
     * @return integer
     */
    public function getTentativePoint()
    {
        if (!$this->hasData('tentative_point')) {
            /** @var \Riki\Loyalty\Model\ResourceModel\Reward $model */
            $model = $this->_rewardResourceFactory->create();
            $this->setData('tentative_point', $model->customerTentativePoint($this->getCustomerCode()));
        }
        return $this->getData('tentative_point');
    }

    /**
     * Get shopping point balance (coins)
     *
     * @return integer
     */
    public function getAvailableCoin()
    {
        if (!$this->hasData('available_coin')) {
            $customerCode = $this->getCustomerCode();
            $response = $this->_shoppingPoint->getPoint($customerCode, ShoppingPoint::TYPE_COIN);
            $coin = 0;
            if (!$response['error']) {
                $coin = $response['return']['REST_POINT'];
            }
            $this->setData('available_coin', $coin);
        }
        return $this->getData('available_coin');
    }

    /**
     * Total point expired
     *
     * @param int $month
     * @return int
     */
    public function totalPointExpired($month)
    {
        $customerCode = $this->getCustomerCode();
        $response = $this->_shoppingPoint->getScheduledExpiredPoint($customerCode, $month);
        if ($response['error'] || !$response['expired']) {
            return 0;
        }
        $total = 0;
        foreach ($response['expired'] as $item) {
            $total += $item['scheduled_expired_point'];
        }
        return $total;
    }

    /**
     * Get shopping point setting: redeem type
     *
     * @return integer
     */
    public function getRewardUserSetting()
    {
        if (!$this->hasData('reward_user_setting')) {
            try {
                $consumerData = $this->consumerCustomerRepository->prepareInfoSubCustomer($this->getCustomerCode());
            } catch (\Exception $e) {
                $consumerData = null;
            }

            if (isset($consumerData[self::USE_POINT_TYPE])) {
                $this->setData('reward_user_setting', $consumerData[self::USE_POINT_TYPE]);
            }

            if (isset($consumerData[self::USE_POINT_AMOUNT])) {
                $this->setData('use_point_amount', $consumerData[self::USE_POINT_AMOUNT]);
            }
        }

        return $this->getData('reward_user_setting');
    }

    /**
     * Get shopping point setting: redeem amount
     *
     * @return integer
     */
    public function getRewardUserRedeem()
    {
        $redeem = $this->getData('use_point_amount');
        return min($redeem, $this->getPointBalance());
    }

    /**
     * Get shopping point setting: redeem amount
     *
     * @param $pointBalance
     *
     * @return mixed
     */
    public function getRewardPointUserRedeem($pointBalance)
    {
        $redeem = $this->getData('use_point_amount');
        return min($redeem, $pointBalance);
    }

    /**
     * Get point history from consumerDB
     *
     * @return array
     */
    public function getPointsHistory()
    {
        if (!$this->hasData('point_history')) {
            $customerCode = $this->getCustomerCode();
            $response = $this->_shoppingPoint->getPointHistory($customerCode, ShoppingPoint::TYPE_POINT);
            if (!$response['error']) {
                $this->setData('point_history', $response['history']);
            } else {
                $this->setData('point_history', []);
            }
        }
        return  $this->getData('point_history');
    }

    /**
     * Get point history in magento side
     *
     * @return mixed
     */
    public function getTentativeHistory()
    {
        if (!$this->hasData('tentative_history')) {
            $customerCode = $this->getCustomerCode();
            /** @var \Riki\Loyalty\Model\ResourceModel\Reward $model */
            $model = $this->_rewardResourceFactory->create();
            $tentativeHistory = $model->tentativePointHistory($customerCode);
            $this->setData('tentative_history', (array) $tentativeHistory);
        }
        return $this->getData('tentative_history');
    }

    public function fetchListArray(){
        $result = [];
        $listPointHistory = $this->getPointsHistory();
        $listTentativeHistory = $this->getTentativeHistory();
        $i = 0;
        
        if($listTentativeHistory){
            foreach ($listTentativeHistory as $item){
                $result[$i]['action_date'] = $item['action_date'];
                $result[$i]['point_issue_type'] = $this->apiHelper->getIssueTypeLabel($item['point_type']);
                $result[$i]['point_status'] = __('Tentative');
                $result[$i]['issued_point'] = $item['issued_point'];
                $result[$i]['expiration'] = '-';
                $result[$i]['order_id'] = $item['order_id'];
                $result[$i]['increment_id'] = $item['order_no'];
                $result[$i]['sort'] = strtotime($item['action_date']);
                $result[$i]['point_used_datetime'] = '';
                $i++;
            }
        }
        if($listPointHistory){
            $orderInPairs = $this->orderNoInPairs();
            foreach ($listPointHistory as $item){
                $result[$i]['action_date'] = $item['point_issue_datetime'];
                $result[$i]['point_issue_type'] = $this->apiHelper->getIssueTypeLabel($item['point_issue_type']);
                $result[$i]['point_status'] = $this->apiHelper->getPointStatusLabel($item['point_issue_status']);
                $result[$i]['issued_point'] = $item['issued_point'];
                $result[$i]['expiration'] = $item['scheduled_expired_date'];
                $result[$i]['order_id'] = ((isset($item['order_no']) && isset($orderInPairs[$item['order_no']])) ? $orderInPairs[$item['order_no']] : '');
                $result[$i]['increment_id'] = $item['order_no'];
                $result[$i]['sort'] = strtotime($item['point_issue_datetime']);
                $result[$i]['point_used_datetime'] = $item['point_used_datetime'];
                $i++;
            }
        }
        if($result){
            usort($result,function($a, $b){
               return $a['sort'] < $b['sort'];
            });
        }
        return $result;
    }
    /**
     * Get point expired from consumerDB
     *
     * @return array
     */
    public function getPointExpired()
    {
        if (!$this->hasData('point_expired')) {
            $customerCode = $this->getCustomerCode();
            $response = $this->_shoppingPoint->getScheduledExpiredPoint($customerCode);
            if (!$response['error']) {
                $this->setData('point_expired', $response['expired']);
            } else {
                $this->setData('point_expired', []);
            }
        }
        return $this->getData('point_expired');
    }

    /**
     * Get list order base on increment_id
     *
     * @return array
     */
    public function orderNoInPairs()
    {
        $history = $this->getPointsHistory();
        if (!$history) {
            return [];
        }
        $arrOrderNo = array_map(function ($value) {
            return $value['order_no'];
        }, $history);
        $incrementIds = array_filter($arrOrderNo);
        if (!sizeof($incrementIds)) {
            return [];
        }
        $filter = $this->_searchBuilder->addFilter('increment_id', array_unique($incrementIds), 'in');
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orders */
        $orders = $this->_orderRepository->getList($filter->create());
        $result = [];
        /** @var \Magento\Sales\Model\Order $order*/
        if ($orders->getSize()) {
            foreach ($orders as $order) {
                $result[$order->getIncrementId()] = $order->getId();
            }
        }
        return $result;
    }

    /**
     * Get shopping point cms page for description
     *
     * @return string
     */
    public function pointSettingPageUrl()
    {
        $pageId = $this->_scopeConfig->getValue(self::XPATH_POINT_SETTING_CMS_PAGE);
        if ($pageId) {
            return $this->_pageHelper->getPageUrl($pageId);
        }
        return null;
    }

    /**
     * Get shopping point cms page for description
     *
     * @return string
     */
    public function pointHistoryPageUrl()
    {
        $pageId = $this->_scopeConfig->getValue(self::XPATH_POINT_HISTORY_CMS_PAGE);
        if ($pageId) {
            return $this->_pageHelper->getPageUrl($pageId);
        }
        return null;
    }

    /**
     * Get System Config
     *
     * @param $path
     *
     * @return mixed
     */
    public function getSystemConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }

    /**
     * Get customer account back url
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('customer/account/');
    }
}