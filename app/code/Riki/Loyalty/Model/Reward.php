<?php

namespace Riki\Loyalty\Model;

use Magento\Framework\Model\AbstractModel;

class Reward extends AbstractModel
{
    const DEFAULT_EXPIRY = 730; //days
    const DEFAULT_RETRY = 1;
    /**
     * Point statuses
     */
    const STATUS_TENTATIVE = 0;
    const STATUS_SHOPPING_POINT = 1;
    const STATUS_REDEEMED = 3;
    const STATUS_CANCEL = 4;
    const STATUS_PENDING_APPROVAL = 5;
    const STATUS_ERROR = 9;

    /**
     * Point issue type
     */
    const TYPE_PURCHASE = 0;
    const TYPE_PAID = 1;
    const TYPE_PRODUCT_REVIEW = 1;
    const TYPE_QUESTIONNAIRE = 2;
    const TYPE_ADJUSTMENT = 3;
    const TYPE_MEMBER_REGISTRATION = 4;
    const TYPE_FREE_GIFT = 5;
    const TYPE_ORDER_DISCOUNT = 6;
    const TYPE_SITE_VISIT = 7;
    const TYPE_GAME = 8;
    const TYPE_CAMPAIGN = 9;
    const TYPE_CONTENT_USAGE = 10;
    const TYPE_POINT_EXCHANGE = 11;
    const TYPE_OTHER = 99;

    /**@#+
     * Level of point earn
     */
    const LEVEL_ITEM = 0;
    const LEVEL_ORDER = 1;
    /**@#-*/

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'riki_loyalty_reward';

    /**
     * Parameter name in event
     *
     * @var string
     */
    protected $_eventObject = 'reward';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Loyalty\Model\ResourceModel\Reward');
    }
}