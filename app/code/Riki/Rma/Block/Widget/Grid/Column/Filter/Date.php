<?php
namespace Riki\Rma\Block\Widget\Grid\Column\Filter;

class Date extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Date
{
    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * Date constructor.
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Framework\DB\Helper $resourceHelper
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface $dateTimeFormatter
     * @param array $data
     */
    public function __construct(
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface $dateTimeFormatter,
        array $data = []
    ){
        $this->datetimeHelper = $datetimeHelper;
        parent::__construct($context, $resourceHelper, $mathRandom, $localeResolver, $dateTimeFormatter, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @param array|string|int|float $value
     * @return $this
     */
    public function setValue($value)
    {
        if (isset($value['locale'])) {
            $timeFormat = $this->datetimeHelper->getTimeFormat(\IntlDateFormatter::SHORT);
            if (!empty($value['from'])) {
                $value['orig_from'] = $value['from'];
                $today = $this->datetimeHelper->getToday()->setTime(0,0,0);
                $value['from'] = $this->_convertDate($value['from'] . ' ' . $today->format($timeFormat));
            }
            if (!empty($value['to'])) {
                $today = $this->datetimeHelper->getToday()->setTime(23,59,59);
                $value['orig_to'] = $value['to'];
                $value['to'] = $this->_convertDate($value['to'] . ' ' . $today->format($timeFormat));
            }
        }
        if (empty($value['from']) && empty($value['to'])) {
            $value = null;
        }
        $this->setData('value', $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $date
     * @return \DateTime
     */
    protected function _convertDate($date)
    {
        $timezone = $this->getColumn()->getTimezone() !== false ? $this->_localeDate->getConfigTimezone() : 'UTC';
        $adminTimeZone = new \DateTimeZone($timezone);
        $formatter = new \IntlDateFormatter(
            $this->localeResolver->getLocale(),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            $adminTimeZone
        );
        $simpleRes = new \DateTime(null, $adminTimeZone);
        $simpleRes->setTimestamp($formatter->parse($date));
        $simpleRes->setTimezone(new \DateTimeZone('UTC'));
        return $simpleRes;
    }
}