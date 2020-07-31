<?php
namespace Riki\Rma\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Riki\Rma\Model\Config\Source\Rma\ReturnStatus;
use Riki\Framework\Helper\Logger\LoggerBuilder;

class LogCompletedReturnChangedHistory implements ObserverInterface
{
    /**
     * @var LoggerBuilder
     */
    protected $loggerBuilder;

    /**
     * @var array
     */
    protected $amountFields = [
        'customer_point_balance',
        'returnable_point_amount',
        'return_shipping_fee',
        'return_shipping_fee_adjusted',
        'return_payment_fee',
        'return_payment_fee_adjusted',
        'total_cancel_point',
        'total_cancel_point_adjusted',
        'earned_point',
        'total_return_amount',
        'total_return_amount_adjusted',
        'total_return_point',
        'total_return_point_adjusted'
    ];

    /**
     * LogCompletedReturnChangedHistory constructor.
     * @param LoggerBuilder $loggerBuilder
     */
    public function __construct(
        LoggerBuilder $loggerBuilder
    ) {
        $this->loggerBuilder = $loggerBuilder;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Rma\Model\Rma $rma */
        $rma = $observer->getRma();

        if (!$rma->dataHasChangedFor('return_status')
            && $rma->getReturnStatus() == ReturnStatus::COMPLETED
        ) {
            foreach ($this->amountFields as $field) {
                if ($rma->dataHasChangedFor($field)) {
                    /** @var \Riki\Framework\Helper\Logger\Monolog $logger */
                    $logger = $this->loggerBuilder
                        ->setName('CompletedReturnChanged')
                        ->setFileName('amount.log')
                        ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
                        ->create();

                    $logger->critical(new LocalizedException(__(
                        'The return #%1 has been updated amount data. Data before save: %2. Data after save: %3',
                        $rma->getIncrementId(),
                        json_encode($this->getDataBeforeSave($rma)),
                        json_encode($this->getDataAfterSaved($rma))
                    )));

                    break;
                }
            }
        }
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return array
     */
    protected function getDataBeforeSave(\Magento\Rma\Model\Rma $rma)
    {
        $result = [];

        foreach ($this->amountFields as $field) {
            $result[$field] = $rma->getOrigData($field);
        }

        return $result;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return array
     */
    protected function getDataAfterSaved(\Magento\Rma\Model\Rma $rma)
    {
        $result = [];

        foreach ($this->amountFields as $field) {
            $result[$field] = $rma->getData($field);
        }

        return $result;
    }
}
