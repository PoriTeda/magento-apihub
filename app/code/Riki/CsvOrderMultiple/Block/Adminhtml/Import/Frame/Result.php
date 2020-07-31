<?php
namespace Riki\CsvOrderMultiple\Block\Adminhtml\Import\Frame;

class Result extends \Magento\ImportExport\Block\Adminhtml\Import\Frame\Result
{

    /**
     * Import button HTML for append to message.
     *
     * @return string
     */
    public function getImportButtonHtml()
    {
        return '&nbsp;&nbsp;<button onclick="rikiCsvOrderUpload.startImport(\'' .
            $this->getImportStartUrl() .
            '\', \'' .
            \Magento\ImportExport\Model\Import::FIELD_NAME_SOURCE_FILE .
            '\');" class="scalable save"' .
            ' type="button"><span><span><span>' .
            __(
                'Import'
            ) . '</span></span></span></button>';
    }

    /**
     * Import start action URL.
     *
     * @return string
     */
    public function getImportStartUrl()
    {
        return $this->getUrl('csvOrderMultiple/import/start');
    }

}
