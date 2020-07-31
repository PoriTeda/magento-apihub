<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\ThirdPartyImportExport\Model\ExportNextDelivery;

use Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryOrderHelper;
use Magento\Framework\App\ResourceConnection;
use Riki\Subscription\Helper\Order\Simulator as OrderSimulator;

class SubscriptionProfileSimulate
{

    const SUBSCRIPTION_PROFILE_MAIN = 1;

    const SUBSCRIPTION_PROFILE_VERSION = 2;

    const SUBSCRIPTION_PROFILE_HANPUKAI = 3;

    const SUBSCRIPTION_PROFILE_VERSION_OUT_OF_DATE = 4;

    const SUBSCRIPTION_PROFILE_TMP = 'tmp';

    protected $aSimulateOrderData = [];

    protected $aSimulateSimulateData = [];

    /**
     * SubscriptionProfileSimulate constructor.
     * @param \Riki\ThirdPartyImportExport\Helper\Subscription\Data $subscriptionHelper
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubProfileCart $loggerCart
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\HandlerSubProfileCart $handlerCartCSV
     * @param SubProfileNextDeliveryOrderHelper $subProfileNextDeliveryHelper
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Framework\Registry $registry
     * @param OrderSimulator\Proxy $simulator
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\Subscription\Data $subscriptionHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubProfileCart $loggerCart,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\HandlerSubProfileCart $handlerCartCSV,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryOrderHelper $subProfileNextDeliveryHelper,
        ResourceConnection $resourceConnection,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Order\Simulator\Proxy $simulator,
        \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionOrderCart $exportSubsCart,
        \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionShipment $exportSubShipment,
        \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionShipmentDetail $exportSubShipmentDetail
    ) {
        $this->cartLogger = $loggerCart;
        $this->handlerCartCSV = $handlerCartCSV;
        $this->cartLogger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));

        $this->resource = $resourceConnection;
        $this->connectionSales = $this->resource->getConnection('sales');

        $this->simulator = $simulator;
        $this->registry = $registry;
        $this->subscriptionHelper = $subscriptionHelper;

        $this->exportSubsCart = $exportSubsCart;
        $this->exportSubShipment = $exportSubShipment;
        $this->exportSubShipmentDetail = $exportSubShipmentDetail;
    }


    /**
     * @param ItemsInterface $message
     * @return void
     */
    public function exportSubscriptionProfileSimulate(
        \Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemsInterface $message,
        $consumerName
    ) {
        try {
            if (!$this->registry->registry('bi_export_subscription')) {
                $this->registry->register('bi_export_subscription', true);
            }

            $profileId = null;
            foreach ($message->getItems() as $profileObject) {
                $profileId = $profileObject->getProfileId();
            }

            $selectProfile = $this->connectionSales->select()->from([
                'sp' => $this->connectionSales->getTableName('subscription_profile')
            ])->where('profile_id = ?', $profileId);

            $queryProfile = $this->connectionSales->query($selectProfile);

            $subProfile = $queryProfile->fetch();

            if ($subProfile) {
                /*get type of profile*/
                $subProfile['type_profile'] = $this->subscriptionHelper->getTypeProfile($subProfile);
                if ($subProfile['type_profile'] != self::SUBSCRIPTION_PROFILE_VERSION_OUT_OF_DATE && $subProfile['type'] != self::SUBSCRIPTION_PROFILE_TMP) {

                    $subProfile['hanpukai_delivery_number'] = $this->subscriptionHelper->getDeliveryNumberHanpukaiExport($subProfile);

                    $subProfile['origin_profile_id'] = $this->subscriptionHelper->getMapVersionAndProfile($subProfile['profile_id']);

                    /*simulate data for profile*/
                    $this->simulateProfileData($subProfile);

                    /*export subscription profile product cart*/
                    $this->exportSubsCart->exportSubscriptionProfileCart($subProfile, $consumerName);

                    /*export subscription profile shipment header*/
                    $this->exportSubShipment->exportSubscriptionShipmentHeader($subProfile, $consumerName);

                    /*export subscription profile shipment detail*/
                    $this->exportSubShipmentDetail->exportSubscriptionShipmentDetail($subProfile, $consumerName);

                    $this->freeSimulateProfileData();
                }
            }

        } catch (\Exception $e) {
            $this->cartLogger->info($e->getMessage());
            throw $e;
        }
    }

    /**
     * @param $subProfile
     */
    public function simulateProfileData($subProfile)
    {
        /*simulate hanpukai with delivery*/
        if ($subProfile['type_profile'] == self::SUBSCRIPTION_PROFILE_HANPUKAI) {

            $aDeliveryNumber = $subProfile['hanpukai_delivery_number'];

            foreach ($aDeliveryNumber as $iDeliveryNumber) {
                list($orderSimulate, $shipmentSimulate) = $this->simulator->createMageOrder($subProfile['profile_id'],
                    null, null, null, false, $iDeliveryNumber, true, true);
                $this->exportSubsCart->addSimulateOrderData($orderSimulate, $subProfile['profile_id'],
                    $iDeliveryNumber);

                $this->exportSubShipment->addSimulateOrderData($orderSimulate, $subProfile['profile_id'],
                    $iDeliveryNumber);
                $this->exportSubShipment->addSimulateShipmentData($shipmentSimulate, $subProfile['profile_id'],
                    $iDeliveryNumber);

                $this->exportSubShipmentDetail->addSimulateOrderData($orderSimulate, $subProfile['profile_id'],
                    $iDeliveryNumber);
                $this->exportSubShipmentDetail->addSimulateShipmentData($shipmentSimulate, $subProfile['profile_id'],
                    $iDeliveryNumber);

            }
        } else {
            list($orderSimulate, $shipmentSimulate) = $this->simulator->createMageOrder($subProfile['profile_id'], null,
                null, null, false, null, true, true);

            $this->exportSubsCart->addSimulateOrderData($orderSimulate, $subProfile['profile_id']);

            $this->exportSubShipment->addSimulateOrderData($orderSimulate, $subProfile['profile_id']);
            $this->exportSubShipment->addSimulateShipmentData($shipmentSimulate, $subProfile['profile_id']);

            $this->exportSubShipmentDetail->addSimulateOrderData($orderSimulate, $subProfile['profile_id']);
            $this->exportSubShipmentDetail->addSimulateShipmentData($shipmentSimulate, $subProfile['profile_id']);

        }
    }

    /**
     *  Free simulate profile data
     */
    public function freeSimulateProfileData()
    {

        $this->exportSubsCart->freeSimulateOrderData();

        $this->exportSubShipment->freeSimulateOrderData();
        $this->exportSubShipment->freeSimulateShipmentData();

        $this->exportSubShipmentDetail->freeSimulateOrderData();
        $this->exportSubShipmentDetail->freeSimulateShipmentData();

    }

}