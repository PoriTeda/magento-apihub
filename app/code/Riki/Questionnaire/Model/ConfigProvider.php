<?php
namespace Riki\Questionnaire\Model;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Riki\Questionnaire\Model\Questionnaire;

/**
 * Class ConfigProvider
 * @package Riki\Questionnaire\Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var \Riki\Questionnaire\Model\QuestionnaireFactory
     */
    protected $questionnaireFactory;

    /**
     * @var \Riki\Questionnaire\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    protected $course;
    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * ConfigProvider constructor.
     * @param CheckoutSession $checkoutSession
     * @param QuestionnaireFactory $questionnaireFactory
     * @param \Riki\Questionnaire\Helper\Data $dataHelper
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course $course
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        QuestionnaireFactory $questionnaireFactory,
        \Riki\Questionnaire\Helper\Data $dataHelper,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $course,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache
    ) {
        $this->questionnaireFactory = $questionnaireFactory;
        $this->checkoutSession = $checkoutSession;
        $this->dataHelper = $dataHelper;
        $this->course = $course;
        $this->functionCache = $functionCache;
    }

    /**
     * Get Questionnaire for checkout each SKU product
     *
     * @return array
     */
    public function getConfig()
    {
        $quote = $this->checkoutSession->getQuote();
        // get from cache tag
        if ($this->functionCache->has($quote->getId())) {
            return $this->functionCache->load($quote->getId());
        }
        $items = $quote->getAllVisibleItems();
        $output = $skuArr = [];

        foreach ($items as $item) {
            $skuArr[] = $item->getSku();
        }
        //Check order Course
        if($quote->getRikiCourseId()){
            $courseCode = $this->course->getCourseInfoById($quote->getRikiCourseId(),['course_code']);
            $skuArr[] = $courseCode['course_code'];
        }
        if (!empty($skuArr)) {
            $itemData = $this->dataHelper->getQuestionnaireBySKUs(
                $skuArr,
                Questionnaire::VISIBILITY_CHECKOUT
            );
            if (!empty($itemData)) {
                $output['questionnaire'] = $itemData;
            }
        }
        if (empty($output)) {
            $output['questionnaire'] = $this->dataHelper->getQuestionnaireDefault(Questionnaire::VISIBILITY_CHECKOUT);
        }
        $this->dataHelper->logQuestionOrder('FO and Cart Id:'.$quote->getId(),$output);
        $this->functionCache->store($output, $quote->getId());
        return $output;
    }

}

