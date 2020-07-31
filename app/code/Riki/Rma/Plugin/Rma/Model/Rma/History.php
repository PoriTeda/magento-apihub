<?php
namespace Riki\Rma\Plugin\Rma\Model\Rma;

use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;
use Riki\Rma\Api\Data\Rma\RefundStatusInterface;

/**
 * @deprecated
 */
class History
{
    /**
     * @var \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface
     */
    protected $historyRepository;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\ReturnStatus
     */
    protected $returnStatusSource;

    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\RefundStatus
     */
    protected $refundStatusSource;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * History constructor.
     *
     * @param \Riki\Rma\Model\Config\Source\Rma\RefundStatus $refundStatusSource
     * @param \Riki\Rma\Model\Config\Source\Rma\ReturnStatus $returnStatusSource
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface $historyRepository
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     */
    public function __construct(
        \Riki\Rma\Model\Config\Source\Rma\RefundStatus $refundStatusSource,
        \Riki\Rma\Model\Config\Source\Rma\ReturnStatus $returnStatusSource,
        \Magento\Framework\App\RequestInterface $request,
        \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface $historyRepository,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\Framework\Helper\Datetime $datetimeHelper
    ) {
        $this->refundStatusSource = $refundStatusSource;
        $this->returnStatusSource = $returnStatusSource;
        $this->request = $request;
        $this->historyRepository = $historyRepository;
        $this->datetimeHelper = $datetimeHelper;
        $this->authSession = $authSession;
    }

    /**
     * Save history
     *
     * @param \Magento\Rma\Model\Rma $subject
     *
     * @return mixed[]
     */
    public function beforeAfterSave(\Magento\Rma\Model\Rma $subject)
    {
        $this->processReturnStatus($subject);
        $this->processRefundStatus($subject);

        return [];
    }


    /**
     * {@inheritdoc}
     */
    public function execute($rma)
    {
        if (!$rma instanceof \Magento\Rma\Model\Rma) {
            return $rma;
        }

        $rma = $this->processReturnStatus($rma);
        $rma = $this->processRefundStatus($rma);

        return $rma;
    }

    /**
     * Execute for return_status
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return \Magento\Rma\Model\Rma
     */
    protected function processReturnStatus(\Magento\Rma\Model\Rma $rma)
    {
        if (!$rma->dataHasChangedFor('return_status')) {
            return $rma;
        }

        if ($adminUser = $this->authSession->getUser()) {
            $comment = __('Return status changed to %1 by %2', $this->returnStatusSource->getLabel($rma->getReturnStatus()), $adminUser->getUserName());
        } else {
            $comment = __('Return status changed to %1', $this->returnStatusSource->getLabel($rma->getReturnStatus()));
        }

        $history = $this->historyRepository->createFromArray([
            'rma_entity_id' => $rma->getId(),
            'comment' => $comment,
            'created_at' => $this->datetimeHelper->toDb(),
            'is_admin' => true,
        ]);
        $this->historyRepository->save($history);

        if ($rma->getData('return_status') == ReturnStatusInterface::CS_FEEDBACK_REJECTED) {
            $comment = $this->request->getParam('comment');
            if (isset($comment['comment']) && $comment['comment']) {
                $history = $this->historyRepository->createFromArray([
                    'rma_entity_id' => $rma->getId(),
                    'comment' => $comment['comment'],
                    'created_at' => $this->datetimeHelper->toDb(),
                    'is_admin' => true,
                    'status' => 'pending'
                ]);
                $this->historyRepository->save($history);
            }
        }

        return $rma;
    }

    /**
     * Execute for refund_status
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return \Magento\Rma\Model\Rma
     */
    protected function processRefundStatus(\Magento\Rma\Model\Rma $rma)
    {
        if (!$rma->dataHasChangedFor('refund_status')) {
            return $rma;
        }

        if ($adminUser = $this->authSession->getUser()) {
            $comment = __('Refund status changed to %1 by %2', $this->refundStatusSource->getLabel($rma->getRefundStatus()), $adminUser->getUserName());
        } else {
            $comment = __('Refund status changed to %1', $this->refundStatusSource->getLabel($rma->getRefundStatus()));
        }

        $history = $this->historyRepository->createFromArray([
            'rma_entity_id' => $rma->getId(),
            'comment' => $comment,
            'created_at' => $this->datetimeHelper->toDb(),
            'is_admin' => true,
            'status' => $rma->getReFundStatus()
        ]);
        $this->historyRepository->save($history);

        return $rma;
    }
}