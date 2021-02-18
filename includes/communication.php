<?php
class Communication
{
    public static function httpPostRequest( $requestUrl, $postFields='' )
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $output = curl_exec($ch);

        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (! $output) {
            if ($responseCode == 'CURLE_OPERATION_TIMEDOUT'){
                return 'Faild: Operation Timedout';
            }
            else {
                return 'Operation Faild';
            }
        } else {
            curl_close($ch);
            return json_decode( $output );
        }

    }

    public static function httpGetRequest( $requestUrl, $get = null )
    {
        $options = array(
            CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
            CURLOPT_URL            => $requestUrl . (strpos($requestUrl, "?") === false ? "?" : "") . http_build_query($get),
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSLVERSION     => 6,
            CURLINFO_HEADER_OUT    => true
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $output = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (! $output) {
            if ($responseCode == 'CURLE_OPERATION_TIMEDOUT'){
                return 'Faild: Operation Timedout';
            }
            else {
                return 'Operation Faild';
            }
        } else {
            curl_close($ch);

            return json_decode( $output, true );
        }

    }
}