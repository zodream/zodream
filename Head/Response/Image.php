<?php
namespace App\Head\Response;

class Image {
	public static function view($img) {
		header('Content-type:image/png');
		imagepng($img);
		imagedestroy($img);
	}
}