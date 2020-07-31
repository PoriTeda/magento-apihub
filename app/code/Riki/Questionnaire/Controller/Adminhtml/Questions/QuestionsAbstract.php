<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Questions;

use Magento\Backend\App\Action;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Riki\Questionnaire\Model\QuestionnaireFactory;
use Riki\SubscriptionCourse\Model\CourseFactory;

abstract class QuestionsAbstract extends Action
{
    const ADMIN_RESOURCE = 'Riki_Questionnaire::questionnaire';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var QuestionnaireFactory
     */
    protected $questionnaireFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CourseFactory
     */
    protected $courseFactory;

    /**
     * QuestionsAbstract constructor.
     * @param Action\Context $context
     * @param Registry $registry
     * @param ForwardFactory $resultForwardFactory
     * @param LoggerInterface $logger
     * @param QuestionnaireFactory $questionnaireFactory
     * @param ProductRepositoryInterface $productRepository
     * @param CourseFactory $courseFactory
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        ForwardFactory $resultForwardFactory,
        LoggerInterface $logger,
        QuestionnaireFactory $questionnaireFactory,
        ProductRepositoryInterface $productRepository,
        CourseFactory $courseFactory
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->questionnaireFactory = $questionnaireFactory;
        $this->productRepository = $productRepository;
        $this->courseFactory = $courseFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initResultPage()
    {
        /**
         * @var \Magento\Backend\Model\View\Result\Page $resultPage
         */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_Questionnaire::questionnaire');
        $resultPage->addBreadcrumb(__('Questionnaire'), __('Questionnaire'));
        $resultPage->getConfig()->getTitle()->prepend(__('Questionnaire'));
        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Questionnaire::questionnaire');
    }
}
