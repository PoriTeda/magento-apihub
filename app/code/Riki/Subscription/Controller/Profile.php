<?php
namespace Riki\Subscription\Controller;

abstract class Profile extends AbstractAction
{
    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_profileData;

    /**
     * Profile constructor.
     *
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Framework\Registry $registry
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Action\Context $context,
        \Riki\Subscription\Helper\Profile\Data $profileData
    ) {
        $this->profileFactory = $profileFactory;
        $this->profileRepository = $profileRepository;
        $this->_profileData = $profileData;
        parent::__construct($customerSession, $customerUrl, $registry, $logger, $context);
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
}