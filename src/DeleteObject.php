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

if (count($args) < 3) {
    echo "Usage: php DeleteObject.php <bucketName> <objectKey1> <objectKey2> ..." . PHP_EOL;
    exit(1);
}

$bucketName = $args[1];
$objectKeys = array_slice($args, 2); // 待删除的键

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

// 执行批量删除操作
function deleteS3Objects($s3Client, $bucketName, $objectKeys)
{
    try {
        $deleteParams = [
            'Bucket' => $bucketName,
            'Delete' => [
                'Objects' => array_map(function ($key) {
                    return ['Key' => $key];
                }, $objectKeys),
            ],
        ];

        $result = $s3Client->deleteObjects($deleteParams);

        echo "Successfully deleted " . count($result['Deleted']) . " objects from S3 bucket {$bucketName}. Deleted objects:" . PHP_EOL;
        foreach ($result['Deleted'] as $deletedObject) {
            echo " • " . $deletedObject['Key'] . PHP_EOL;
        }
    } catch (AwsException $e) {
        echo "Error deleting objects: " . $e->getMessage() . PHP_EOL;
    }
}

// 执行删除
deleteS3Objects($s3Client, $bucketName, $objectKeys);
