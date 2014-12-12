<?php

namespace Core;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of image_helper
 *
 * @author mepengadmin
 */
class ImageHelper {

	/**
	 * Creates a thumbnail image of a source image and saves it at the specified destination.
	 * Only works for Jpg, Gif, Png.
	 * 
	 * @param type $source Source image.
	 * @param type $destination Filename destination to save the thumbnail
	 * @param type $width Width of the thumbnail
	 * @param type $height Height of the thumbnail
	 * @return boolean True on successful thumbnail creation, false otherwise.
	 */
	public static function createThumbnail($source, $destination, $width, $height) {
		list($width_orig, $height_orig) = getimagesize($source);

		$ratio_orig = $width_orig / $height_orig;

		if($width / $height > $ratio_orig) {
			$width = $height * $ratio_orig;
		} else {
			$height = $width / $ratio_orig;
		}

		$image_thumb = imagecreatetruecolor($width, $height);

		// Determine what kind of image the file is.
		$ex = strtolower(pathinfo($source, PATHINFO_EXTENSION));

		if($ex == "jpeg" || $ex == "jpg") {
			$image = imagecreatefromjpeg($source);
			
		} elseif($ex == "png") {
			$image = imagecreatefrompng($source);
			
		} elseif($ex == "gif") {
			$image = imagecreatefromgif($source);
		}else{
			// Unknown image type.
			return false;
		}

		imagecopyresampled($image_thumb, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
		imagejpeg($image_thumb, $destination, 100);
		
		return imagedestroy($image) && imagedestroy($image_thumb);

	}

}