<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Ui\Component\Listing\Column
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Bluecom\Scheduler\Ui\Component\Listing\Column;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Cron\Model\Schedule;
/**
 * Class ScheduleAction
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Ui\Component\Listing\Column
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class ScheduleAction extends Column
{
    /**
     * Class container
     *
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param   array $dataSource
     * @return  array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['schedule_id'])) {
                continue;
            }
            $item[$name]['delete'] = [
                'href' => $this->context->getUrl('scheduler/schedules/delete', [
                    'schedule_id' => $item['schedule_id']
                ]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Delete Item'),
                    'message' => __('Are you sure you want to delete a "%1" item?',['${ $.$data.job_code }'])
                ]
            ];
            if($item['status']==Schedule::STATUS_RUNNING)
            {
                $item[$name]['kill'] = [
                    'href' => $this->context->getUrl('scheduler/schedules/kill', [
                        'schedule_id' => $item['schedule_id']
                    ]),
                    'label' => __('Kill'),
                    'confirm' => [
                        'title' => __('Kill Item'),
                        'message' => __('Are you sure you want to kill a "%1" item?',['${ $.$data.job_code }'])
                    ]
                ];
            }
        }

        return $dataSource;
    }
}