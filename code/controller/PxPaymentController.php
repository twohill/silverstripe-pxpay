<?php

namespace Twohill\PXPay;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Control\NestedController;
use SilverStripe\ORM\ValidationException;


class PxPaymentController extends Controller implements NestedController
{
    protected $parentController;
    protected $payment;
    protected $urlSegment;
    protected $successURL;
    protected $failURL;

    public function __construct(
        Controller $parentController,
        HTTPRequest $request,
        PxPayment $payment = null,
        $successURL = null,
        $failURL = null
    ) {
        parent::__construct();
        $this->parentController = $parentController;
        $this->payment = $payment;
        $this->successURL = $successURL;
        $this->failURL = $failURL;

        $this->urlSegment = $request->latestParam('Action');
    }

    private static $allowed_actions = array(
        'submit',
        'handle_response',
    );

    public function Link($action = null)
    {
        return Controller::join_links($this->parentController->Link(), "/{$this->urlSegment}/$action");
    }

    public function absoluteURL($action = '')
    {
        return Director::absoluteURL($this->Link($action));
    }

    /**
     * Implement controller nesting
     */
    public function getNestedController()
    {
        return $this->parentController;
    }

    /*
     * Submits the data to Payment Express, then redirects the user based on the response
     *
     * @throws Exception
     */
    public function submit()
    {
        if (!$this->payment) {
            user_error("No payment data supplied");
        }
        $xml = "<GenerateRequest>";
        $xml .= "<PxPayUserId>" . $this->config()->get('PxPayUserId') . "</PxPayUserId>";
        $xml .= "<PxPayKey>" . $this->config()->get('PxPayKey') . "</PxPayKey>";
        $xml .= "<UrlFail>" . $this->absoluteURL("handle-response") . "</UrlFail>";
        $xml .= "<UrlSuccess>" . $this->absoluteURL("handle-response") . "</UrlSuccess>";
        foreach (array(
                     'TxnType',
                     'MerchantReference',
                     'TxnData1',
                     'TxnData2',
                     'TxnData3',
                     'EmailAddress',
                     'AmountInput',
                     'CurrencyInput'
                 ) as $key) {
            $xml .= "<$key>" . Convert::raw2xml($this->payment->$key) . "</$key>";
        }
        $xml .= "</GenerateRequest>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config()->get('PxPayURL'));

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //This is in the example code, but I don't like it

        $rawResponse = curl_exec($ch);
        $response = simplexml_load_string($rawResponse);

        if ($response) {
            if ($response->URI) {
                $this->redirect($response->URI);
            } else {
                $error = $response->responsetext ? $response->responsetext : $rawResponse;
                throw new Exception("Error message from DPS: `$error`. Request was: `$xml`");
            }
        } else {
            $error = ($rawResponse) ? $rawResponse : curl_error($ch);
            throw new Exception("Error communicating with DPS:`$error`. Request was: `$xml`");
        }

    }

    /*
     * Handles the response
     */
    public function handle_response(HTTPRequest $request)
    {
        $token = $request->getVar('result');

        if ($token) {
            $xml = "<ProcessResponse>";
            $xml .= "<PxPayUserId>" . $this->config()->get('PxPayUserId') . "</PxPayUserId>";
            $xml .= "<PxPayKey>" . $this->config()->get('PxPayKey') . "</PxPayKey>";
            $xml .= "<Response>$token</Response>";
            $xml .= "</ProcessResponse>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->config()->get('PxPayURL'));

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //This is in the example code, but I don't like it

            $rawResponse = curl_exec($ch);

            curl_close($ch);

            $response = simplexml_load_string($rawResponse);


            if ($response) {

                $reference = $response->MerchantReference;
                $this->payment = PxPayment::get()->filter(array('MerchantReference' => $reference))->first();
                if ($this->payment) {
                    if ($this->payment->TxnId) {
                        //Payment is already processed, do nothing
                        $this->redirect($this->successURL);
                    } else {
                        if ($response->Success == 1) {
                            try {
                                $this->payment->TxnId = "{$response->TxnId}";
                                $this->payment->write();
                            } catch (ValidationException $e) {
                                $this->redirect($this->failURL);
                            }
                            $this->redirect($this->successURL);
                        } else {

                            $this->redirect($this->failURL);
                        }
                    }

                } else {
                    $this->redirect($this->failURL);
                }
            } else {
                $this->redirect($this->failURL);
            }
        }
    }

}
