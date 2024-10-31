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
    echo "Usage: php Upload.php <bucketName> <keyName> <filePath> [storageClass]" . PHP_EOL;
    exit(1);
}

$bucketName = $args[1];
$keyName = $args[2];
$filePath = $args[3];  // 本地文件路径
$storageClass = $args[4] ?? null;  // 可选的存储类

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
    $params = [
        'Bucket' => $bucketName,
        'Key' => $keyName,
        'SourceFile' => $filePath,
    ];

    if ($storageClass) {
        $params['StorageClass'] = $storageClass;
    }

    // 上传文件
    $result = $s3Client->putObject($params);
    echo "File uploaded successfully: " . $result['ObjectURL'] . PHP_EOL;
} catch (AwsException $e) {
    echo "Error uploading file: " . $e->getMessage() . PHP_EOL;
}
