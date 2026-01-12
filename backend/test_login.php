<?php
$data = json_encode([
    "email" => "admin@dgtcp.ci",
    "password" => "password"
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Accept: application/json",
    "Content-Length: " . strlen($data)
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . PHP_EOL;
echo "Response: " . $response . PHP_EOL;
?>
