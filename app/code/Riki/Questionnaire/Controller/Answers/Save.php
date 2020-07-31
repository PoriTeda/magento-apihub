<?php
namespace Riki\Questionnaire\Controller\Answers;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Riki\Questionnaire\Model\AnswersFactory;

/**
 * Class Save
 * @package Riki\Questionnaire\Controller\Answers
 */
class Save extends Action
{
    /**
     * @var AnswersFactory
     */
    protected $_answersFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data $helper
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * Save constructor.
     * 
     * @param Context $context
     * @param AnswersFactory $answersFactory
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        Context $context,
        AnswersFactory $answersFactory,
        \Magento\Framework\Json\Helper\Data $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        $this->_answersFactory = $answersFactory;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        parent::__construct($context);
    }

    /**
     * Save answer on order success page
     *
     * @return bool
     */
    public function execute()
    {
        $data = null;
        $httpBadRequestCode = 400;

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        try {
            $data = $this->helper->jsonDecode($this->getRequest()->getContent());
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$data || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        $response = [
            'errors' => false,
            'message' => __('Saved answers successful.')
        ];

        // save answers
        if (!empty($data)) {
            /** @var \Riki\Questionnaire\Model\Answers $answerModel */
            $answerModel = $this->_answersFactory->create();

            foreach ($data as $item) {

                $replyArr = [];
                $answerModel->setEnqueteId($item['enquete_id']);
                $answerModel->setCustomerId($item['customer_id']);
                $answerModel->setEntityId($item['order_id']);
                $answerModel->setEntityType(\Riki\Questionnaire\Model\Questionnaire::CHECKOUT_QUESTIONNAIRE);

                if (!empty($item['questions'])) {
                    foreach ($item['questions'] as $question) {
                        $answers = $question['answers'];
                        $questionId = $question['question_id'];
                        if (!empty($answers)) {
                            foreach ($answers as $answer) {
                                $content = $answer['content'];
                                $choices = $answer['choices'];

                                if ($content == '' || $content == null) {
                                    foreach ($choices as $choice) {
                                        $reply = [];
                                        if ($choice !== '0' && $choice !== null && $choice !== '') {
                                            $reply['question_id'] = $questionId;
                                            $reply['choice_id'] = $choice;
                                            $reply['content'] = $content; // null
                                            $replyArr[] = $reply;
                                        }
                                    }
                                } else {
                                    $reply = [];
                                    $reply['question_id'] = $questionId;
                                    $reply['choice_id'] = null;
                                    $reply['content'] = $content;
                                    $replyArr[] = $reply;
                                }

                            }
                        }
                    }
                }

                if (!empty($replyArr)) {
                    $answerModel->setAnswersReplys($replyArr);
                    try {
                        $answerModel->save();
                    } catch (\Exception $e) {
                        $response = [
                            'errors' => true,
                            'message' => $e->getMessage()
                        ];
                    }
                } else {
                    $response = [
                            'errors' => true,
                            'message' => __('Data answers empty.')
                        ];
                }

            }
        } else {
            $response = [
                'errors' => true,
                'message' => __('Data answers empty.')
            ];
        }
        if ($response['errors']) {
            $this->messageManager->addError($response['message']);
        } else {
            $this->messageManager->addSuccess($response['message']);
        }
         /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);

    }
}