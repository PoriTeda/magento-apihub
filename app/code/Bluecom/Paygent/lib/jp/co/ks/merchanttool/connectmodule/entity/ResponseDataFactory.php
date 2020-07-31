<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity;

use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModuleResources;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity\ReferenceResponseDataImpl;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity\PaymentResponseDataImpl;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity\FilePaymentResponseDataImpl;

/**
 * �����d�������p�I�u�W�F�N�g�쐬�N���X
 *
 * @version $Revision: 15878 $
 * @author $Author: orimoto $
 */
class ResponseDataFactory
{

    /**
     * ResponseData ���쐬
     *
     * @param kind
     * @return ResponseData
     */
    static function create($kind)
    {
        $resData = null;
        $masterFile = null;

        $masterFile = PaygentB2BModuleResources::getInstance();

        // Create ResponseData
        if (PaygentB2BModule__TELEGRAM_KIND_FILE_PAYMENT_RES == $kind) {
            // �t�@�C�����ό��ʏƉ�̏ꍇ
            $resData = new FilePaymentResponseDataImpl();
        } elseif ($masterFile->isTelegramKindRef($kind)) {
            // �Ɖ�̏ꍇ
            $resData = new ReferenceResponseDataImpl();
        } else {
            // �Ɖ�ȊO�̏ꍇ
            $resData = new PaymentResponseDataImpl();
        }

        return $resData;
    }

}

?>