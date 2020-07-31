<?php
namespace Riki\Subscription\Controller\Adminhtml\Product;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;

class SaveCourse extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    protected $_sessionQuote;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Backend\Model\Session\Quote $sessionQuote
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_sessionQuote = $sessionQuote;

    }
    public function execute()
    {
        $quote = $this->_sessionQuote->getQuote();
        try {
            if($frequencyID = $this->getRequest()->getParam('frequency_id' , false)){
                $quote->setRikiFrequencyId($frequencyID);
            }

            if( $courseId = $this->getRequest()->getParam('course_id' , false) ){
                $quote->setRikiCourseId($courseId);
            }
            $quote->save();
        }
        catch (\Exception $e){
            throwException($e);
            return $this->getResponse()
                ->setBody(\Zend_Json::encode(array(
                    "success" => false
                )))
                ->sendResponse();
        }

        return $this->getResponse()
            ->setBody(\Zend_Json::encode(array(
                "success" => true
            )))
            ->sendResponse();
    }
}