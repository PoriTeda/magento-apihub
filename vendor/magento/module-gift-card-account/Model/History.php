<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model;

/**
 * @method int getGiftcardaccountId()
 * @method \Magento\GiftCardAccount\Model\History setGiftcardaccountId(int $value)
 * @method string getUpdatedAt()
 * @method \Magento\GiftCardAccount\Model\History setUpdatedAt(string $value)
 * @method int getAction()
 * @method \Magento\GiftCardAccount\Model\History setAction(int $value)
 * @method float getBalanceAmount()
 * @method \Magento\GiftCardAccount\Model\History setBalanceAmount(float $value)
 * @method float getBalanceDelta()
 * @method \Magento\GiftCardAccount\Model\History setBalanceDelta(float $value)
 * @method string getAdditionalInfo()
 * @method \Magento\GiftCardAccount\Model\History setAdditionalInfo(string $value)
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class History extends \Magento\Framework\Model\AbstractModel
{
    const ACTION_CREATED = 0;

    const ACTION_USED = 1;

    const ACTION_SENT = 2;

    const ACTION_REDEEMED = 3;

    const ACTION_EXPIRED = 4;

    const ACTION_UPDATED = 5;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\GiftCardAccount\Model\ResourceModel\History::class);
    }

    /**
     * @return array
     */
    public function getActionNamesArray()
    {
        return [
            self::ACTION_CREATED => __('Created'),
            self::ACTION_UPDATED => __('Updated'),
            self::ACTION_SENT => __('Sent'),
            self::ACTION_USED => __('Used'),
            self::ACTION_REDEEMED => __('Redeemed'),
            self::ACTION_EXPIRED => __('Expired')
        ];
    }

    /**
     * Get info about creation context
     *
     * @return \Magento\Framework\Phrase|string
     */
    protected function _getCreatedAdditionalInfo()
    {
        if ($this->getGiftcardaccount()->getOrder()) {
            $orderId = $this->getGiftcardaccount()->getOrder()->getIncrementId();
            return __('Order #%1.', $orderId);
        }

        return '';
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    protected function _getUsedAdditionalInfo()
    {
        if ($this->getGiftcardaccount()->getOrder()) {
            $orderId = $this->getGiftcardaccount()->getOrder()->getIncrementId();
            return __('Order #%1.', $orderId);
        }

        return '';
    }

    /**
     * Get info about sent mail context
     *
     * @return \Magento\Framework\Phrase
     */
    protected function _getSentAdditionalInfo()
    {
        $recipient = $this->getGiftcardaccount()->getRecipientEmail();
        $name = $this->getGiftcardaccount()->getRecipientName();
        if ($name) {
            $recipient = "{$name} <{$recipient}>";
        }

        return __('Recipient: %1.', $recipient);
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    protected function _getRedeemedAdditionalInfo()
    {
        if ($customerId = $this->getGiftcardaccount()->getCustomerId()) {
            return __('Customer #%1.', $customerId);
        }
        return '';
    }

    /**
     * Get info about update context
     *
     * @return string
     */
    protected function _getUpdatedAdditionalInfo()
    {
        return '';
    }

    /**
     * @return string
     */
    protected function _getExpiredAdditionalInfo()
    {
        return '';
    }

    /**
     * Processing object before save data
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        if (!$this->hasGiftcardaccount()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please assign a gift card account.'));
        }

        $this->setAction($this->getGiftcardaccount()->getHistoryAction());
        $this->setGiftcardaccountId($this->getGiftcardaccount()->getId());
        $this->setBalanceAmount($this->getGiftcardaccount()->getBalance());
        $this->setBalanceDelta($this->getGiftcardaccount()->getBalanceDelta());

        switch ($this->getGiftcardaccount()->getHistoryAction()) {
            case self::ACTION_CREATED:
                $this->setAdditionalInfo($this->_getCreatedAdditionalInfo());

                $this->setBalanceDelta($this->getBalanceAmount());
                break;
            case self::ACTION_USED:
                $this->setAdditionalInfo($this->_getUsedAdditionalInfo());
                break;
            case self::ACTION_SENT:
                $this->setAdditionalInfo($this->_getSentAdditionalInfo());
                break;
            case self::ACTION_REDEEMED:
                $this->setAdditionalInfo($this->_getRedeemedAdditionalInfo());
                break;
            case self::ACTION_UPDATED:
                $this->setAdditionalInfo($this->_getUpdatedAdditionalInfo());
                break;
            case self::ACTION_EXPIRED:
                $this->setAdditionalInfo($this->_getExpiredAdditionalInfo());
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('Unknown history action.'));
                break;
        }

        return parent::beforeSave();
    }

    /**
     * @inheritdoc
     */
    public function hasDataChanges()
    {
        return parent::hasDataChanges()
            || ($this->hasGiftcardaccount() && $this->getGiftcardaccount()->hasDataChanges());
    }
}
