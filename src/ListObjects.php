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

if (count($args) !== 2 && count($args) !== 3) {
    echo "Usage: php ListObjects.php <bucketName> [maxKeys]" . PHP_EOL;
    exit(1);
}

$bucketName = $args[1];
$maxKeys = isset($args[2]) ? (int)$args[2] : 1000;  // 默认maxKeys为 1000

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
    echo "Your bucket contains the following objects:" . PHP_EOL;
    $totalCount = 0;
    $continuationToken = null;

    do {
        $params = [
            'Bucket' => $bucketName,
            'MaxKeys' => $maxKeys ,
            'ContinuationToken' => $continuationToken,
        ];

        $result = $s3Client->listObjectsV2($params);
        $isTruncated = $result['IsTruncated'];
        $continuationToken = $result['NextContinuationToken'] ?? null;

        foreach ($result['Contents'] as $object) {
            echo $object['Key'] . PHP_EOL;
            $totalCount++;
            if ($totalCount >= $maxKeys) {
                $isTruncated = false;
                break;
            }
        }
    } while ($isTruncated && $totalCount < $maxKeys);

} catch (S3Exception $e) {
    die("Error: " . $e->getMessage());
}

