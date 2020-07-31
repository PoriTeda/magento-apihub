<?php
namespace Riki\Rma\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\OfflinePayments\Model\Cashondelivery;
use Riki\Rma\Model\Config\Source\ReviewCc\Status;
use Riki\Rma\Model\Config\Source\ReviewCc\Item\Status as ItemStatus;

class ReviewCcManagement
{
    /** @var RmaManagement  */
    protected $rmaManagement;

    /** @var \Riki\Rma\Logger\ReviewCc\LoggerFactory  */
    protected $loggerFactory;

    /** @var ReviewCcFilter  */
    protected $reviewCcFilter;

    /** @var \Magento\Framework\App\RequestInterface  */
    protected $request;

    /** @var \Magento\Rma\Api\RmaRepositoryInterface  */
    protected $rmaRepository;

    /** @var \Riki\Rma\Helper\Amount  */
    protected $rmaAmountHelper;

    /** @var \Riki\Rma\Helper\Data  */
    protected $rikiRmaHelper;

    /**
     * @var AmountCalculator
     */
    protected $amountCalculator;

    /**
     * @var array
     */
    protected $defaultValues = [
        [
            'refund_allowed'    =>  0,
            'return_shipping_fee_adj' =>  0,
            'return_payment_fee_adj' =>  0,
            'total_cancel_point_adj' =>  0,
            'total_return_amount_adj' =>  0,
            'refund_without_product' =>  0,
            'total_return_point_adj' =>  0,
        ],
        [
            'refund_allowed'    =>  0,
            'return_shipping_fee_adj' =>  0,
            'return_payment_fee_adj' =>  0,
            'total_cancel_point_adj' =>  0,
            'total_return_amount_adj' =>  0,
            'refund_without_product' =>  0,
            'total_return_point_adj' =>  0,
        ]
    ];

    /**
     * @var array
     */
    protected $defaultItemValues = [
        'return_amount_adj' =>  0,
        'return_wrapping_fee_adj' =>  0
    ];

    /**
     * @var integer
     */
    protected $itemDefaultOverallValue = 0;

    /**
     * ReviewCcManagement constructor.
     * @param RmaManagement $rmaManagement
     * @param \Riki\Rma\Logger\ReviewCc\LoggerFactory $loggerFactory
     * @param ReviewCcFilter $reviewCcFilter
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param AmountCalculator $amountCalculator
     */
    public function __construct(
        \Riki\Rma\Model\RmaManagement $rmaManagement,
        \Riki\Rma\Logger\ReviewCc\LoggerFactory $loggerFactory,
        \Riki\Rma\Model\ReviewCcFilter $reviewCcFilter,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Rma\Model\AmountCalculator $amountCalculator
    )
    {
        $this->rmaManagement = $rmaManagement;
        $this->loggerFactory = $loggerFactory;
        $this->reviewCcFilter = $reviewCcFilter;
        $this->request = $request;
        $this->rmaRepository = $rmaRepository;
        $this->rmaAmountHelper = $amountCalculator->getAmountHelper();
        $this->rikiRmaHelper = $amountCalculator->getDataHelper();
        $this->amountCalculator = $amountCalculator;
    }

    /**
     * @return \Riki\Rma\Logger\ReviewCc\LoggerFactory
     */
    public function getLoggerFactory()
    {
        return $this->loggerFactory;
    }

    /**
     * @return array
     */
    public function getDefaultItemValues()
    {
        return $this->defaultItemValues;
    }

