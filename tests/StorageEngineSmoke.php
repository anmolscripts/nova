<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
app($app);

$assert = function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$disk = storage('memory');
$assert($disk->put('docs/readme.txt', 'Nova'), 'put failed.');
$assert($disk->get('docs/readme.txt') === 'Nova', 'get failed.');
$assert($disk->exists('docs/readme.txt'), 'exists failed.');
$assert($disk->copy('docs/readme.txt', 'docs/copy.txt'), 'copy failed.');
$assert($disk->get('docs/copy.txt') === 'Nova', 'copy contents failed.');
$assert($disk->move('docs/copy.txt', 'docs/moved.txt'), 'move failed.');
$assert(!$disk->exists('docs/copy.txt') && $disk->exists('docs/moved.txt'), 'move existence failed.');
$assert($disk->size('docs/moved.txt') === 4, 'size failed.');
$assert(in_array('docs/readme.txt', $disk->files('docs'), true), 'files failed.');
$assert($disk->delete('docs/moved.txt'), 'delete failed.');

$public = storage('public');
$public->put('avatars/user.txt', 'Ada');
$assert(str_ends_with($public->url('avatars/user.txt'), '/storage/avatars/user.txt'), 'public url failed.');

$tmp = tempnam(sys_get_temp_dir(), 'nova-upload-');
file_put_contents($tmp, 'upload');
$upload = new \Nova\Storage\UploadFile('report.txt', $tmp, 'text/plain', UPLOAD_ERR_OK, 6);
$stored = $upload->storeAs('documents', 'report.txt');
$assert($stored->path() === 'documents/report.txt', 'upload path failed.');
$assert(storage('local')->get('documents/report.txt') === 'upload', 'upload store failed.');

$tmp2 = tempnam(sys_get_temp_dir(), 'nova-upload-');
file_put_contents($tmp2, 'temp');
$temporary = new \Nova\Storage\UploadFile('temp.txt', $tmp2, 'text/plain', UPLOAD_ERR_OK, 4);
$temporary->temporary();
$assert($temporary->path() !== null && storage('temporary')->exists($temporary->path()), 'temporary upload failed.');

$download = storage('local')->download('documents/report.txt', 'report.txt');
$assert($download instanceof \Nova\Http\DownloadResponse, 'download response failed.');

$gif = base64_decode('R0lGODlhAQABAPAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==');
$imagePath = $app->storagePath('temporary/pixel.gif');
file_put_contents($imagePath, $gif);
$image = image($imagePath);
$assert($image->width() === 1 && $image->height() === 1, 'image dimensions failed.');

$public->delete('avatars/user.txt');
storage('local')->delete('documents/report.txt');
@unlink($imagePath);

echo "Storage engine smoke test passed." . PHP_EOL;
