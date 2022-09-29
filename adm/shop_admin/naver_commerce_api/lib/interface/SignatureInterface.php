<?php

interface SignatureInterface {

    public function generateSignature($clientId, $clientSecret, $timestamp);

}
