<?php
// Create the profiles directory if it doesn't exist
$upload_dir = 'assets/images/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Create a default profile image using GD
$image = imagecreatetruecolor(150, 150);
$bg_color = imagecolorallocate($image, 200, 200, 200);
$text_color = imagecolorallocate($image, 100, 100, 100);

// Fill background
imagefilledrectangle($image, 0, 0, 150, 150, $bg_color);

// Add text
$text = "User";
$font_size = 5;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = (150 - $text_width) / 2;
$y = (150 - $text_height) / 2;
imagestring($image, $font_size, $x, $y, $text, $text_color);

// Save the image
imagepng($image, $upload_dir . 'default-profile.png');
imagedestroy($image);

echo "Default profile image created successfully!";
?> 