<?php
$ch = curl_init("http://127.0.0.1:8000/api/v1/models");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = json_decode($body, true);
echo "Code: " . $code . "\n";
$count = count($data["data"] ?? []);
echo "Models: " . $count . "\n";
