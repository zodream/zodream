<?php
namespace Zodream\Domain\Image;
/**
 * 二维码
 * http://phpqrcode.sourceforge.net/
 * https://sourceforge.net/projects/phpqrcode/
 * @author Jason
 * @time 2015-12-1
 */
class QrCode extends Image {

    /**
     * 生成二维码
     * @param string $value
     * @param int $level 容错率
     * @param int $size 尺寸
     * @return $this
     */
	public function create($value, $level = 0 , $size = 6) {
		$this->image = \QRcode::png((string)$value, false, $level, $size, 2);
		return $this;
	}

    /**
     * 添加LOGO
     * @param string|Image|resource $logo
     * @return $this
     */
	public function addLogo($logo) {
		if (!$logo instanceof Image) {
			$logo = new Image($logo);
		}
		$width = ($this->width - $logo->width) / 2;
		$logoWidth = $this->width / 5;
		$this->copyFromWithReSampling($logo,
			$width,
			$width,
			0,
			0,
			$logo->width,
			$logo->height,
			$logoWidth,
			$this->height * $logo->width / $logoWidth
		);
		$logo->close();
		return $this;
	}
}