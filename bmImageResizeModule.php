<?php

/**
 * Created by PhpStorm.
 * User: vir-mir
 * Date: 08.08.14
 * Time: 20:07
 */
trait bmImageResizeModule
{

	private $allowedDimensions = array();

	/**
	 * @param $imagePath
	 * @param $dimension
	 *
	 * @return string
	 */
	private function getGeometry($imagePath, $dimension)
	{
		$geometry = '';
		$imageSize = getimagesize($imagePath);

		if (preg_match('/^\d+$/', $dimension))
		{
			$geometry = $dimension . 'x' . $dimension . '\>';
		}
		elseif (preg_match('/^w\d+$/', $dimension))
		{
			$geometry = substr($dimension, 1);
			if ($imageSize[0] <= $geometry)
			{
				$geometry = 'copy';
			}

		}
		elseif (preg_match('/^h\d+$/', $dimension))
		{
			$geometry = 'x' . substr($dimension, 1);
		}
		elseif (preg_match('/^\d+x\d+$/', $dimension))
		{
			$imageDimensions = explode('x', $dimension);
			$width = $imageDimensions[0];
			$height = $imageDimensions[1];

			$geometry = 'x' . $width . ' -resize ' . "'" . $height . "x<'" . ' -gravity center -extent ' . $dimension;
		}
		elseif (preg_match('/^g\d+x\d+$/', $dimension))
		{
			$dimension = ltrim($dimension, 'g');
			$geometry = "{$dimension}^ -gravity North -extent {$dimension}";
		}
		elseif (preg_match('/^s\d+$/', $dimension))
		{
			$imageDimension = substr($dimension, 1);
			$dimensions = $imageDimension . 'x' . $imageDimension;

			$geometry = $dimensions + '\>^';
		}

		return $geometry;
	}

	/**
	 * @param $fileUrl
	 *
	 * @return string
	 * @throws \PHPImageWorkshop\Core\Exception\ImageWorkshopLayerException
	 * @throws \PHPImageWorkshop\Exception\ImageWorkshopException
	 */
	private function resize($fileUrl)
	{
		if (!$this->allowedDimensions)
		{
			include(projectRoot . '/conf/allowedImageSizes.conf');
		}

		$modificator = '';
		$returnTo = '';
		$file = explode('/', $fileUrl);
		$fileName = array_pop($file);
		array_pop($file);
		$size = array_pop($file);

		$file = implode('/', $file);

		$folder = rtrim(documentRoot, '/') . BM_C_IMAGE_FOLDER . $file . '/' . $size . '/' . mb_substr($fileName, 0, 2) . '/';
		@mkdir($folder, 0777, true);
		chmod(rtrim(documentRoot, '/') . BM_C_IMAGE_FOLDER . $file . '/' . $size . '/', 0777);
		chmod(rtrim(documentRoot, '/') . BM_C_IMAGE_FOLDER . $file . '/' . $size . '/' . mb_substr($fileName, 0, 2) . '/', 0777);

		$originFile = rtrim(documentRoot, '/') . BM_C_IMAGE_FOLDER . $file . '/originals/' . mb_substr($fileName, 0, 2) . '/' . $fileName;
		$url = BM_C_IMAGE_FOLDER . $file . '/' . $size . '/' . mb_substr($fileName, 0, 2) . '/' . $fileName;
		if (in_array($size, $this->allowedDimensions) && file_exists($originFile))
		{
			$geometry = $this->getGeometry($originFile, $size);


			if ($geometry != 'copy')
			{
				imagemagick_convert(
					' -resize ' . $geometry . ' ' . $originFile . ' ' . $folder . $fileName
				);
			}
			else
			{
				copy(
					$originFile,
					$folder . $fileName
				);
			}

			exec('chmod 0777 ' . $folder . $fileName);


			$returnTo = $url;
		}

		return $returnTo;
	}

} 