    /**
     * @return $this
     */
    public function approve(\Riki\Rma\Model\ReviewCc $reviewCc)
    {
        /** @var \Monolog\Logger $logger */
        $logger = $this->loggerFactory->create($reviewCc);
        $logger->info(__('Start to review'));

        $reviewCc->setData('status', Status::STATUS_RUNNING)
            ->setData('executed_from', new \Zend_Db_Expr('CURTIME()'))
            ->save();

        $this->rikiRmaHelper->setSkipNeedToSaveAgain(true);

        $numSuccess = 0;
        $numFailed = 0;

        /** @var \Riki\Rma\Model\ReviewCc\Item $reviewCcItem */
        foreach ($reviewCc->getItemCollection() as $reviewCcItem) {

            $rmaId = $reviewCcItem->getRmaId();

            $logger->info(__('RMA #%1', $rmaId));

            $itemStatus = ItemStatus::STATUS_SUCCESS;

            try {

                if ($rma = $this->validateRma($rmaId)) {

                    $this->prepareData($rma);

                    $this->rmaManagement->acceptRequest($rmaId);

                    $logger->info(__('RMA #%1 has approved successfully', $rmaId));

                    $numSuccess++;
                } else {
                    throw new LocalizedException(__('Return does not match conditions'));
                }

            } catch (\Exception $e) {
                $numFailed++;
                $itemStatus = ItemStatus::STATUS_FAILED;
                $logger->error($e->getMessage());
                $logger->critical($e);
            }

            try {
                $reviewCcItem->setData('status', $itemStatus)->save();
            } catch (\Exception $e) {
                $logger->critical($e);
            }
        }

        $reviewCc->setData('status', Status::STATUS_DONE)
            ->setData('total_success_returns', $numSuccess)
            ->setData('total_failed_returns', $numFailed)
            ->setData('executed_to', new \Zend_Db_Expr('CURTIME()'))
            ->save();

        $logger->info(__('Review completed'));

        return $this;
    }

    /**
     * @param $rma
     * @return bool|\Magento\Rma\Api\Data\RmaInterface
     */
    public function validateRma($rma)
    {

        if (!$rma instanceof \Magento\Rma\Model\Rma) {
            $rma = $this->rmaRepository->get($rma);
        }

        if ($this->reviewCcFilter->doMatchCondition($rma)) {
            return $rma;
        }

        return false;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return $this
     * @throws LocalizedException
     */
    public function prepareData(\Magento\Rma\Model\Rma $rma)
    {
        $this->request->setPostValue([]);

        $defaultValues = $this->getDefaultValue($rma);

        foreach ($defaultValues as $field => $value) {
            $rma->setData($field, $value);
        }

        $defaultValues['items'] = [];

        foreach ($rma->getItemsForDisplay() as $item) {
            foreach ($this->defaultItemValues as $field => $value) {
                $defaultValues['items'][$item->getId()][$field] = $value;
            }

            $defaultValues['items'][$item->getId()]['return_amount']
                = $this->rmaAmountHelper->getReturnAmountByItem($item);

            $defaultValues['items'][$item->getId()]['return_wrapping_fee']
                = $this->rmaAmountHelper->getReturnWrappingByItem($item);
        }

        $order = $this->rikiRmaHelper->getRmaOrder($rma);
        if (!$order instanceof \Magento\Sales\Model\Order) {
            throw new LocalizedException(__('Order can not be found'));
        }

        if (!$rma->getData('rma_shipment_number')
            && $order->getPayment()->getMethod() == Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE
        ) {
            $shipments = $order->getShipmentsCollection()->getItems();

            if (count($shipments) == 1) {
                $shipmentNumber = array_shift($shipments)->getIncrementId();
                $rma->setRmaShipmentNumber($shipmentNumber);
                $defaultValues['rma_shipment_number'] = $shipmentNumber;
            }
        }

        $amountFields = $this->amountCalculator->calculateReturnAmount($rma);

        $result = $defaultValues + $amountFields;
        $result['item_default_overall_value'] = $this->itemDefaultOverallValue;
        $this->request->setPostValue($result);

        return $this;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return mixed
     */
    protected function getDefaultValue(\Magento\Rma\Model\Rma $rma)
    {
        if ($this->rmaAmountHelper->getDataHelper()->isCodAndNpAtobaraiShipmentRejected($rma)) {
            return $this->defaultValues[0];
        }

        return $this->defaultValues[1];
    }
}