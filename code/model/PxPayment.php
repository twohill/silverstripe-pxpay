<?php

namespace Twohill\PXPay;

use SilverStripe\ORM\DataObject;

class PxPayment extends DataObject {

    private static $singular_name = "Payment";
    
    private static $db = array(
        'TxnType' => 'Enum("Purchase, Auth", "Purchase")',
        'PaymentMethod' => 'Enum("CreditCard, Bank Transfer, Invoice", "CreditCard")',
        'MerchantReference' => 'Varchar(64)',
        'TxnId' => 'Varchar(16)',
        'TxnData1' => 'Varchar(255)',
        'TxnData2' => 'Varchar(255)',
        'TxnData3' => 'Varchar(255)',
        'EmailAddress' => 'Varchar(255)',
        'AmountInput' => 'Currency',
        'CurrencyInput' => 'Varchar(5)',
        'Processed' => 'Boolean',
    );

    private static $has_one = array(
        'ExhibitorRegistration' => 'ExhibitorRegistration'
    );
    private static $indexes = array(
        'MerchantReference' => array(
            'type' => 'unique',
            'value' => 'MerchantReference',
        ),
    );

    private static $summary_fields = array(
        'Created',
        'PaymentMethod',
        'MerchantReference',
        'AmountInput',
        'Processed',
    );
}