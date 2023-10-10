<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

$screenshotImage = getReportScreenshotImage($reportId);
if ($screenshotImage === NULL) {
    show404();
    exit;
} else {
    header('Content-Type: image/jpeg');
    header('Cache-Control: max-age=31536000');
    echo $screenshotImage;
    exit;
}
