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
    echo "Usage: php SliceUpload.php <bucketName> <keyName> <filePath>" . PHP_EOL;
    exit(1);
}

$bucketName = $args[1];
$keyName = $args[2];
$filePath = $args[3];  // 本地文件路径

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
    // 读取文件
    $fileContent = file_get_contents($filePath);
    $fileSize = strlen($fileContent);
    $partSize = 8 * 1024 * 1024; // 固定8MB
    $numParts = ceil($fileSize / $partSize);

    // 创建分片上传任务
    $createMultipartUploadResult = $s3Client->createMultipartUpload([
        'Bucket' => $bucketName,
        'Key' => $keyName,
    ]);

    $uploadId = $createMultipartUploadResult['UploadId'];
    $parts = [];

    // 分片上传
    for ($partNumber = 1; $partNumber <= $numParts; $partNumber++) {
        $start = ($partNumber - 1) * $partSize;
        $end = min($start + $partSize, $fileSize);
        $partContent = substr($fileContent, $start, $end - $start);
        $md5Hash = base64_encode(md5($partContent, true));

        // 上传分片
        $uploadPartResult = $s3Client->uploadPart([
            'Bucket' => $bucketName,
            'Key' => $keyName,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
            'Body' => $partContent,
            'ContentMD5' => $md5Hash,
        ]);

        $parts[] = [
            'PartNumber' => $partNumber,
            'ETag' => $uploadPartResult['ETag'],
        ];

        echo "Uploaded part $partNumber successfully!" . PHP_EOL;
    }

    // 完成分片上传
    $s3Client->completeMultipartUpload([
        'Bucket' => $bucketName,
        'Key' => $keyName,
        'UploadId' => $uploadId,
        'MultipartUpload' => [
            'Parts' => $parts,
        ],
    ]);

    echo "File uploaded successfully!" . PHP_EOL;
} catch (AwsException $e) {
    echo "Error uploading file: " . $e->getMessage() . PHP_EOL;

    // 如果上传失败，中止上传
    if (isset($uploadId)) {
        $s3Client->abortMultipartUpload([
            'Bucket' => $bucketName,
            'Key' => $keyName,
            'UploadId' => $uploadId,
        ]);
        echo "Aborted multipart upload." . PHP_EOL;
    }
}
