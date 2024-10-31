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

if (count($args) < 4) {
    echo "Usage: php updateClass.php <bucketName> <keyName> <storageClass>" . PHP_EOL;
    exit(1);
}

$bucketName = $args[1];
$keyName = $args[2];
$storageClass = $args[3]; // 目标存储类别

// 初始化 S3 客户端
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
    // 执行更新存储类别操作
    $copySource = "{$bucketName}/{$keyName}";
    $result = $s3Client->copyObject([
        'Bucket'            => $bucketName,
        'CopySource'        => $copySource,
        'Key'               => $keyName,
        'StorageClass'      => $storageClass,
        'MetadataDirective' => 'COPY'
    ]);

    echo "Storage class updated successfully for {$keyName} in bucket {$bucketName}." . PHP_EOL;
} catch (AwsException $e) {
    echo "Error updating storage class: " . $e->getMessage() . PHP_EOL;
}
