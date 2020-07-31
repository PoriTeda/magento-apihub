<?php
namespace Riki\Rma\Controller\Adminhtml;

abstract class Reason extends AbstractAction
{
    const ADMIN_RESOURCE = 'Riki_Rma::reason';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Api\ReasonRepositoryInterface
     */
    protected $reasonRepository;

    /**
     * Reason constructor.
     *
     * @param \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->reasonRepository = $reasonRepository;
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
        $result->addBreadcrumb(__('Reasons'), __('Reasons'));
        $result->getConfig()->getTitle()->prepend(__('Reasons'));

        return $result;
    }
}