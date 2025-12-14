<?php

namespace Objects;

class FileHandler
{
    private $allowedTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mp3' => 'audio/mpeg'
    ];
    private $allowedMimes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'video/mp4',
        'video/webm',
        'audio/mpeg'
    ];

    private $targetDir;
    private $maxSize;
    private $file;
    private $ext;
    private $error_list;
    private $fileOrg;

    private $Suffix;

    public function __construct(
        $file,
        $Suffix = 'media_',
        $path = "/uploads/"
    )
    {
        $this->error_list = [];
        $this->file = $file['tmp_name'];
        $this->fileOrg = $file;

        $this->ext = strtolower(pathinfo($this->fileOrg['name'], PATHINFO_EXTENSION));
        $this->targetDir = $_SERVER['DOCUMENT_ROOT'] . $path;
        $this->maxSize = 10 * 1024 * 1024;
        $this->Suffix = $Suffix;
    }

    public function upload(): array
    {
        $file = $this->fileOrg;
        if ($this->check_file_type()) {
            $this->error_list["fileType"] = "File type is not allowed " . $file['name'];
        }
        if ($this->check_file_size()) {
            $this->error_list["fileSize"] = "File size is not allowed " . $file['name'] . "=" . $file['size'] / 1024 / 1024 . "MB while allowed is: " . $this->maxSize / 1024 / 1024 . "MB";
        }
        if ($this->check_file_mime()) {
            $this->error_list["fileMiMe"] = "File mime is not allowed " . $file['name'];
        }
        if ($this->error_list) {
            $report = [
                'ok' => 0,
                'error' => $this->error_list,
                'filename' => ''
            ];
            return $report;
        }
        $report = array(
            'ok' => 1,
            'error' => [],
            'filename' => $this->move()
        );
        return $report;
    }

    private function check_file_type(): bool
    {
        // Return true when file type is NOT allowed (to match other check_* methods behavior)
        return !array_key_exists($this->ext, $this->allowedTypes);
    }

    private function check_file_size(): bool
    {
        $fileSize = filesize($this->file);
        return $fileSize > $this->maxSize;
    }

    private function check_file_mime(): bool
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $this->file);
        finfo_close($finfo);
        return !in_array($mime_type, $this->allowedMimes);
    }

    private function move()
    {
        // Determine prefix based on file type when default 'media_' is used
        $prefix = $this->Suffix;
        if ($prefix === 'media_') {
            if ($this->isImageExtension($this->ext)) {
                $prefix = 'pic_';
            } elseif ($this->isVideoExtension($this->ext)) {
                $prefix = 'vid_';
            } elseif ($this->isAudioExtension($this->ext)) {
                $prefix = 'aud_';
            }
        }

        $target_filename = uniqid($prefix) . '.' . $this->ext;
        $targetFilePath = $this->targetDir . $target_filename;
        if (move_uploaded_file($this->file, $targetFilePath)) {
            // Generate thumbnail based on media type
            if ($this->isImageExtension($this->ext)) {
                // For images: keep same extension for thumb
                $this->createThumbnail($targetFilePath, $this->targetDir . 'thumb_' . $target_filename);
            } elseif ($this->isVideoExtension($this->ext)) {
                // For videos: create a placeholder thumbnail with a play icon
                $base = pathinfo($target_filename, PATHINFO_FILENAME);
                $thumbPath = $this->targetDir . 'thumb_' . $base . '.jpg';
                $this->createVideoPlaceholderThumbnail($thumbPath);
            } elseif ($this->isAudioExtension($this->ext)) {
                // For audio: try to extract embedded cover art; if not available, create placeholder
                $base = pathinfo($target_filename, PATHINFO_FILENAME);
                $thumbPath = $this->targetDir . 'thumb_' . $base . '.jpg';
                $coverData = $this->extractMp3CoverArt($targetFilePath);
                if ($coverData) {
                    $this->createThumbnailFromBinary($coverData, $thumbPath);
                } else {
                    $this->createAudioPlaceholderThumbnail($thumbPath);
                }
            }
            return $target_filename;
        } else {
            return false;
        }
    }

    private function isImageExtension(string $ext): bool
    {
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    private function isVideoExtension(string $ext): bool
    {
        return in_array($ext, ['mp4', 'webm']);
    }

    private function isAudioExtension(string $ext): bool
    {
        return in_array($ext, ['mp3']);
    }

    private function createThumbnail(string $sourcePath, string $thumbPath, int $maxWidth = 300, int $maxHeight = 300): void
    {
        if (!file_exists($sourcePath)) {
            return;
        }
        $info = getimagesize($sourcePath);
        if ($info === false) {
            return;
        }
        [$width, $height] = $info;
        $mime = $info['mime'] ?? '';

        // Calculate new dimensions preserving aspect ratio
        $scale = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = (int)floor($width * $scale);
        $newHeight = (int)floor($height * $scale);

        // Create image resource from source based on mime
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $src = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $src = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $src = @imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($sourcePath);
                } else {
                    return;
                }
                break;
            default:
                return; // Not an image we handle
        }
        if (!$src) {
            return;
        }

        $dst = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if (in_array($mime, ['image/png', 'image/gif'])) {
            imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save thumbnail based on mime
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                @imagejpeg($dst, $thumbPath, 85);
                break;
            case 'image/png':
                // Compression: 0 (no compression) to 9
                @imagepng($dst, $thumbPath, 6);
                break;
            case 'image/gif':
                @imagegif($dst, $thumbPath);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    @imagewebp($dst, $thumbPath, 80);
                }
                break;
        }

        imagedestroy($src);
        imagedestroy($dst);
    }

    private function createVideoPlaceholderThumbnail(string $thumbPath, int $size = 300): void
    {
        // Create square canvas
        $img = imagecreatetruecolor($size, $size);
        if (!$img) return;
        $bg = imagecolorallocate($img, 30, 30, 30); // dark background
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);

        // Draw a subtle gradient-ish overlay using lighter rects (optional minimal)
        $overlay = imagecolorallocatealpha($img, 255, 255, 255, 100);
        imagefilledrectangle($img, 0, (int)($size*0.65), $size, $size, $overlay);

        // Draw play triangle in the center
        $triangleSize = (int)($size * 0.4);
        $centerX = (int)($size / 2);
        $centerY = (int)($size / 2);
        $halfH = (int)($triangleSize / 2);
        $points = [
            $centerX - (int)($triangleSize / 3), $centerY - $halfH,
            $centerX - (int)($triangleSize / 3), $centerY + $halfH,
            $centerX + (int)($triangleSize / 2), $centerY
        ];
        $playColor = imagecolorallocate($img, 255, 255, 255);
        imagefilledpolygon($img, $points, 3, $playColor);

        // Save as JPEG
        @imagejpeg($img, $thumbPath, 85);
        imagedestroy($img);
    }

    private function createAudioPlaceholderThumbnail(string $thumbPath, int $size = 300): void
    {
        $img = imagecreatetruecolor($size, $size);
        if (!$img) return;
        $bg = imagecolorallocate($img, 20, 50, 90); // blue-ish background
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);

        // Draw a simple music note
        $noteColor = imagecolorallocate($img, 255, 255, 255);
        $stemX = (int)($size * 0.55);
        $stemTopY = (int)($size * 0.25);
        $stemBottomY = (int)($size * 0.65);
        imagesetthickness($img, (int)($size * 0.04));
        imageline($img, $stemX, $stemTopY, $stemX, $stemBottomY, $noteColor);
        // Flag
        imageline($img, $stemX, $stemTopY, (int)($stemX + $size*0.18), (int)($stemTopY + $size*0.10), $noteColor);
        // Note head (circle)
        $headR = (int)($size * 0.10);
        imagefilledellipse($img, (int)($stemX - $headR), $stemBottomY, (int)($headR*2), (int)($headR*2), $noteColor);

        @imagejpeg($img, $thumbPath, 85);
        imagedestroy($img);
    }

    private function createThumbnailFromBinary(string $binaryData, string $thumbPath, int $maxWidth = 300, int $maxHeight = 300): void
    {
        $src = @imagecreatefromstring($binaryData);
        if (!$src) {
            // Fallback to placeholder
            $this->createAudioPlaceholderThumbnail($thumbPath, min($maxWidth, $maxHeight));
            return;
        }
        $width = imagesx($src);
        $height = imagesy($src);
        $scale = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = (int)floor($width * $scale);
        $newHeight = (int)floor($height * $scale);
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($dst, true);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        @imagejpeg($dst, $thumbPath, 85);
        imagedestroy($src);
        imagedestroy($dst);
    }

    private function extractMp3CoverArt(string $filePath): ?string
    {
        // Read ID3v2 header
        $fp = @fopen($filePath, 'rb');
        if (!$fp) return null;
        $header = fread($fp, 10);
        if (strlen($header) < 10 || substr($header, 0, 3) !== 'ID3') {
            fclose($fp);
            return null;
        }
        $versionMajor = ord($header[3]); // 2,3,4
        $flags = ord($header[5]);
        // Synchsafe 28-bit size
        $size = (ord($header[6]) & 0x7F) << 21 | (ord($header[7]) & 0x7F) << 14 | (ord($header[8]) & 0x7F) << 7 | (ord($header[9]) & 0x7F);
        $tagData = fread($fp, $size);
        fclose($fp);
        if (strlen($tagData) < 10) return null;

        $pos = 0;
        if ($versionMajor === 2) {
            // ID3v2.2 frames: 3-byte id + 3-byte size
            while ($pos + 6 <= strlen($tagData)) {
                $id = substr($tagData, $pos, 3);
                $pos += 3;
                $frameSize = (ord($tagData[$pos]) << 16) | (ord($tagData[$pos+1]) << 8) | ord($tagData[$pos+2]);
                $pos += 3;
                if ($frameSize <= 0 || $pos + $frameSize > strlen($tagData)) break;
                $frameData = substr($tagData, $pos, $frameSize);
                if ($id === 'PIC') {
                    $img = $this->parseApicFrame($frameData, 2);
                    if ($img) return $img;
                }
                $pos += $frameSize;
            }
        } else {
            // ID3v2.3/2.4: 4-byte id + 4-byte size + 2 flags
            while ($pos + 10 <= strlen($tagData)) {
                $id = substr($tagData, $pos, 4);
                $pos += 4;
                $sizeBytes = substr($tagData, $pos, 4);
                $pos += 4;
                $flags2 = substr($tagData, $pos, 2);
                $pos += 2;
                if (trim($id, "\0 ") === '') break;
                if ($versionMajor === 4) {
                    $frameSize = (ord($sizeBytes[0]) & 0x7F) << 21 | (ord($sizeBytes[1]) & 0x7F) << 14 | (ord($sizeBytes[2]) & 0x7F) << 7 | (ord($sizeBytes[3]) & 0x7F);
                } else {
                    $frameSize = (ord($sizeBytes[0]) << 24) | (ord($sizeBytes[1]) << 16) | (ord($sizeBytes[2]) << 8) | ord($sizeBytes[3]);
                }
                if ($frameSize <= 0 || $pos + $frameSize > strlen($tagData)) break;
                $frameData = substr($tagData, $pos, $frameSize);
                if ($id === 'APIC') {
                    $img = $this->parseApicFrame($frameData, $versionMajor);
                    if ($img) return $img;
                }
                $pos += $frameSize;
            }
        }
        return null;
    }

    private function parseApicFrame(string $frameData, int $versionMajor): ?string
    {
        $p = 0;
        if ($p >= strlen($frameData)) return null;
        $textEncoding = ord($frameData[$p]);
        $p++;
        if ($versionMajor === 2) {
            // 3 bytes image format like 'JPG'
            if ($p + 3 > strlen($frameData)) return null;
            $mime = strtolower(substr($frameData, $p, 3));
            $p += 3;
        } else {
            // Mime string terminated by 0x00
            $zeroPos = strpos($frameData, "\x00", $p);
            if ($zeroPos === false) return null;
            $mime = strtolower(substr($frameData, $p, $zeroPos - $p));
            $p = $zeroPos + 1;
        }
        // Picture type
        if ($p >= strlen($frameData)) return null;
        $picType = ord($frameData[$p]);
        $p++;
        // Description (terminated according to encoding)
        if ($textEncoding === 0 || $textEncoding === 3) {
            // ISO-8859-1 or UTF-8: terminated by 0x00
            $zeroPos = strpos($frameData, "\x00", $p);
            if ($zeroPos === false) return null;
            $p = $zeroPos + 1;
        } else {
            // UTF-16 with BOM: terminated by 0x00 0x00
            $zeroPos = strpos($frameData, "\x00\x00", $p);
            if ($zeroPos === false) return null;
            $p = $zeroPos + 2;
        }
        // Remaining is image data
        $imageData = substr($frameData, $p);
        if ($imageData === '') return null;
        return $imageData;
    }
}