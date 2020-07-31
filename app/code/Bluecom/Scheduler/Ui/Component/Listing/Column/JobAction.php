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
/**
 * Class JobAction
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Ui\Component\Listing\Column
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class JobAction extends Column
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
            if (!isset($item['job_id'])) {
                continue;
            }
            //enable item
            if(!$item['active']) {
                $item[$name]['enable'] = [
                    'href' => $this->context->getUrl('scheduler/jobs/enable', [
                        'job_id' => $item['job_id']
                    ]),
                    'label' => __('Enable'),
                    'confirm' => [
                        'title' => __('Enable Item'),
                        'message' => __('Are you sure you want to enable job: "%1" ?', ['${ $.$data.job_code }'])
                    ]
                ];
            }
            //disable item
            if($item['active']) {
                $item[$name]['disable'] = [
                    'href' => $this->context->getUrl('scheduler/jobs/disable', [
                        'job_id' => $item['job_id']
                    ]),
                    'label' => __('Disable'),
                    'confirm' => [
                        'title' => __('Disable Item'),
                        'message' => __('Are you sure you want to disable job: "%1" ?', ['${ $.$data.job_code }'])
                    ]
                ];
                $item[$name]['schenow'] = [
                    'href' => $this->context->getUrl('scheduler/jobs/schenow', [
                        'job_id' => $item['job_id']
                    ]),
                    'label' => __('Schedule now'),
                    'confirm' => [
                        'title' => __('Schedule Item'),
                        'message' => __('Are you sure you want to schedule job: "%1" now',['${ $.$data.job_code }'])
                    ]
                ];
            }

        }
        return $dataSource;
    }
}