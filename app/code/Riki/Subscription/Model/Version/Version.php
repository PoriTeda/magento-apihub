<?php
namespace Riki\Subscription\Model\Version;

use Magento\Framework\DataObject;

/**
 * Class Version
 * @package Riki\Subscription\Model\Version
 */
class Version extends \Magento\Framework\Model\AbstractModel
{
    const TABLE = 'subscription_profile_version';

    const ACTIVE_STATUS = 1;

    /**
     * @var \Riki\Subscription\Logger\LoggerStateProfile
     */
    protected $loggerStateProfile;

    /**
     * Version constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Logger\LoggerStateProfile $loggerStateProfile
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Logger\LoggerStateProfile $loggerStateProfile,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->loggerStateProfile = $loggerStateProfile;

        parent::__construct($context,$registry,$resource,$resourceCollection,$data);
    }

    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Version\ResourceModel\Version');
    }

    /**
     * @return $this
     */
    public function afterSave(){

        parent::afterSave();

        $this->loggerStateProfile->infoProfileVersion($this);

        return $this;
    }
}