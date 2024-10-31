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
    echo "Usage: php GetObjectAcl.php <bucketName> <keyName>" . PHP_EOL;
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
    // 获取对象的ACL
    $result = $s3Client->getObjectAcl([
        'Bucket' => $bucketName,
        'Key' => $keyName,
    ]);

    // 输出ACL信息
    echo "Object ACL:" . PHP_EOL;
    foreach ($result['Grants'] as $grant) {
        $permission = $grant['Permission'];
        $grantee = isset($grant['Grantee']['DisplayName']) ? $grant['Grantee']['DisplayName'] : $grant['Grantee']['URI'];
        echo "Grantee: $grantee, Permission: $permission" . PHP_EOL;
    }
} catch (AwsException $e) {
    echo "Error getting object ACL: " . $e->getMessage() . PHP_EOL;
}

