<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$region = $_ENV['region'] ?? null;
$endpoint = $_ENV['endpoint'] ?? null;
$signatureVersion = $_ENV['signatureVersion'] ?? 'v4';
$forcePathStyle = $_ENV['forcePathStyle'] === 'true';
$accessKey = $_ENV['accessKeyId'] ?? null;
$secretKey = $_ENV['secretAccessKey'] ?? null;

$args = $argv;

if (count($args) < 4) {
    echo "Usage: php SliceUpload.php <bucketName> <keyName> <filePath>" . PHP_EOL;
    exit(1);
}

$bucketName = $args[1];
$keyName = $args[2];
$filePath = $args[3];

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
    $createMultipartUploadResult = $s3Client->createMultipartUpload([
        'Bucket' => $bucketName,
        'Key' => $keyName,
    ]);

    $uploadId = $createMultipartUploadResult['UploadId'];
    $partSize = 8 * 1024 * 1024; // 8MB
    $fileHandle = fopen($filePath, 'rb');

    if (!$fileHandle) {
        throw new Exception("无法打开文件: $filePath");
    }

    $partNumber = 1;
    $parts = [];

    while (!feof($fileHandle)) {
        $partContent = fread($fileHandle, $partSize);

        if ($partContent === false || strlen($partContent) === 0) {
            break;
        }


        // 上传分片
        $md5Hash = base64_encode(md5($partContent, true));
        $uploadPartResult = $s3Client->uploadPart([
            'Bucket' => $bucketName,
            'Key' => $keyName,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
            'Body' => $partContent,
            'ContentMD5' => $md5Hash,
        ]);

        echo "Uploaded part $partNumber successfully!" . PHP_EOL;

        // 保存分片信息
        $parts[] = [
            'PartNumber' => $partNumber,
            'ETag' => $uploadPartResult['ETag'],
        ];

        $partNumber++;

        // 释放内存
        unset($partContent);
        gc_collect_cycles();
    }

    fclose($fileHandle);

    // 完成上传
    $s3Client->completeMultipartUpload([
        'Bucket' => $bucketName,
        'Key' => $keyName,
        'UploadId' => $uploadId,
        'MultipartUpload' => ['Parts' => $parts],
    ]);

    echo "File uploaded successfully!" . PHP_EOL;
} catch (AwsException $e) {
    echo "Error uploading file: " . $e->getMessage() . PHP_EOL;

    if (isset($uploadId)) {
        $s3Client->abortMultipartUpload([
            'Bucket' => $bucketName,
            'Key' => $keyName,
            'UploadId' => $uploadId,
        ]);
        echo "Aborted multipart upload." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . PHP_EOL;
}
