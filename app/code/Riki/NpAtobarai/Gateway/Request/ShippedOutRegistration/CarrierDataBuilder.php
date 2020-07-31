<?php

namespace Riki\NpAtobarai\Gateway\Request\ShippedOutRegistration;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class TransactionDataBuilder
 */
class CarrierDataBuilder implements BuilderInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Sales\Api\Data\TrackInterface[]
     */
    protected $minShipmentTrack = [];

    /**
     * CarrierDataBuilder constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Riki\NpAtobarai\Api\Data\TransactionInterface[] $transactions
     * @return mixed
     * @throws LocalizedException
     */
    public function build(array $transactions)
    {
        $transaction = isset($transactions['transaction']) ? $transactions['transaction'] : '';

        if (!$transaction instanceof \Riki\NpAtobarai\Api\Data\TransactionInterface) {
            throw new LocalizedException(__('Transaction must be an instance of NpTransaction'));
        }

        $shipment = $transaction->getShipment();
        $track = $this->getMinTrackOfShipment($shipment);
        $carrierCode = $track->getCarrierCode();
        if (!$carrierCode) {
            throw new LocalizedException(__('Carrier code must be provided'));
        }
        $pdCompanyCode = $this->getNpCarrierCode($carrierCode);
        $slipNo = $this->getSlipNo($carrierCode, $shipment);

        return [
            'pd_company_code' => $pdCompanyCode,
            'slip_no' => $slipNo,
        ];
    }

    /**
     * @param string $carrierCode
     * @return mixed
     * @throws LocalizedException
     */
    private function getNpCarrierCode($carrierCode)
    {
        return $this->getCarrierConfiguration($carrierCode, 'np_carrier_code');
    }

    /**
     * @param string $carrierCode
     * @return mixed
     * @throws LocalizedException
     */
    private function isSlipNoFixed($carrierCode)
    {
        return $this->getCarrierConfiguration($carrierCode, 'slip_no_fixed_status');
    }

    /**
     * @param string $carrierCode
     * @return mixed
     * @throws LocalizedException
     */
    private function getSlipNoFixedValue($carrierCode)
    {
        return $this->getCarrierConfiguration($carrierCode, 'slip_no_fixed_value');
    }

    /**
     * @param string $carrierCode
     * @param string $field
     * @return mixed
     * @throws LocalizedException
     */
    private function getCarrierConfiguration($carrierCode, $field)
    {
        if (!$carrierCode || !$field) {
            throw new LocalizedException(__('Carrier code and field configuration must be provided'));
        }

        $configPath = 'carriers/' . $carrierCode . '/' . $field;

        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @param string $carrierCode
     * @param \Magento\Sales\Api\Data\ShipmentInterface $shipment
     * @return null|string
     * @throws LocalizedException
     */
    private function getSlipNo($carrierCode, $shipment)
    {
        if ($this->isSlipNoFixed($carrierCode)) {
            return $this->getSlipNoFixedValue($carrierCode);
        }

        $minTrack = $this->getMinTrackOfShipment($shipment);

        if (!$minTrack->getTrackNumber()) {
            throw new LocalizedException(__('Min tracking number could not be found'));
        }

        return $minTrack->getTrackNumber();
    }

    /**
     * @param \Magento\Sales\Api\Data\ShipmentInterface $shipment
     * @return mixed
     * @throws LocalizedException
     */
    private function getMinTrackOfShipment($shipment)
    {
        if (!isset($this->minShipmentTrack[$shipment->getEntityId()])) {
            $minTrack = $shipment->getTracksCollection()
                ->setOrder('track_number', \Zend_Db_Select::SQL_ASC)
                ->setPageSize(1)
                ->getFirstItem();

            $this->minShipmentTrack[$shipment->getEntityId()] = $minTrack;
        }

        return $this->minShipmentTrack[$shipment->getEntityId()];
    }
}
