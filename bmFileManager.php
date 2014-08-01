<?php


abstract class bmFileManager extends bmFFObject
{
	private $allowedDimensions = [];
	
	private function createResizedImage($fileName, $dimension)
	{
		$imagePath = documentRoot . '/images/content/originals/' . mb_substr($fileName, 0, 2) . '/' . $fileName;
		$resultFileName = documentRoot . '/images/content/' . $dimension . '/' . mb_substr(
				$fileName,
				0,
				2
			) . '/' . $fileName;

		if (file_exists($resultFileName))
		{
			return true;
		}
		if (!file_exists($imagePath))
		{
			return false;
		}


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
		elseif (preg_match('/^s\d+$/', $dimension))
		{
			$imageDimension = substr($dimension, 1);
			$dimensions = $imageDimension . 'x' . $imageDimension;

			$geometry = $dimensions + '\>^';
		}


		if ($geometry != '')
		{
			if ($geometry != 'copy')
			{
				imagemagick_convert(
					' -resize ' . $geometry . ' ' . documentRoot . '/images/content/originals/' . mb_substr(
						$fileName,
						0,
						2
					) . '/' . $fileName . ' ' . $resultFileName
				);
			}
			else
			{
				copy(
					documentRoot . '/images/content/originals/' . mb_substr($fileName, 0, 2) . '/' . $fileName,
					documentRoot . '/images/content/' . $dimension . '/' . mb_substr($fileName, 0, 2) . '/' . $fileName
				);
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	public function acceptUpload($type);
	{

		if (array_key_exists('Filedata', $_FILES))
		{

			$fileData = $_FILES['Filedata'];

			if ($fileData['error'] == UPLOAD_ERR_OK)
			{
				switch ($type)
				{
					case 'image':
						$md5 = md5(md5_file($fileData['tmp_name']));
						$originalName = $fileData['name'];
						//$imageId = $this->application->getObjectIdByFieldName('image', 'md5', $md5);
						$imageId = 0;
						$image = new bmImage($this->application, array('identifier' => $imageId));

						if ($imageId > 0)
						{
							$fileName = $image->fileName;
						}
						else
						{
							$fileName = md5(uniqid('', true));
							$image->name = $fileData['name'];
							$image->fileName = $fileName;
							$image->md5 = $md5;
						}
						$originalsDirectoryName = documentRoot . '/images/content/originals/' . mb_substr($fileName, 0, 2) . '/';
						if (!file_exists($originalsDirectoryName))
						{
							mkdir($originalsDirectoryName, 0777, true);
						}

						$originalsImagePath = $originalsDirectoryName . $fileName;
						if (!file_exists($originalsImagePath))
						{
							move_uploaded_file($fileData['tmp_name'], $originalsImagePath);

							if (is_file($originalsImagePath))
							{
								$imageInfo = @getimagesize($originalsImagePath);
								if (isset($imageInfo[0]) && isset($imageInfo[1]))
								{
									if (($imageInfo[0] > 0) && ($imageInfo[1] > 0))
									{
										$image->width = $imageInfo[0];
										$image->height = $imageInfo[1];
									}
								}
							}
						}

						$dimensionsString = trim($this->application->cgi->getGPC('dimensions', ''));


						//$dimensions = array('100', '200x200');

						/* */
						$dimensions = explode(',', $dimensionsString);
						if (!in_array('200x200', $dimensions))
						{
							$dimensions[] = '200x200';
						}
						/* */
						//$dimensions = $this->allowedDimensions;

						foreach ($dimensions as $dimension)
						{
							$dimension = trim($dimension);

							if (!in_array($dimension, $this->allowedDimensions))
							{
								continue;
							}

							$directoryName = documentRoot . '/images/content/' . $dimension . '/' . mb_substr($fileName, 0, 2) . '/';
							if (!file_exists($directoryName))
							{
								mkdir($directoryName, 0777, true);
							}

							$this->createResizedImage($fileName, $dimension);
						}
						break;


					case 'file':
						$md5 = md5(md5_file($fileData['tmp_name']));
						$fileId = $this->application->getObjectIdByFieldName('file', 'md5', $md5);
						$file = new bmImage($this->application, array('identifier' => $fileId));

						if ($fileId > 0)
						{
							$fileName = $file->fileName;
						}
						else
						{
							$fileName = md5(uniqid('', true));
							$originalsDirectoryName = documentRoot . '/files/content/' . mb_substr($fileName, 0, 2) . '/';
							if (!file_exists($originalsDirectoryName))
							{
								mkdir($originalsDirectoryName, 0777, true);
							}

							move_uploaded_file($fileData['tmp_name'], $originalsDirectoryName . $fileName);

							$file->name = $fileData['name'];
							$file->fileName = $fileName;
							$file->md5 = $md5;
						}
						break;
					default:

						break;
				}
			}
		}
	}

	return parent::execute();
}

}
