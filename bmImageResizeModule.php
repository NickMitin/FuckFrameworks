<?php

class bmImageResizeModule
{
	private $image;
	private $width;
	private $height;
	private $type;

	/**
	 * Инициализация объекта
	 *
	 * @param $file string    Путь к временному файлу
	 */
	public function __construct($file)
	{
		if (@!file_exists($file))
		{
			exit("File does not exist");
		}
		if (!$this->setType($file))
		{
			exit("File is not an image");
		}
		$this->openImage($file);
		$this->setSize();
	}

	/**
	 * @param bool $width
	 * @param bool $height
	 *
	 * @return $this
	 */
	public function resize($width = false, $height = false)
	{
		/**
		 * В зависимости от типа ресайза, запишем в $newSize новые размеры изображения.
		 */
		if (is_numeric($width) && is_numeric($height) && $width > 0 && $height > 0)
		{
			$newSize = $this->getSizeByFramework($width, $height);
		}
		else
		{
			if (is_numeric($width) && $width > 0)
			{
				$newSize = $this->getSizeByWidth($width);
			}
			else
			{
				if (is_numeric($height) && $height > 0)
				{
					$newSize = $this->getSizeByHeight($height);
				}
				else
				{
					$newSize = array($this->width, $this->height);
				}
			}
		}
		$newImage = imagecreatetruecolor($newSize[0], $newSize[1]);
		//завернуть в отдельную функцию, сжимающую изображение поэтапно
		imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $newSize[0], $newSize[1], $this->width, $this->height);
		$this->image = $newImage;
		$this->setSize();
		return $this;
	}

	/**
	 * @param int $x0
	 * @param int $y0
	 * @param bool $w
	 * @param bool $h
	 *
	 * @return object
	 */
	public function crop($x0 = 0, $y0 = 0, $w = false, $h = false)
	{
		if (!is_numeric($x0) || $x0 < 0 || $x0 >= $this->width)
		{
			$x0 = 0;
		}
		if (!is_numeric($y0) || $y0 < 0 || $y0 >= $this->height)
		{
			$y0 = 0;
		}
		if (!is_numeric($w) || $w <= 0 || $w > $this->width - $x0)
		{
			$w = $this->width - $x0;
		}
		if (!is_numeric($h) || $h <= 0 || $h > $this->height - $y0)
		{
			$h = $this->height - $y0;
		}
		return $this->cropSave($x0, $y0, $w, $h);
	}

	/**
	 * @param bool $x0
	 * @param bool $y0
	 * @param bool $size
	 *
	 * @return object
	 */
	public function cropSquare($x0 = false, $y0 = false, $size = false)
	{
		if (!is_numeric($size) || $size <= 0)
		{
			$size = false;
		}
		if (!is_numeric($x0) && !is_numeric($y0))
		{
			if ($this->width < $this->height)
			{
				$x0 = 0;
				if (!$size || $size > $this->width)
				{
					$size = $this->width;
					$y0 = round(($this->height - $size) / 2);
				}
				else
				{
					$y0 = 0;
				}
			}
			else
			{
				$y0 = 0;
				if (!$size || $size > $this->height)
				{
					$size = $this->height;
					$x0 = round(($this->width - $size) / 2);
				}
				else
				{
					$x0 = 0;
				}
			}
		}
		else
		{
			if (!is_numeric($x0) || $x0 <= 0 || $x0 >= $this->width)
			{
				$x0 = 0;
			}
			if (!is_numeric($y0) || $y0 <= 0 || $y0 >= $this->height)
			{
				$y0 = 0;
			}
			if (!$size || $this->width < $size + $x0)
			{
				$size = $this->width - $x0;
			}
			if (!$size || $this->height < $size + $y0)
			{
				$size = $this->height - $y0;
			}
		}
		return $this->cropSave($x0, $y0, $size, $size);
	}

	/**
	 * @param $x0
	 * @param $y0
	 * @param $w
	 * @param $h
	 *
	 * @return $this
	 */
	private function cropSave($x0, $y0, $w, $h)
	{
		$newImage = imagecreatetruecolor($w, $h);
		imagecopyresampled($newImage, $this->image, 0, 0, $x0, $y0, $w, $h, $w, $h);
		$this->image = $newImage;
		$this->setSize();
		return $this;
	}

	/**
	 * @param $width
	 * @param $height
	 * @param int $c
	 *
	 * @return $this
	 */
	public function thumbnail($width, $height, $c = 2)
	{
		if (!is_numeric($width) || $width <= 0)
		{
			$width = $this->width;
		}
		if (!is_numeric($height) || $height <= 0)
		{
			$height = $this->height;
		}
		if (!is_numeric($c) || $c <= 1)
		{
			$c = 2;
		}
		$newSize = $this->getSizeByThumbnail($width, $height, $c);
		$newImage = imagecreatetruecolor($newSize[0], $newSize[1]);
		imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $newSize[0], $newSize[1], $this->width, $this->height);
		$this->image = $newImage;
		$this->setSize();
		return $this;
	}

	/**
	 * @param string $path
	 * @param $fileName
	 * @param bool $type
	 * @param bool $rewrite
	 * @param int $quality
	 *
	 * @return bool|string
	 */
	public function save($path = '', $fileName, $type = false, $rewrite = false, $quality = 100)
	{
		if (trim($fileName) == '' || $this->image === false)
		{
			return false;
		}
		$type = strtolower($type);
		switch ($type)
		{
			case false:
				$savePath = $path . trim($fileName) . "." . $this->type;
				switch ($this->type)
				{
					case 'jpg':
						if (!$rewrite && @file_exists($savePath))
						{
							return false;
						}
						if (!is_numeric($quality) || $quality < 0 || $quality > 100)
						{
							$quality = 100;
						}
						imagejpeg($this->image, $savePath, $quality);
						return $savePath;
					case 'png':
						if (!$rewrite && @file_exists($savePath))
						{
							return false;
						}
						imagepng($this->image, $savePath);
						return $savePath;
					case 'gif':
						if (!$rewrite && @file_exists($savePath))
						{
							return false;
						}
						imagegif($this->image, $savePath);
						return $savePath;
					default:
						return false;
				}
				break;
			case 'jpg':
				$savePath = $path . trim($fileName) . "." . $type;
				if (!$rewrite && @file_exists($savePath))
				{
					return false;
				}
				if (!is_numeric($quality) || $quality < 0 || $quality > 100)
				{
					$quality = 100;
				}
				imagejpeg($this->image, $savePath, $quality);
				return $savePath;
			case 'png':
				$savePath = $path . trim($fileName) . "." . $type;
				if (!$rewrite && @file_exists($savePath))
				{
					return false;
				}
				imagepng($this->image, $savePath);
				return $savePath;
			case 'gif':
				$savePath = $path . trim($fileName) . "." . $type;
				if (!$rewrite && @file_exists($savePath))
				{
					return false;
				}
				imagegif($this->image, $savePath);
				return $savePath;
			default:
				return false;
		}
	}

	/**
	 * Приватная функция, "открывающая" файл в зависимости от типа изображения.
	 *
	 * @param $file string    Путь исходного файла
	 */
	private function openImage($file)
	{
		switch ($this->type)
		{
			case 'jpg':
				$this->image = @imagecreatefromjpeg($file);
				break;
			case 'png':
				$this->image = @imagecreatefrompng($file);
				break;
			case 'gif':
				$this->image = @imagecreatefromgif($file);
				break;
			default:
				exit("File is not an image");
		}
	}

	/**
	 * Приватная функция, записывающая в поле type тип исходного изображения
	 *
	 * @param $file string    Путь исходного файла
	 *
	 * @return boolean        TRUE, если файл является изображением. FALSE - в противном случае.
	 */
	private function setType($file)
	{
		$mime = mime_content_type($file);
		switch ($mime)
		{
			case 'image/jpeg':
				$this->type = "jpg";
				return true;
			case 'image/png':
				$this->type = "png";
				return true;
			case 'image/gif':
				$this->type = "gif";
				return true;
			default:
				return false;
		}
	}

	/**
	 * Приватная функция, записывающая размеры исходного изображения
	 */
	private function setSize()
	{
		$this->width = imagesx($this->image);
		$this->height = imagesy($this->image);
	}

	/**
	 * Приватная функция, определяющая размеры нового изображения при вписывании его в рамки.
	 * Сжатие происходит пропорционально.
	 *
	 * @param $width integer        Максимальная ширина нового изображения
	 * @param $height integer    Максимальная высота нового изображения
	 *
	 * @return array            Массив, содержащий размеры нового изображения
	 */
	private function getSizeByFramework($width, $height)
	{
		if ($this->width <= $width && $this->height <= height)
		{
			return array($this->width, $this->height);
		}
		if ($this->width / $width > $this->height / $height)
		{
			$newSize[0] = $width;
			$newSize[1] = round($this->height * $width / $this->width);
		}
		else
		{
			$newSize[1] = $height;
			$newSize[0] = round($this->width * $height / $this->height);
		}
		return $newSize;
	}

	/**
	 * Приватная функция, определяющая размеры нового изображения при сжатии по ширине.
	 * Сжатие происходит пропорционально.
	 *
	 * @param $width integer        Максимальная ширина нового изображения
	 *
	 * @return array            Массив, содержащий размеры нового изображения
	 */
	private function getSizeByWidth($width)
	{
		if ($width >= $this->width)
		{
			return array($this->width, $this->height);
		}
		$newSize[0] = $width;
		$newSize[1] = round($this->height * $width / $this->width);
		return $newSize;
	}

	/**
	 * Приватная функция, определяющая размеры нового изображения при сжатии по высоте.
	 * Сжатие происходит пропорционально.
	 *
	 * @param $height integer    Максимальная высота нового изображения
	 *
	 * @return array            Массив, содержащий размеры нового изображения
	 */
	private function getSizeByHeight($height)
	{
		if ($height >= $this->height)
		{
			return array($this->width, $this->height);
		}
		$newSize[1] = $height;
		$newSize[0] = round($this->width * $height / $this->height);
		return $newSize;
	}

	/**
	 * @param $width
	 * @param $height
	 * @param $c
	 *
	 * @return array
	 */
	private function getSizeByThumbnail($width, $height, $c)
	{
		if ($this->width <= $width && $this->height <= $height)
		{
			return array($this->width, $this->height);
		}
		$realW = $this->width;
		$realH = $this->height;

		$rotate = false;
		if ($width / $realW <= $height / $realH)
		{
			$t = $realH;
			$realH = $realW;
			$realW = $t;
			$t = $width;
			$width = $height;
			$height = $t;
			$rotate = true;
		}

		$limX = $c * $width;
		$limY = $c * $height;
		$possH = $realH * $width / $realW;

		if ($realW > $width)
		{
			if ($possH <= $limY)
			{
				$newSize[0] = $width;
				$newSize[1] = round($possH);
			}
			else
			{
				if ($possH <= 2 * $limY)
				{
					$newSize[1] = $limY;
					$newSize[0] = $realW * $newSize[1] / $realH;
				}
				else
				{
					$newSize[0] = $width / 2;
					$newSize[1] = $realH * $newSize[0] / $realW;
				}
			}
		}
		else
		{
			if ($realH <= $limY)
			{
				$newSize[0] = $realW;
				$newSize[1] = $realH;
			}
			else
			{
				if ($realH <= 2 * $limY)
				{
					if ($realW * $limY / $realH >= $width / 2)
					{
						$newSize[1] = $limY;
						$newSize[0] = $realW * $limY / $realH;
					}
					else
					{
						$newSize[0] = $width / 2;
						$newSize[1] = $realH * $newSize[0] / $realW;
					}
				}
				else
				{
					$newSize[0] = $width / 2;
					$newSize[1] = $realH * $newSize[0] / $realW;
				}
			}
		}
		if ($rotate)
		{
			$t = $newSize[0];
			$newSize[0] = $newSize[1];
			$newSize[1] = $t;
		}
		return $newSize;
	}
}
