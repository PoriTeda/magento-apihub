<?php

namespace Riki\ThirdPartyImportExport\Helper\Subscription;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $courseModel;

    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    protected $resourceCourseModel;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connectionSales;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course $resourceCourseModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $resourceCourseModel,
        \Magento\Framework\App\ResourceConnection $resourceConnection

    ) {
        $this->courseModel = $courseModel;
        $this->resourceCourseModel = $resourceCourseModel;
        $this->resource = $resourceConnection;
        $this->connectionSales = $this->resource->getConnection('sales');
        parent::__construct($context);
    }


    /**
     * @param $subProfile
     */
    public function getTypeProfile($subProfile)
    {
        if ($this->isHanpukaiSequenceProfile($subProfile['course_id']) == true) {
            return \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_HANPUKAI;
        } else {
            return $this->checkProfileIsVersion($subProfile['profile_id']);
        }
    }

    /**
     * @param $subProfile
     * @return mixed
     */
    public function getDeliveryNumberHanpukaiExport($subProfile){

        $aDeliveryNeedExport = [];

        if($subProfile['type_profile'] == \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_HANPUKAI){

            $courseModel = $this->courseModel->load($subProfile['course_id']);
            $aHanpukaiSequenceProductData = $this->resourceCourseModel->getHanpukaiSequenceProductsData($courseModel);
            $aAllDelivery = $this->howManyDelivery($aHanpukaiSequenceProductData);
            $aDeliveryNeedExport = $this->deleteDeliveredHanpukaiSequence($subProfile['order_times'], $aAllDelivery);

        }

        return $aDeliveryNeedExport;
    }
    /**
     * @param $courseId
     * @return bool
     */
    public function isHanpukaiSequenceProfile($courseId)
    {
        $courseModel = $this->courseModel->load($courseId);
        if ($courseModel instanceof \Riki\SubscriptionCourse\Model\Course) {
            if ($courseModel->getData('hanpukai_type') == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_SEQUENCE) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $profileId
     * @return int (1 subscription profile version, 2 not subscription profile version, 3 subscription profile version out of date)
     */
    public function checkProfileIsVersion($profileId)
    {
        $profileVersion = $this->connectionSales->select()->from([
            'sp_version' => $this->connectionSales->getTableName('subscription_profile_version')
        ])->where('moved_to = ?', $profileId);

        $collectionVersionQuery = $this->connectionSales->query($profileVersion);

        while ($profileVersionData = $collectionVersionQuery->fetch()) {
            if (!empty($profileVersionData)) {
                if ($profileVersionData['status'] == 0) {
                    return \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_VERSION_OUT_OF_DATE;
                } else {
                    return \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_VERSION;
                }
            }
        }
        return \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_MAIN;
    }


    /**
     * How many delivery hanpukai sequence
     *
     * @param $arrHanpukaiSequenceProductData
     *
     * @return array
     */
    public function howManyDelivery($aHanpukaiSequenceProductData)
    {
        $arrDelivery = array();
        foreach ($aHanpukaiSequenceProductData as $productId => $detail) {
            if (!in_array($detail['delivery_number'], $arrDelivery)) {
                $arrDelivery[] = $detail['delivery_number'];
            }
        }
        return $arrDelivery;
    }

    /**
     * Delete Delivery Hanpukai Sequence
     *
     * @param $deliveredNumber
     * @param $arrHanpukaiSequenceDelivery
     *
     * @return array
     *
     */
    public function deleteDeliveredHanpukaiSequence($deliveredNumber, $aHanpukaiSequenceDelivery)
    {
        $arrResult = [];

        foreach ($aHanpukaiSequenceDelivery as $iHanpukaiSequenceDelivery){
            if ($iHanpukaiSequenceDelivery > $deliveredNumber) {
                $arrResult[] = $iHanpukaiSequenceDelivery;
            }
        }

        return $arrResult;
    }

    /**
     * @param $profileId
     * @return null
     */
    public function getMapVersionAndProfile($profileId)
    {
        $iOriginProfileId = null;

        if ($profileId) {
            $profileVersion = $this->connectionSales->select()->from([
                'sp_version' => $this->connectionSales->getTableName('subscription_profile_version')
            ])->where('moved_to = ?', $profileId)
              ->where('status = ?', 1);

            $collectionVersionQuery = $this->connectionSales->query($profileVersion);
            while ($profileVersionData = $collectionVersionQuery->fetch()) {
                if (isset($profileVersionData['moved_to']) && isset($profileVersionData['rollback_id'])) {
                    $iOriginProfileId = $profileVersionData['rollback_id'];
                }
            }
        }

        return $iOriginProfileId;
    }
}