<?php

namespace Riki\SubscriptionCourse\Helper;

use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Model\Product\Type as ProductType;

class Type extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $courseType;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\SubscriptionCourse\Model\Course\Type $courseType
    ) {
        $this->courseType = $courseType;

        parent::__construct($context);
    }

    /**
     * get keyword of layout name by subscription type
     *
     * @param $type
     * @return string
     */
    public function getLayoutNameByType($type)
    {
        switch ($type) {
            case SubscriptionType::TYPE_SUBSCRIPTION:
                $layout = 'default';
                break;
            case SubscriptionType::TYPE_HANPUKAI_FIXED:
                $layout = 'hfixed';
                break;
            case SubscriptionType::TYPE_HANPUKAI_SEQUENCE:
                $layout = 'hsequence';
                break;
            case SubscriptionType::TYPE_MULTI_MACHINES:
                $layout = 'machine';
                break;
            case SubscriptionType::TYPE_MONTHLY_FEE:
                $layout = 'monthly_fee';
                break;
            default:
                $layout = 'default';
        }

        return $layout;
    }

    /**
     * get title page
     *
     * @param $model
     * @return \Magento\Framework\Phrase
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTitlePageByType($model)
    {
        if ($model->getId()) {
            return $model->getName();
        } else {
            if ($model->isHanpukai()) {
                switch ($model->getHanpukaiType()) {
                    case SubscriptionType::TYPE_HANPUKAI_FIXED:
                        $title = __('New Fixed Hanpukai');
                        break;
                    case SubscriptionType::TYPE_HANPUKAI_SEQUENCE:
                        $title = __('New Sequence Hanpukai');
                        break;
                    default:
                        throw new \Magento\Framework\Exception\LocalizedException(__('The request data is invalid'));
                }

                return $title;
            } elseif ($model->isMachine()) {
                return __('New Multiple Machines');
            } else {
                return __('New Subscription Course');
            }
        }
    }

    /**
     * @param $object
     * @param $requestType
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareTypeForNewObject($object, $requestType)
    {
        $normalTypes = [
            SubscriptionType::TYPE_SUBSCRIPTION,
            SubscriptionType::TYPE_MULTI_MACHINES,
            SubscriptionType::TYPE_MONTHLY_FEE
        ];

        if (in_array($requestType, $normalTypes)) {
            $object->setSubscriptionType($requestType);
            return $object;
        } elseif (in_array($requestType, array_keys($this->courseType->getHanpukaiTypes()))) {
            $object->setSubscriptionType(SubscriptionType::TYPE_HANPUKAI);
            $object->setHanpukaiType($requestType);
            return $object;
        }

        throw new \Magento\Framework\Exception\LocalizedException(__('The request data is invalid'));
    }
}
