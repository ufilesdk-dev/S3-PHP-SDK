<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

// 加载 .env 文件
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// 获取环境变量
$region = $_ENV['region'] ?? null;
$endpoint = $_ENV['endpoint'] ?? null;
$signatureVersion = $_ENV['signatureVersion'] ?? 'v4';
$forcePathStyle = $_ENV['forcePathStyle'] === 'true';
$accessKey = $_ENV['accessKeyId'] ?? null;
$secretKey = $_ENV['secretAccessKey'] ?? null;


$args = $argv;

if (count($args) < 4) {
    echo "Usage: php CopyObject.php <sourceBucketName> <sourceKeyName> <destinationBucketName> [destinationKeyName]" . PHP_EOL;
    exit(1);
}

$sourceBucketName = $args[1];  // 源 bucket 名称
$sourceKeyName = $args[2];     // 源文件的 key
$destinationBucketName = $args[3]; // 目标 bucket 名称
$destinationKeyName = $args[4] ?? $sourceKeyName; // 目标文件的 key, 默认为源文件的 key

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
    // 执行复制操作
    $result = $s3Client->copyObject([
        'Bucket'     => $destinationBucketName,
        'Key'        => $destinationKeyName,
        'CopySource' => "{$sourceBucketName}/{$sourceKeyName}",
    ]);

    echo "File copied successfully from {$sourceBucketName}/{$sourceKeyName} to {$destinationBucketName}/{$destinationKeyName}" . PHP_EOL;
} catch (AwsException $e) {
    echo "Error copying file: " . $e->getMessage() . PHP_EOL;
}
