<?php

namespace Riki\Subscription\Model\Profile;

use \Magento\Framework\Model\Context as Context;
use \Magento\Framework\Registry as Registry;
use \Riki\Subscription\Model\Profile\ResourceModel\ProfileLink as ProfileLinkResourceModel;
use \Riki\Subscription\Model\Profile\ResourceModel\ProfileLink\Collection as ProfileLinkCollection;

class ProfileLink extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Riki\Subscription\Logger\LoggerStateProfile
     */
    protected $loggerStateProfile;

    /**
     * ProfileLink constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ProfileLinkResourceModel $resourceModel
     * @param ProfileLinkCollection $collection
     * @param \Riki\Subscription\Logger\LoggerStateProfile $loggerStateProfile
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ProfileLinkResourceModel $resourceModel,
        ProfileLinkCollection $collection,
        \Riki\Subscription\Logger\LoggerStateProfile $loggerStateProfile
    ) {

        $this->loggerStateProfile = $loggerStateProfile;
        parent::__construct($context, $registry, $resourceModel, $collection);
    }

    /**
     * @return $this
     */
    public function afterSave(){

        parent::afterSave();

        $this->loggerStateProfile->infoProfileTemp($this);

        return $this;
    }

    /**
     * @return $this
     */
    public function afterDelete(){

        parent::afterDelete();

        $this->loggerStateProfile->infoProfileTempDeleted($this);

        return $this;
    }
}