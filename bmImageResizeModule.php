<?php
/**
 * Created by PhpStorm.
 * User: vir-mir
 * Date: 08.08.14
 * Time: 20:07
 */

trait bmImageResizeModule 
{

	private $allowedDimensions = array(
		'h100',
		'200x200',
	);

	private function resize($fileUrl)
	{
		$returnTo = '';
		$file = explode('/', $fileUrl);
		$fileName = array_pop($file);
		array_pop($file);
		$size = array_pop($file);

		$file = implode('/', $file);

		$folder = rtrim(documentRoot, '/') . BM_C_IMAGE_FOLDER . $file . '/' . $size . '/' . mb_substr($fileName, 0, 2) . '/';

		$originFile  = rtrim(documentRoot, '/') . BM_C_IMAGE_FOLDER . $file . '/originals/' . mb_substr($fileName, 0, 2) . '/' . $fileName;
		$url  = BM_C_IMAGE_FOLDER . $file . '/' . $size . '/' . mb_substr($fileName, 0, 2) . '/' . $fileName;

		if (in_array($size, $this->allowedDimensions) && file_exists($originFile))
		{
			$size = explode('x', $size);
			$width =  $height = null;

			if (count($size) > 1)
			{
				$width = $size[0];
				$height = $size[1];
			}
			else
			{
				$modificator = mb_substr($size[0], 0, 1);
				switch ($modificator)
				{
					case 'h':
						$height = mb_substr($size[0], 1);
						break;
					case 'w':
						$width = mb_substr($size[0], 1);
						break;
					default:
						$width = $size[0];
						break;
				}
			}


			$image = \PHPImageWorkshop\ImageWorkshop::initFromPath($originFile);
			$image->resizeInPixel($width, $height, true);
			$image->save($folder, $fileName, true, null, 100);

			$returnTo = $url;
		}

		return $returnTo;
	}

} 