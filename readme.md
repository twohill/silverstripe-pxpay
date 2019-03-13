# silverstripe-pxpay

A module that integrates your controller with Payment Express.

## Requirements
 * SilverStripe ^4
 * php-curl
 * php-simplexml


## Installation
Using composer

```
composer require twohill/silverstripe-pxpay
```

## License
BSD 3-Clause "New" or "Revised" License. See [License](license.md)


## Example configuration

Configure your PxPayment details in a config yml file. You can specify different settings for `dev` or `live` modes.
A useful option for doing this is to use your sandbox credentials when in `dev` mode.

```yaml

---
Only:
  environment: 'live'
---
Twohill\PXPay\PxPaymentController:
  PxPayURL: https://sec.paymentexpress.com/pxaccess/pxpay.aspx
  PxPayUserId: MyUserID
  PxPayKey: xxxxxx

---
Only:
  environment: 'dev'
---
Twohill\PXPay\PxPaymentController:
  PxPayURL: https://sec.paymentexpress.com/pxaccess/pxpay.aspx
  PxPayUserId: MyUserID_dev
  PxPayKey: xxxxxx
  
```

## Example usage

Return the `PxPaymentController` as the result of a payment method

```php

class MyPageController extends PageController
{
    private static $allowed_actions = [
        'thankyou',
        'pay_order',
        'unsuccessful',
    ];
    
    public function thankyou(HTTPRequest $request)
    {
        $content = '';
        if ($request->getSession()->get('OrderID')) {
            $order = Order::get()->byID($request->getSession()->get('OrderID'));

            if ($order) {
           
                $payment = $order->Payment();

                if ($payment && $payment->TxnId) {
                    if ($payment->Processed) {
                        $sendEmail = false;
                    } else {
                        $payment->Processed = true;
                        $payment->write();
                    }
                } else {
                    $this->redirect($this->Link('pay-order/submit'));
                }
                $content = $this->ThankYouForPayingContent; // From MyPage $db
            }

            $request->getSession()->clear("OrderID");
            return $this->customise(new ArrayData([
                'Content' => DBField::create_field('HTMLFragment', $content),
                'Form' => ''
            ]));
        }
        return $this->redirect($this->Link());
    }
    /**
     * Process the payment
     *
     * @param HTTPRequest $request
     * @return PxPaymentController
     * @throws ValidationException
     */
    public function pay_order(HTTPRequest $request)
    {
    
        // Load the payment details somehow
        $payment = null;

        if ($request->getSession()->get('OrderID')) {
           
            $order = Order::get()->byID($request->getSession()->get('OrderID'));

            if ($order) {
                $payment = new PxPayment();
                $payment->TxnType = "Purchase";
                $payment->MerchantReference = $order->InvoiceNumber;
                $payment->TxnData1 = $order->CompanyName;
                $payment->TxnData2 = $order->Address;
                $payment->TxnData3 = $order->City;
                $payment->EmailAddress = $order->Contact()->Email;
                $payment->AmountInput = $order->Total;
                $payment->CurrencyInput = "NZD";
                $payment->InvoiceID = $order->ID;
                $payment->write();
                }
            }
        }
        return new PxPaymentController($this, $request, $payment, $this->Link("thankyou"), $this->Link("unsuccessful"));
    }

    /** 
     * Action when payment is unsuccessful. 
     */
    public function unsuccessful(HTTPRequest $request)
    {
        if ($request->getSession()->get('OrderID')) {
            $order = Order::get()->byID($request->getSession()->get('OrderID'));
            if ($order) {
                return $this->customise([
                    'Content' => DBField::create_field('HTMLFragment', $this->UnsuccessfulContent),
                    'Form' => ''
                ]);
            }
        }
        return $this->redirect($this->Link());
    }


}

```

## Maintainers
 * Al Twohill <al@twohill.nz>
 
## Bugtracker
Bugs are tracked in the issues section of this repository. Before submitting an issue please read over 
existing issues to ensure yours is unique. 
 
If the issue does look like a new bug:
 
 - Create a new issue
 - Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots 
 and screencasts can help here.
 - Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version, 
 Operating System, any installed SilverStripe modules.
 
Please report security issues to the module maintainers directly. Please don't file security issues in the bugtracker.
 
## Development and contribution
If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.
