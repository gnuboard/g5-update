<?php

/**
 * password_* 기능의 호환성 라이브러리
 * 
 * @link https://github.com/ircmaxell/password_compat/blob/master/lib/password.php
 * @todo 적용 & 테스트
 */
class SignatureGeneratorIrcmaxell implements SignatureInterface {

    public function generateSignature($clientId, $clientSecret, $timestamp)
    {
        echo $clientId . "_" . $clientSecret . "_" . $timestamp;
    }
}