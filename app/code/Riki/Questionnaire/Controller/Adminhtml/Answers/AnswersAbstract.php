<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Answers;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Riki\Questionnaire\Model\QuestionFactory;
use Riki\Questionnaire\Model\QuestionnaireFactory;
use Riki\Questionnaire\Model\ChoiceFactory;
use Riki\Questionnaire\Model\AnswersFactory;

abstract class AnswersAbstract extends Action
{
    const ADMIN_RESOURCE = 'Riki_Questionnaire::answers';

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
     * @var QuestionFactory
     */
    protected $questionFactory;

    /**
     * @var ChoiceFactory
     */
    protected $choiceFactory;

    protected $_answersFactory;

    /**
     * AnswersAbstract constructor.
     *
     * @param Action\Context $context
     * @param Registry $registry
     * @param ForwardFactory $resultForwardFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        ForwardFactory $resultForwardFactory,
        LoggerInterface $logger,
        QuestionnaireFactory $questionnaireFactory,
        QuestionFactory $questionFactory,
        AnswersFactory $answersFactory,
        ChoiceFactory $choiceFactory
    )
    {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->questionnaireFactory = $questionnaireFactory;
        $this->questionFactory = $questionFactory;
        $this->choiceFactory = $choiceFactory;
        $this->_answersFactory = $answersFactory;

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
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->addBreadcrumb(__('Questionnaire Answers'), __('Questionnaire Answers'));
        $resultPage->getConfig()->getTitle()->prepend(__('Questionnaire Answers'));
        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
