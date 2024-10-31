<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

// 加载 .env 文件
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// 获取环境变量
$region = $_ENV['AWS_REGION'] ?? null;
$endpoint = $_ENV['AWS_ENDPOINT'] ?? null;
$signatureVersion = $_ENV['AWS_SIGNATURE_VERSION'] ?? 'v4';
$forcePathStyle = $_ENV['AWS_FORCE_PATH_STYLE'] === 'true';
$accessKey = $_ENV['AWS_ACCESS_KEY_ID'] ?? null;
$secretKey = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? null;

$args = $argv;

if (count($args) !== 3) {
    echo "Usage: php GetObjectAttr.php <bucketName> <keyName>" . PHP_EOL;
    exit(1);
}

$bucketName = $args[1];
$keyName = $args[2];

// 初始化客户端
try {
    $s3Client = new S3Client([
        'endpoint' => $endpoint,
        'region' => $region,
        'signature_version' => $signatureVersion,
        'force_path_style' => $forcePathStyle,
        'credentials' => [
            'key' => $accessKey,
            'secret' => $secretKey,
        ],
    ]);
    echo "Client initialized successfully!" . PHP_EOL;
} catch (InvalidArgumentException $e) {
    die("Failed to initialize Client: " . $e->getMessage());
}

try {
    // 获取对象元数据
    $result = $s3Client->headObject([
        'Bucket' => $bucketName,
        'Key' => $keyName,
    ]);

    // 输出元数据
    echo "Object attributes:" . PHP_EOL;
    echo " - Size: " . $result['ContentLength'] . " bytes" . PHP_EOL;
    echo " - Last Modified: " . $result['LastModified']->format(DateTime::RFC3339) . PHP_EOL;
    echo " - ETag: " . $result['ETag'] . PHP_EOL;
    echo " - Content Type: " . $result['ContentType'] . PHP_EOL;
    // 可以根据需要添加更多元数据
} catch (AwsException $e) {
    echo "Error getting object attributes: " . $e->getMessage() . PHP_EOL;
}

