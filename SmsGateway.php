<?php
// =========================================================================
// FILE NAME: SmsGateway.php
// PURPOSE: Centralized SMS Gateway configuration for Murang'a Dairy System
// =========================================================================

/**
 * Formats local Kenyan phone numbers (e.g., 0712345678) into mandatory 2547XXXXXXXX
 */
function cleanKenyanPhone($phone) {
    // Strip out any non-numeric characters (spaces, dashes, etc.)
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // If it starts with local '0', swap it with '254'
    if (substr($phone, 0, 1) === '0') {
        $phone = '254' . substr($phone, 1);
    }
    // Handle cases where number might start with 7... directly
    if (strlen($phone) === 9 && substr($phone, 0, 1) !== '0') {
        $phone = '254' . $phone;
    }
    
    return $phone;
}

/**
 * Sends the formatted message payload to opensms.co.ke using your personal token
 */
function sendDairyAlert($phone, $message) {
    $url = "https://account.opensms.co.ke/api/v3/sms/send";
    
    // Your exact API token loaded from your dashboard
    // 368|XP15TR7U8BikrSDkFPFkPWabYid1YjJZ7IZN9jHhf3df7eb6 (0101965519)
    // 364|RZhtRx1OcagcMR9tU1GLgWzX4aXXeKMsaVDsVzbf1d94cf36 (0720601394)
    // 371|iqVQfExSnrc1PfoWqnA44ItHiYdbnhQ6Zg70z0XJ9051d58a (0790146776)
    $apiToken = "364|RZhtRx1OcagcMR9tU1GLgWzX4aXXeKMsaVDsVzbf1d94cf36"; 
    $senderId = "OPENSMS";

    $payload = array(
        "recipient" => $phone,
        "message"   => $message,
        "sender_id" => $senderId,
        "type"      => "plain"
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 3600); // Cache DNS for 1 hour
    curl_setopt($ch, CURLOPT_TCP_NODELAY, 1); // Instant packet delivery
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0); // Use multiplexing if supported
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . $apiToken,
        "Content-Type: application/json",
        "Accept: application/json",
        "Expect:", // Disable the "100-continue" delay
        "Connection: keep-alive"
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return json_encode(['status' => 'error', 'message' => $error_msg]);
    }
    
    if ($httpCode >= 400) {
        return json_encode(['status' => 'error', 'message' => "HTTP $httpCode: " . $response]);
    }

    curl_close($ch);
    
    return $response;
}
?>