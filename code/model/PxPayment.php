<?php

namespace Twohill\PXPay;

use SilverStripe\ORM\DataObject;

/**
 * Class PxPayment
 * @package Twohill\PXPay
 *
 * @property string $TxnType
 * @property string $MerchantReference
 * @property string $TxnId
 * @property string $TxnData1
 * @property string $TxnData2
 * @property string $TxnData3
 * @property string $EmailAddress
 * @property double $AmountInput
 * @property string $CurrencyInput
 * @property boolean $Processed
 *
 */
class PxPayment extends DataObject
{

    private static $table_name = "PXPayment";

    private static $singular_name = "Payment";

    private static $plural_name = "Payments";

    private static $db = [
        'TxnType' => 'Enum("Purchase, Auth", "Purchase")',
        'MerchantReference' => 'Varchar(64)',
        'TxnId' => 'Varchar(16)',
        'TxnData1' => 'Varchar(255)',
        'TxnData2' => 'Varchar(255)',
        'TxnData3' => 'Varchar(255)',
        'EmailAddress' => 'Varchar(255)',
        'AmountInput' => 'Currency',
        'CurrencyInput' => 'Varchar(5)',
        'Processed' => 'Boolean',
    ];


    private static $indexes = [
        'MerchantReference' => [
            'type' => 'unique',
            'columns' => ['MerchantReference'],
        ],
    ];

    private static $summary_fields = [
        'Created',
        'MerchantReference',
        'AmountInput',
        'Processed',
    ];
    
    /** Payment Express does not like more than 2 digits **/
    public function setAmountInput($value) 
    {
        return $this->setField('AmountInput', round($value, 2));
    }
}
