<?php

namespace Riki\Questionnaire\Controller\Adminhtml\Questions;

use Riki\Questionnaire\Model\QuestionnaireAnswer;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Save
 * @package Riki\Questionnaire\Controller\Adminhtml\Questions
 */
class Save extends QuestionsAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Questionnaire::save');
    }

    /**
     * Save Questionnaire action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $redirectBack = $this->getRequest()->getParam('back', false);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if data sent
        $data = $this->getRequest()->getPostValue('questionnaire');
        if (!empty($data)) {
            $id = isset($data['enquete_id']) ? $data['enquete_id'] : '';
            /** @var \Riki\Questionnaire\Model\Questionnaire $questionnaire */
            $questionnaire = $this->questionnaireFactory->create();
            if ($id && !$questionnaire->load($id)) {
                $this->messageManager->addError(__('This questionnaire no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            /**
             * Check Question data
             */
            if (!isset($data['questions'])) {
                $this->messageManager->addError(__('The question option is empty.'));
                $this->_session->setFormData($data);
                return $resultRedirect->setPath('*/*/edit', ['enquete_id' => $id]);
            }
            if ((isset($data['enquete_type']) && $data['enquete_type']
                    == QuestionnaireAnswer::FIELD_ENTITY_TYPE_ORDER)
                || ($questionnaire->getId() && $questionnaire->getEntityType()
                    == QuestionnaireAnswer::FIELD_ENTITY_TYPE_ORDER)
            ) {
                if (isset($data['linked_product_sku']) && $data['linked_product_sku']) {
                    //verify product
                    $sku = $courseCode = $data['linked_product_sku'];
                    try {
                        $product = $this->productRepository->get($sku);
                    } catch (NoSuchEntityException $e) {
                        $product = false;
                    }
                    //verify subscription course
                    $courseCollection = $this->courseFactory->create()->getCollection();
                    $courseCollection->addFieldToFilter('course_code', trim($courseCode));
                    if (!$product && !$courseCollection->getSize()) {
                        $this->messageManager->addError(__('The SKU or Subscription code does not exist.'));
                        return $resultRedirect->setPath('*/*/edit', ['enquete_id' => $id]);
                    }
                }
            } else {
                //reset data
                unset($data['start_date']);
                unset($data['end_date']);
                unset($data['linked_product_sku']);
                unset($data['visible_on_checkout']);
                unset($data['visible_on_order_success_page']);
            }
            // try to save it
            try {

                /**
                 * Initialize questionnaire questions
                 */
                if (isset($data['questions'])) {
                    $questionnaire->setQuestionnaireQuestions($data['questions']);
                    unset($data['questions']);
                }

                $questionnaire->addData($data);

                //Set default priority
                if (!$questionnaire->getPriority()) {
                    $questionnaire->setPriority(null);
                }
                // save the data
                $questionnaire->save();

                // display success message
                $this->messageManager->addSuccess(__('You saved the questionnaire.'));
                // clear previously saved data from session
                $this->_session->setFormData(false);
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // save data in session
                $this->_session->setFormData($data);
            }
            return $redirectBack ?
                $resultRedirect->setPath('*/*/edit', ['enquete_id' => $questionnaire->getId()])
                : $resultRedirect->setPath('*/*/');
        } else {
            $this->messageManager->addError('No data to save');
            return $resultRedirect->setPath('*/*/');
        }
    }
}
