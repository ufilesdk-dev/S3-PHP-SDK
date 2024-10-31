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

if (count($args) < 3) {
    echo "Usage: php RestoreObject.php <bucketName> <keyName>" . PHP_EOL;
    exit(1);
}

$bucketName = $args[1];  // bucket 名称
$keyName = $args[2];     // 文件的 key

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

// 发送对象恢复请求
function restoreS3Object($s3Client, $bucketName, $keyName)
{
    try {
        $params = [
            'Bucket' => $bucketName,
            'Key' => $keyName,
            'RestoreRequest' => [
                'Days' => 3, // 解冻天数
            ],
        ];

        $s3Client->restoreObject($params);
        echo "Restore request sent successfully." . PHP_EOL;

        checkRestorationStatus($s3Client, $bucketName, $keyName);
    } catch (AwsException $e) {
        echo "Error restoring object: " . $e->getMessage() . PHP_EOL;
    }
}

// 检查恢复状态
function checkRestorationStatus($s3Client, $bucketName, $keyName)
{
    try {
        $headParams = [
            'Bucket' => $bucketName,
            'Key' => $keyName,
        ];

        $result = $s3Client->headObject($headParams);

        $restoreStatus = isset($result['Restore']) && strpos($result['Restore'], 'ongoing-request="true"') !== false
            ? "in-progress"
            : "finished or failed";

        echo "Restoration status: {$restoreStatus}" . PHP_EOL;
    } catch (AwsException $e) {
        echo "Error checking restoration status: " . $e->getMessage() . PHP_EOL;
    }
}

// 执行对象解冻
restoreS3Object($s3Client, $bucketName, $keyName);
