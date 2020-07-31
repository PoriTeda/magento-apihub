<?php

namespace Riki\SerialCode\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Riki\SerialCode\Model\Source\Status as SerialCodeStatus;
use Riki\Loyalty\Model\Reward;
use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;

class SerialCode extends AbstractModel
{
    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $_loyaltyHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint
     */
    protected $_consumerDb;

    /**
     * @var \Riki\Loyalty\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * @var \Riki\Loyalty\Helper\Email
     */
    protected $_loyaltyEmail;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Loyalty\Helper\Data $helper,
        \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $consumerDb,
        \Riki\Loyalty\Helper\Email $email,
        \Riki\Loyalty\Model\RewardFactory $rewardFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_loyaltyHelper = $helper;
        $this->_dateTime = $dateTime;
        $this->_localeDate = $localeDate;
        $this->_rewardFactory = $rewardFactory;
        $this->_consumerDb = $consumerDb;
        $this->_loyaltyEmail = $email;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\SerialCode\Model\ResourceModel\SerialCode');
    }

    /**
     * Can not edit serial code that used
     *
     * @return bool
     */
    public function canEdit()
    {
        $protect = [SerialCodeStatus::STATUS_USED];
        return !in_array($this->getData('status'), $protect);
    }

    /**
     * Generate serial code
     *
     * @return mixed
     */
    public function generateSerialCode()
    {
        return $this->getResource()->generateSerialCode($this);
    }

    /**
     * Get serial_code id by code
     *
     * @param string $serialCode
     * @return bool
     */
    public function loadBySerialCode($serialCode)
    {
        if(!$serialCode){
            return false;
        }
        $id = $this->getResource()->loadBySerialCode($serialCode);
        return $id ;
    }

    /**
     * Apply serial code
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param int|null $websiteId
     * @return array
     */
    public function applySerialCode($customer, $websiteId = null)
    {
        $customerCode = $customer->getData('consumer_db_id');
        $response = [];
        try {
            $dateCompare = new \Zend_Date();
            $dateCompare->setTimezone($this->_localeDate->getConfigTimezone());

            if (!$this->getId()) {
                throw new LocalizedException(__('This Serial code/Lucky number is invalid.'));
            }

            if ($this->getData('status') == SerialCodeStatus::STATUS_USED) {
                throw new LocalizedException(__('This serial code has already been registered for this campaign.'));
            }

            $validate = true;

            if ($this->getData('status') == SerialCodeStatus::STATUS_CANCELLED) {
                //throw new \Exception(__('This serial code is not applicable'));
                $validate = false;
            }

            $activationDate = $this->_dateTime->formatDate($this->getData('activation_date'), true);
            if ($dateCompare->isEarlier($activationDate, \Zend_Date::ISO_8601)) {
                //throw new \Exception(__('This serial code is not active'));
                $validate = false;
            }
            if ($expirationDate = $this->getData('expiration_date')) {
                $expirationDate = $this->_dateTime->formatDate($expirationDate, true);
                if ($dateCompare->isLater($expirationDate, \Zend_Date::ISO_8601)) {
                    //throw new \Exception(__('This serial code is not active'));
                    $validate = false;
                }
            }
            if ($campaign = $this->getData('campaign_id')) {
                $totalUsed = $this->getResource()->campaignUsed($campaign);
                $campaignLimit = (int) $this->getData('campaign_limit');
                if ($totalUsed >= $campaignLimit) {
                    //throw new \Exception(__('This serial code reaches the limit of used for this campaign'));
                    $validate = false;
                }
            }

            if(!$validate){
                throw new LocalizedException(__('This Serial code/Lucky number is invalid.'));
            }

            $expiryPeriod = $this->getData('point_expiration_period');
            if (!$expiryPeriod) {
                $expiryPeriod = $this->_loyaltyHelper->getDefaultExpiryPeriod();
            }
            /** @var  \Riki\Loyalty\Model\Reward $reward */
            $reward = $this->_rewardFactory->create();
            $data = [
                'website_id' => $websiteId,
                'point' => $this->getData('issued_point'),
                'description' => sprintf("Serial code %s", $this->getData('serial_code')),
                'account_code' => $this->getData('account_code'),
                'wbs_code' => $this->getData('wbs'),
                'customer_id' => $this->getId(),
                'customer_code' => $customerCode,
                'point_type' => Reward::TYPE_CAMPAIGN,
                'serial_code' => $this->getData('serial_code'),
                'action_date' => $this->_loyaltyHelper->pointActionDate(),
                'expiry_period' => $expiryPeriod
            ];

            $reward->addData($data);
            //step 1: save in consumerDB
            $consumerData = [
                'pointIssueType' => Reward::TYPE_CAMPAIGN,
                'description' => $reward->getData('description'),
                'pointAmountId' => ShoppingPoint::POINT_AMOUNT_ID,
                'point' => $reward->getData('point'),
                'orderNo' => '',
                'scheduledExpiredDate' => $this->_loyaltyHelper->scheduledExpiredDate($reward->getData('expiry_period')),
                'serialNo' => $reward->getData('serial_code'),
                'wbsCode' => $reward->getData('wbs_code'),
                'accountCode' => $reward->getData('account_code')
            ];
            $response = $this->_consumerDb->setPoint(ShoppingPoint::REQUEST_TYPE_ALLOCATION, $customerCode, $consumerData);
            if ($response['error']) {
                throw new LocalizedException(__($response['msg']));
            }
            //step 2: save in Magento
            $reward->setData('status', Reward::STATUS_SHOPPING_POINT);
            $reward->save();

            //step 4: update status used on this serial code
            $this->setData('customer_id', $customerCode);
            $this->setData('status', SerialCodeStatus::STATUS_USED);
            $this->setData('used_date', (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
            $this->save();

            //step 5: send mail confirm to customer
            $this->_loyaltyEmail->serialCodeConfirmation($customer, $this);
            $response['err'] = false;
            //$response['msg'] = __('Successful apply serial code %1', $this->getData('serial_code'));
            $response['msg'] = __('Serial Code has been registered successfully');
        } catch (\Exception $e) {
            $response['err'] = true;
            $response['msg'] = $e->getMessage();
        }
        return $response;
    }
}