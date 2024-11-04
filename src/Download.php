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
    echo "Usage: php Download.php <bucketName> <keyName> <downloadPath>" . PHP_EOL;
    exit(1);
}

$bucketName = $args[1];
$keyName = $args[2];
$downloadPath = $args[3];

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
    echo "Client 初始化成功！" . PHP_EOL;
} catch (InvalidArgumentException $e) {
    die("Client 初始化失败: " . $e->getMessage());
}

try {
    $result = $s3Client->getObject([
        'Bucket' => $bucketName,
        'Key' => $keyName,
        'SaveAs' => $downloadPath,
    ]);

    echo "文件下载成功到 " . $downloadPath  . PHP_EOL;

} catch (AwsException $e) {
    echo "文件下载失败: " . $e->getMessage() . PHP_EOL;
}