<?php
$db = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
$token = $db->query('select api_token from printify_connections limit 1')->fetchColumn();
if (!$token) {
    echo "NO_CONN\n";
    exit(1);
}

$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer {$token}\r\nAccept: application/json\r\n",
        'timeout' => 20,
    ],
];

$ctx = stream_context_create($opts);
$raw = @file_get_contents('https://api.printify.com/v1/catalog/blueprints.json', false, $ctx);
if ($raw === false) {
    echo "REQ_FAIL\n";
    exit(1);
}

$json = json_decode($raw, true);
$items = $json['data'] ?? [];
foreach ($items as $bp) {
    $title = $bp['title'] ?? '';
    $t = strtolower($title);
    if (str_contains($t, 'zip') || str_contains($t, 'hoodie') || str_contains($t, '18600')) {
        echo ($bp['id'] ?? '') . ' :: ' . $title . PHP_EOL;
    }
}
