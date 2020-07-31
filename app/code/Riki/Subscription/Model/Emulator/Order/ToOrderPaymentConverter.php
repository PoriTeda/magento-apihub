<?php

namespace Riki\Subscription\Model\Emulator\Order;


class ToOrderPaymentConverter
    extends \Magento\Quote\Model\Quote\Payment\ToOrderPayment{

    public function __construct(
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper ,
        \Riki\Subscription\Model\Emulator\Order\Payment\Repository $emulatorOrderPaymentRepository
    )
    {
        parent::__construct($orderPaymentRepository, $objectCopyService, $dataObjectHelper);
        $this->orderPaymentRepository = $emulatorOrderPaymentRepository;
    }
}