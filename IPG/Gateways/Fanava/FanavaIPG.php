<?php
/*
*                        _oo0oo_
*                       o8888888o
*                       88" . "88
*                       (| -_- |)
*                       0\  =  /0
*                     ___/`---'\___
*                   .' \\|     |// '.
*                  / \\|||  :  |||// \
*                 / _||||| -:- |||||- \
*                |   | \\\  -  /// |   |
*                | \_|  ''\---/''  |_/ |
*                \  .-\__  '-'  ___/-. /
*              ___'. .'  /--.--\  `. .'___
*           ."" '<  `.___\_<|>_/___.' >' "".
*          | | :  `- \`.;`\ _ /`;.`/ - ` : | |
*          \  \ `_.   \_ __\ /__ _/   .-` /  /
*      =====`-.____`.___ \_____/___.-`___.-'=====
*                        `=---='
* 
* 
*      ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
* 
*                Buddha Bless This Code
*                    To be Bug Free
* 
*  Created by nevercom at 2/6/17 5:37 PM
*/

namespace IPG\Gateways\Fanava;


use IPG\Contract\AbstractIPG;
use IPG\Models\PaymentResponse;
use IPG\Models\ValidationResponse;
use IPG\Models\VerificationResponse;

class FanavaIPG extends AbstractIPG {
    private   $service;
    protected $username;
    protected $password;
    private   $sessionId;

    /**
     * AbstractIPG constructor.
     *
     * @param array $config
     */
    public function __construct($config = array()) {
        if (isset($config['amount'])) {
            $this->amount = $config['amount'];
        }
        $this->service = new Fanava('https://fanava.shaparak.ir/ref-payment/jax/merchantAuth?wsdl');


    }

    /**
     * @param int    $paymentId   Payment ID, this the id that is used to identify the transaction
     * @param int    $amount      Amount of payment (Rial)
     * @param string $callbackUrl Callback URL which the data should be sent to by IPG upon payment
     *
     * @return PaymentResponse
     */
    public function startPayment($paymentId, $amount, $callbackUrl) {
        $response = new PaymentResponse();
        $response->setIsSuccessful(TRUE);
        $response->setTransactionId($paymentId);
        $response->setTargetUrl('https://fanava.shaparak.ir/_ipgw_/payment/simple/');
        $response->setData(
            Array(
                'amount'      => $this->amount,
                'resNum'      => $paymentId,
                'MID'         => $this->username,
                'redirectURL' => $callbackUrl
            )
        );

        return $response;
    }

    /**
     * @param array $request $_REQUEST is passed to this method for validation check
     *
     * @return ValidationResponse
     */
    public function isPaymentValid($request) {
        $isValid = isset($request['ResNum']) &&
                   isset($request['RefNum']) &&
                   strtolower($request['State']) == 'ok' &&
                   $request['MID'] == $this->username;

        $res = new ValidationResponse();
        $res->setValid($isValid);
        $res->setReferenceId($request['RefNum']);

        return $res;
    }

    /**
     * @param int    $paymentId
     * @param string $referenceId
     *
     * @return VerificationResponse
     */
    public function verify($paymentId, $referenceId) {
        $v    = new verifyTransaction();
        $vReq = new verifyRequest();

        $v->context       = $this->getContext();
        $vReq->refNumList = $referenceId;
        $v->verifyRequest = $vReq;

        $result = $this->service->verifyTransaction($v);
        $isSuccessfull = !isset($result->return->verifyResponseResults->verificationError)
                         || empty($result->return->verifyResponseResults->verificationError);

        $res = new VerificationResponse();
        $res->setSuccessful($isSuccessfull);
        return $res;


    }


    public function inquiry($paymentId, $referenceId) {
        // TODO: Implement inquiry() method.
    }

    public function settle($paymentId, $referenceId) {
        // TODO: Implement settle() method.
    }

    public function reversal($paymentId, $referenceId) {
        // TODO: Implement reversal() method.
    }

    /**
     * @return int
     */
    public function getErrorCode() {
        // TODO: Implement getErrorCode() method.
    }

    /**
     * @return string
     */
    public function getErrorMessage() {
        // TODO: Implement getErrorMessage() method.
    }

    /**
     * @return string
     */
    public function getCanonicalName() {
        return get_class();
    }


    /**
     * @return wsContext
     */
    private function getContext() {
        if (empty($this->sessionId)) {
            $this->login();
        }
        $c            = new wsContext();
        $data         = new data();
        $entry        = new entry();
        $entry->key   = 'SESSION_ID';
        $entry->value = $this->sessionId;
        $data->entry  = $entry;
        $c->data      = $data;

        return $c;
    }

    private function login() {
        $loginParam   = new login();
        $loginRequest = new loginRequest();

        $loginRequest->username   = $this->username;
        $loginRequest->password   = $this->password;
        $loginParam->loginRequest = $loginRequest;

        $result = $this->service->login($loginParam);

        $this->sessionId = $result->return;
    }
}