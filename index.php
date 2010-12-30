<?php
/*
    Bizou - a (french) KISS php image gallery
    Copyright (C) 2010  Marc MAURICE

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require 'config.php';

// load plugins
$plugins = scandir("plugins");
array_shift($plugins); array_shift($plugins); // remove . and ..
foreach ($plugins as $p) if (is_file("plugins/$p/functions.php"))
	require "plugins/$p/functions.php";

function plugins_include($phpFile)
{
	foreach ($GLOBALS['plugins'] as $p) if (is_file("plugins/$p/$phpFile"))
		require "plugins/$p/$phpFile";
}

if (! function_exists('getThumbTarget')) {
function getThumbTarget($imageSimplePath)
{
	return dirname($_SERVER["SCRIPT_NAME"]).'/'.IMAGES_DIR.$imageSimplePath;
}
}

function getPreview($imgFile, $maxSize = THUMB_SIZE)
{
	# example: data/myalbum/100.mypic.jpg
	$newImgFile = DATA_DIR."/".dirname($imgFile)."/".$maxSize.".".basename($imgFile);
	
	if (! is_file($newImgFile))
	{
		$ext = strtolower(substr($imgFile, -4));
		if ($ext == ".jpg")
			$img = imagecreatefromjpeg($imgFile);
		else
			$img = imagecreatefrompng($imgFile);

		$w = imagesx($img);
		$h = imagesy($img);
		# don't do anything if the image is already small
		if ($w <= $maxSize and $h <= $maxSize) {
			imagedestroy($img);
			return $imgFile;
		}

		# create the thumbs directory recursively
		if (! is_dir(dirname($newImgFile))) mkdir(dirname($newImgFile), 0777, true);

		if ($w > $h) {
			$newW = $maxSize;
			$newH = $h/($w/$maxSize);
		} else {
			$newW = $w/($h/$maxSize);
			$newH = $maxSize;
		}

		$newImg = imagecreatetruecolor($newW, $newH);

		imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);

		if ($ext == ".jpg")
			imagejpeg($newImg, $newImgFile);
		else
			imagepng($newImg, $newImgFile);
		
		imagedestroy($img);
		imagedestroy($newImg);
	}

	return dirname($_SERVER["SCRIPT_NAME"])."/".$newImgFile;
}

function getAlbumPreview($dir)
{
	foreach (scandir($dir) as $file) if ($file != '.' and $file != '..') {
		$ext = strtolower(substr($file, -4));
		if ($ext == ".jpg" or $ext == ".png")
			return getPreview("$dir/$file");
	}

	return '';
}

$scriptUrlPath = $_SERVER["SCRIPT_NAME"];

// if url == http://localhost/photos/index.php/toto/titi, path_info == /toto/titi
// if url == http://localhost/photos/index.php, path_info is not set
// if url == http://localhost/photos/, path_info is not set
// if path_info is not set, we are at top level, so we redirect to /photos/index.php/
if (! isset($_SERVER["PATH_INFO"])) {
	header("Location: $scriptUrlPath/");
	exit();
}

# shortPath is the simple path to the image
# /index.php/toto/titi => shortPath == /toto/titi
$shortPath = $_SERVER["PATH_INFO"];
if ($shortPath == '/') $shortPath = '';
// extra security check to avoid /photos/index/../.. like urls, maybe useless but..
if (strpos($shortPath, '..') !== false) die(".. found in url");

$folders = array();
$imageFiles = array();
$otherFiles = array();

# realDir is the directory in filesystem
# seen from current script directory
$realDir = IMAGES_DIR.$shortPath;

if (! is_dir($realDir)) {
	header("HTTP/1.1 404 Not Found");
	die("Directory Not Found");
}

foreach (scandir($realDir) as $file) if ($file != '.' and $file != '..')
{
	if (is_dir("$realDir/$file"))
	{
		$folders[] = array( "name" => $file, "link" => "$scriptUrlPath$shortPath/$file", "preview" => getAlbumPreview("$realDir/$file") );
	}
	else
	{
		$ext = strtolower(substr($file, -4));
		if ($ext == ".jpg" or $ext == ".png") {
				$link = dirname($scriptUrlPath)."/$realDir/$file";
				$link = getThumbTarget("$shortPath/$file");

			$imageFiles[] = array( "name" => $file, "url" => getPreview("$realDir/$file"), "link" => $link );

		} else {
			$otherFiles[] = array( "name" => $file, "link" => dirname($scriptUrlPath)."/$realDir/$file" );
		}
	}
}

if (dirname($shortPath) !== '')
	$parentLink = $scriptUrlPath.dirname($shortPath);
else
	$parentLink = "";

?>
<?php
///// template starts here /////
header('Content-Type: text/html; charset=utf-8');
header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
?>
<html>
<head>
<style type="text/css">
body {
	padding-top: 2em;
}
img {
	border: 0;
}
a {
	text-decoration: none;
}
.square {
	display: inline-block;
}
.image {
	width: <?php echo THUMB_SIZE ?>px;
	height: <?php echo THUMB_SIZE ?>px;
	display: table-cell;
	text-align: center;
	vertical-align: middle;
}
.foldername {
	height: <?php echo THUMB_SIZE ?>px;
	display: table-cell;
	vertical-align: middle;
}
#parentfolder {
	position: fixed;
	font-size: 4em;
	font-weight: bold;
	top: 0;
	left: 0;
}
</style>
<?php foreach ($plugins as $p) if (is_file("plugins/$p/style.css")) { ?>
	<link rel="stylesheet" type="text/css" href="<?php echo dirname($scriptUrlPath)."/plugins/$p/style.css" ?>" />
<?php } ?>
</head>
<body>

<?php if ($parentLink !== '') { ?>
	<div id="parentfolder"><a href="<?php echo $parentLink ?>">^</a></div>
<?php } ?>

<?php plugins_include("before_content.php") ?>

<?php foreach($folders as $folder) { ?>
	<div class="folder">
	<?php if ($folder["preview"] === "") { ?>
		<a href="<?php echo $folder["link"] ?>"><?php echo $folder["name"] ?></a>
	<?php } else { ?>
		<div class="square"><div class="image"><a href="<?php echo $folder["link"] ?>"><img src="<?php echo $folder["preview"] ?>" /></a></div></div>
		<div class="square"><div class="foldername"><a href="<?php echo $folder["link"] ?>"><?php echo $folder["name"] ?></a></div></div>
	<?php } ?>
	</div>
<?php } ?>

<?php foreach ($imageFiles as $file) { ?>
	<div class="square"><div class="image"><a href="<?php echo $file["link"] ?>"><img src="<?php echo $file["url"] ?>" alt="<?php echo $file["name"] ?>" /></a></div></div>
<?php } ?>

<?php foreach ($otherFiles as $file) { ?>
	<div class="miscfile"><a href="<?php echo $file["link"] ?>"><?php echo $file["name"] ?></a></div>
<?php } ?>

</body>
</html>
