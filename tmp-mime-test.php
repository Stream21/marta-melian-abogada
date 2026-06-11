<?php
require '/app/vendor/autoload.php';
$d = new App\Infrastructure\Http\UploadedFileMimeDetector();
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
file_put_contents('/tmp/t.png', $png);
echo $d->detectFromContent($png, 'png');
