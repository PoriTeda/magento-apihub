<?php
namespace Riki\Rma\Cron;

use Magento\Checkout\Exception;
use Magento\Framework\Exception\LocalizedException;
use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;
use Riki\Rma\Model\Config\Source\RequestedMassAction\Status;
use Riki\Rma\Model\Config\Source\Rma\MassAction as MassActionOption;
use Riki\NpAtobarai\Exception\NotRefundPaidTransactionException;

class MassAction
{
    /**
     * @var \Riki\Rma\Model\ResourceModel\RequestedMassAction\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Riki\Rma\Model\RmaManagement
     */
    protected $rmaManager;

    /**
     * @var \Riki\Rma\Model\AmountCalculator
     */
    protected $amountCalculator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Rma\Model\ApproveCcManagement
     */
    private $approveCcManagement;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Riki\Rma\Model\ReviewCcManagement
     */
    protected $reviewCcManagement;

    /**
     * MassAction constructor.
     * @param \Riki\Rma\Model\ResourceModel\RequestedMassAction\CollectionFactory $collectionFactory
     * @param \Riki\Rma\Model\RmaManagement $rmaManager
     * @param \Riki\Rma\Model\AmountCalculator $amountCalculator
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Rma\Model\ApproveCcManagement $approveCcManagement
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Riki\Rma\Model\ReviewCcManagement $reviewCcManagement
     */
    public function __construct(
        \Riki\Rma\Model\ResourceModel\RequestedMassAction\CollectionFactory $collectionFactory,
        \Riki\Rma\Model\RmaManagement $rmaManager,
        \Riki\Rma\Model\AmountCalculator $amountCalculator,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Rma\Model\ApproveCcManagement $approveCcManagement,
        \Magento\Framework\App\RequestInterface $request,
        \Riki\Rma\Model\ReviewCcManagement $reviewCcManagement
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->rmaManager = $rmaManager;
        $this->amountCalculator = $amountCalculator;
        $this->logger = $logger;
        $this->approveCcManagement = $approveCcManagement;
        $this->request = $request;
        $this->reviewCcManagement = $reviewCcManagement;
    }

    /**
     *
     */
    public function process()
    {
        $callAbles = [
            MassActionOption::REVIEW_BY_CC =>  'acceptRequest',
            MassActionOption::APPROVE_BY_CS =>  'approve',
            MassActionOption::APPROVE_BY_CC =>  'approveRequest',
            MassActionOption::REJECT_REQUEST =>  'rejectRequest',
            MassActionOption::DENY_REQUEST =>  'denyRequest',
            MassActionOption::REJECT =>  'reject',
            MassActionOption::CLOSE_REQUEST =>  'close'
        ];

        /** @var \Riki\Rma\Model\ResourceModel\RequestedMassAction\Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', Status::STATUS_WAITING);

        /** @var \Riki\Rma\Model\RequestedMassAction $item */
        foreach ($collection as $item) {
            $action = $item->getAction();

            try {
                if (!isset($callAbles[$action])) {
                    throw new LocalizedException(__('Action request is invalid.'));
                }

                $this->request->setPostValue([]);

                $item->setExecutedAt(new \Zend_Db_Expr('NOW()'));
                $item->setStatus(Status::STATUS_SUCCESS);

                $rma = $item->getRma();
                $rma->setData('mass_action', $action);
                $amountData = $this->amountCalculator->calculateReturnAmount($rma);

                foreach ($amountData as $field => $value) {
                    $rma->setData($field, $value);
                }

                call_user_func([self::class, $callAbles[$action]], $rma);

                $amountData['customer_point_balance'] = $rma->getData('customer_point_balance');
                $item->setData('amounts_data', \Zend_Json::encode($amountData));
            } catch (\Exception $e) {
                $item->setMessage($e->getMessage());
                $item->setStatus(Status::STATUS_FAILURE);
            }

            try {
                $item->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     * @throws LocalizedException
     */
    protected function acceptRequest(\Magento\Rma\Model\Rma $rma)
    {
        $this->reviewCcManagement->prepareData($rma);

        return $this->rmaManager->acceptRequest($rma->getId());
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     * @throws \Exception
     */
    protected function approve(\Magento\Rma\Model\Rma $rma)
    {
        $rma->removeExtensionData('need_save_again')->save();

        $rma->save();

        try {
            $isRejected = false;
            $this->rmaManager->approve($rma->getId());
        } catch (NotRefundPaidTransactionException $e) {
            $isRejected = true;
        }

        if ($isRejected) {
            try {
                $message = __('The return status was changed to CS feedback - Rejected as it was already paid');
                $this->request->setPostValue('comment', ['comment' => $message]);
                $this->rmaManager->reject($rma->getId());
            } catch (\Exception $e) {
                throw $e;
            }

            // If rejected successfully, need to throw this [$message] to add into item riki_rma_action_queue
            throw new NotRefundPaidTransactionException($message);
        }

        return true;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     * @throws LocalizedException
     */
    protected function approveRequest(\Magento\Rma\Model\Rma $rma)
    {
        $rma->removeExtensionData('need_save_again')->save();
        if (ReturnStatusInterface::CREATED == $rma->getData('return_status')) {
            // add RMA items to post variable when approve by cc via mass action from a rma just created
            $this->approveCcManagement->prepareData($rma);
        }
        return $this->rmaManager->approveRequest($rma->getId());
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     * @throws \Exception
     */
    protected function rejectRequest(\Magento\Rma\Model\Rma $rma)
    {
        $rma->removeExtensionData('need_save_again')->save();

        return $this->rmaManager->rejectRequest($rma->getId());
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     * @throws LocalizedException
     */
    protected function denyRequest(\Magento\Rma\Model\Rma $rma)
    {
        $rma->removeExtensionData('need_save_again')->save();

        return $this->rmaManager->denyRequest($rma->getId());
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     * @throws \Exception
     */
    protected function reject(\Magento\Rma\Model\Rma $rma)
    {
        $this->request->setPostValue('comment', ['comment' => 'Rejected by mass action']);

        $rma->removeExtensionData('need_save_again')->save();

        $result = $this->rmaManager->reject($rma->getId());

        return $result;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     * @throws LocalizedException
     */
    protected function close(\Magento\Rma\Model\Rma $rma)
    {
        $rma->removeExtensionData('need_save_again')->save();

        return $this->rmaManager->close($rma->getId());
    }
}