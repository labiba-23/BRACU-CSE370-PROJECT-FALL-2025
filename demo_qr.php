<?php
declare(strict_types=1);


header("Content-Type: image/png");

$size = 240;
$cell = 12;
$grid = $size / $cell;

$img = imagecreatetruecolor($size, $size);

$white = imagecolorallocate($img, 255, 255, 255);
$black = imagecolorallocate($img, 0, 0, 0);

imagefill($img, 0, 0, $white);


for ($y = 0; $y < $grid; $y++) {
    for ($x = 0; $x < $grid; $x++) {
        if (random_int(0, 100) < 45) {
            imagefilledrectangle(
                $img,
                $x * $cell,
                $y * $cell,
                ($x + 1) * $cell,
                ($y + 1) * $cell,
                $black
            );
        }
    }
}


function finder($img, $x, $y, $size, $black, $white) {
    imagefilledrectangle($img, $x, $y, $x+$size, $y+$size, $black);
    imagefilledrectangle($img, $x+8, $y+8, $x+$size-8, $y+$size-8, $white);
    imagefilledrectangle($img, $x+16, $y+16, $x+$size-16, $y+$size-16, $black);
}

finder($img, 0, 0, 72, $black, $white);
finder($img, $size-72, 0, 72, $black, $white);
finder($img, 0, $size-72, 72, $black, $white);


imagepng($img);
imagedestroy($img);

