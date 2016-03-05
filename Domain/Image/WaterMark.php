<?php
namespace Zodream\Domain\Image;

/**
 * 加水印
 * @author zx648
 *
 */
class WaterMark extends Image{
	
	/**
	 * 加文字
	 * @param unknown $text
	 * @param number $x
	 * @param number $y
	 * @param number $fontSize
	 * @param string $color
	 * @param number $fontFamily
	 * @param number $angle 如果 $fontFamily 为 int，则不起作用
	 */
	public function addText($text, $x = 0, $y = 0, $fontSize = 16, $color = '#000', $fontFamily = 5, $angle = 0) {
		$color = $this->getColorWithRGB($color);
		if (is_string($fontFamily) && is_file($fontFamily)) {
			imagettftext($this->image, $fontSize, $angle, $x, $y, $color, $fontFamily, $text);
		} else {
			imagestring($this->img, $fontFamily, $x, $y, $text, $color);
		}
	}
	
	/**
	 * 加水印图片
	 * @param unknown $imageFile
	 * @param number $x
	 * @param number $y
	 * @param number $opacity 透明度，对png图片不起作用
	 */
	public function addImage($imageFile, $x = 0, $y = 0, $opacity = 50) {
		$image = new Image($imageFile);
		if ($image->getRealType() == 'png') {
			$this->copyFrom($image, 0, 0, $x, $y);
		} else {
			$this->copyAndMergeFrom($image, $x, $y, $opacity);
		}
	}
}