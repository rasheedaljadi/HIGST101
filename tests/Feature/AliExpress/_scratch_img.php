<?php

$im = imagecreatetruecolor(1, 1);
imagefill($im, 0, 0, imagecolorallocate($im, 200, 100, 50));
ob_start();
imagepng($im);
$png = ob_get_clean();
echo base64_encode($png).PHP_EOL;
// sanity: re-read + webp encode
$f = tempnam(sys_get_temp_dir(), 't').'.png';
file_put_contents($f, $png);
$im2 = imagecreatefrompng($f);
ob_start();
imagewebp($im2);
$w = ob_get_clean();
fwrite(STDERR, 'png:'.strlen($png).' webp:'.strlen($w).PHP_EOL);
@unlink($f);
