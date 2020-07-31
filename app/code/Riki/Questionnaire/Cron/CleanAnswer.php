<?php

namespace Riki\Questionnaire\Cron;

use Riki\Questionnaire\Model\Answers;

class CleanAnswer
{
    const XML_PATH_STATUS = 'riki_questionnaire/questionnaire_old_answer/questionnaire_old_answer_status';

    const XML_PATH_LIFE_TIME = 'riki_questionnaire/questionnaire_old_answer/questionnaire_old_answer_life_time';

    protected $_scopeConfig;

    protected $_answerCollectionFactory;

    protected $_loggerInterface;

    /**
     * Date
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Riki\Questionnaire\Model\ResourceModel\Answers\CollectionFactory $answerCollectionFactory
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_answerCollectionFactory = $answerCollectionFactory;
        $this->_date = $date;
        $this->_loggerInterface = $loggerInterface;
    }

    /**
     * Run process delete old answers
     *
     * @return $this
     */
    public function process()
    {
        if ((int)$this->_scopeConfig->getValue(self::XML_PATH_STATUS)) {
            $lifeTimeDays = (int)$this->_scopeConfig->getValue(self::XML_PATH_LIFE_TIME);

            $currentTimeStamp = $this->_date->timestamp();

            $maxDateFilter = $this->_date->date('Y-m-d H:i:s', $currentTimeStamp - ($lifeTimeDays * 86400));

            /** @var \Riki\Questionnaire\Model\ResourceModel\Answers\Collection $collection */
            $collection = $this->_answerCollectionFactory->create();
            $collection->getSelect()
                ->where(
                    'main_table.created_at < ?',
                    $maxDateFilter
                )->where('main_table.entity_type = ?',
                    Answers::QUESTIONNAIRE_ANSWER_TYPE_ORDER);

            foreach ($collection as $answer) {
                try {
                    $answer->delete();
                } catch (\Exception $e) {
                    $this->_loggerInterface->critical($e);
                }
            }
        }

        return $this;
    }
}
