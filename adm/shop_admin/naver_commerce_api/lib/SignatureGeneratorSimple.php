<?php

/**
 * 전자서명 암호화
 * - 호환성 팩(ircmaxell/password_compat)이나 password_hash는 버전 & salt 이슈가 있기 때문에 이 방법을 사용
 * 
 * @todo 보안상 안전한 방법인지 확인 필요
 */
class SignatureGeneratorSimple implements SignatureInterface {
    /**
     * 
     * @param String $clientId 
     * @param String $clientSecret      
     * @param String $timestamp      
     * @return String
     */
    public function generateSignature($clientId, $clientSecret, $timestamp)
    {
        if (strlen($clientSecret) < 22) {
            return '';
        }

        // 밑줄로 연결하여 password 생성
        $password = $clientId . "_" . $timestamp;
        $clientSecret = str_replace('+', '.', $clientSecret);

        // 암호화
        $cryptedSignature = crypt($password, $clientSecret);

        // base64 인코딩
        return base64_encode($cryptedSignature);
    }
}