<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Riki\Questionnaire\Model\AnswersFactory;
use Psr\Log\LoggerInterface as Logger;

class CheckoutSubmitAllAfterFrontEnd implements ObserverInterface
{
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $quoteSession;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Riki\Questionnaire\Helper\Admin
     */
    protected $questionnaireAdminHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * CheckoutSubmitAllAfterFrontEnd constructor.
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Riki\Questionnaire\Helper\Admin $questionnaireAdminHelper
     * @param Logger $loggerInterface
     */

    public function __construct(
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Magento\Framework\App\RequestInterface $request,
        \Riki\Questionnaire\Helper\Admin $questionnaireAdminHelper,
        Logger $loggerInterface
    ) {
        $this->quoteSession = $quoteSession;
        $this->request = $request;
        $this->questionnaireAdminHelper = $questionnaireAdminHelper;
        $this->logger = $loggerInterface;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        /**
         * Save questionnaire
         */
        try {
            $dataQuestionnaire = $this->request->getParam('questionnaire');
            if ($dataQuestionnaire !='') {
                $dataAnswers = \Zend_Json::decode($dataQuestionnaire);
                if (isset($dataAnswers) && !empty($dataAnswers)) {
                    $this->questionnaireAdminHelper->saveAnswersCreatedOrderFrontEnd($order, $dataAnswers);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
