<?php
namespace Riki\Rma\Controller\Adminhtml;

abstract class Returns extends \Riki\Rma\Controller\Adminhtml\AbstractAction
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_return_actions';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Model\RmaManagement
     */
    protected $rmaManagement;

    /**
     * Returns constructor.
     *
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Rma\Model\RmaManagement $rmaManagement
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Riki\Rma\Model\RmaManagement $rmaManagement,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->rmaRepository = $rmaRepository;
        $this->searchHelper = $searchHelper;
        $this->logger = $logger;
        $this->rmaManagement = $rmaManagement;

        parent::__construct($registry, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function initPageResult()
    {
        $result = parent::initPageResult();
        $result->addBreadcrumb(__('Return'), __('Return'));
        $result->getConfig()->getTitle()->prepend(__('Return'));

        return $result;
    }
}