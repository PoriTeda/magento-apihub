<?php
namespace Riki\Subscription\Controller\Adminhtml\Profile;

abstract class AbstractProfile extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Riki_Subscription::profile_edit';

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * AbstractProfile constructor.
     *
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->profileFactory = $profileFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->profileRepository = $profileRepository;
        parent::__construct($context);
    }

    /**
     * Init the page result
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initPageResult()
    {
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}