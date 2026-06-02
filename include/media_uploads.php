<?php
require_once __DIR__ . '/config.php';

function uploadRootPath(): string
{
    return dirname(__DIR__) . '/assets/images/uploads';
}

function uploadPublicPath(string $filename, string $bucket = 'vehicles'): string
{
    $bucket = preg_replace('/[^a-z0-9_-]/i', '', $bucket) ?: 'vehicles';
    return 'assets/images/uploads/' . $bucket . '/' . date('Y/m') . '/' . $filename;
}

function validateUploadedImage(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['valid' => false, 'error' => 'No file was selected.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'The upload did not complete.'];
    }

    $maxBytes = (int)($_ENV['UPLOAD_MAX_BYTES'] ?? 5242880);
    if ((int)($file['size'] ?? 0) > $maxBytes) {
        return ['valid' => false, 'error' => 'The image is larger than the allowed size.'];
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_file($tmp)) {
        return ['valid' => false, 'error' => 'The uploaded file could not be read.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        return ['valid' => false, 'error' => 'Only JPG, PNG, and WEBP images are allowed.'];
    }

    return [
        'valid' => true,
        'mime' => $mime,
        'extension' => $allowed[$mime],
        'size' => (int)($file['size'] ?? 0),
        'original_name' => basename((string)($file['name'] ?? 'upload')),
    ];
}

function storeUploadedImage(PDO $db, array $file, string $bucket, ?string $uploadedBy = null): array
{
    $validation = validateUploadedImage($file);
    if (!$validation['valid']) {
        return $validation;
    }

    $bucket = preg_replace('/[^a-z0-9_-]/i', '', $bucket) ?: 'vehicles';
    $relativeDir = $bucket . '/' . date('Y/m');
    $targetDir = uploadRootPath() . '/' . $relativeDir;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        return ['valid' => false, 'error' => 'The upload folder could not be created.'];
    }

    $safeBase = strtolower(pathinfo($validation['original_name'], PATHINFO_FILENAME));
    $safeBase = preg_replace('/[^a-z0-9]+/', '-', $safeBase);
    $safeBase = trim((string)$safeBase, '-') ?: 'vehicle';
    $filename = $safeBase . '-' . bin2hex(random_bytes(6)) . '.' . $validation['extension'];
    $targetPath = $targetDir . '/' . $filename;

    $moved = is_uploaded_file((string)$file['tmp_name'])
        ? move_uploaded_file((string)$file['tmp_name'], $targetPath)
        : copy((string)$file['tmp_name'], $targetPath);

    if (!$moved) {
        return ['valid' => false, 'error' => 'The image could not be saved.'];
    }

    $publicPath = uploadPublicPath($filename, $bucket);
    $stmt = $db->prepare('
        INSERT INTO uploaded_media (file_path, original_name, mime_type, file_size, uploaded_by)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $publicPath,
        $validation['original_name'],
        $validation['mime'],
        $validation['size'],
        $uploadedBy,
    ]);

    return [
        'valid' => true,
        'path' => $publicPath,
        'id' => (int)$db->lastInsertId(),
    ];
}
