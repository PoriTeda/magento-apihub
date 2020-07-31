<?php
namespace Riki\Rma\Controller\Adminhtml;

abstract class Refund extends AbstractAction
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_refund_actions';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * @var \Riki\Rma\Model\RefundManagement
     */
    protected $refundManagement;

    /**
     * Refund constructor.
     *
     * @param \Riki\Rma\Model\RefundManagement $refundManagement
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Riki\Rma\Model\RefundManagement $refundManagement,
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->refundManagement = $refundManagement;
        $this->rmaRepository = $rmaRepository;
        $this->searchHelper = $searchHelper;
        $this->logger = $logger;
        parent::__construct($registry, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function initPageResult()
    {
        $result = parent::initPageResult();
        $result->addBreadcrumb(__('Refund'), __('Refund'));
        $result->getConfig()->getTitle()->prepend(__('Refund'));

        return $result;
    }
}