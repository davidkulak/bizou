<?php

$quickPath = isset($_SERVER["PATH_INFO"])?$_SERVER["PATH_INFO"]:"";
$scriptPath = $_SERVER["SCRIPT_NAME"];

$quickDir = dirname($quickPath);
$realDir = "images$quickDir";

$files = scandir($realDir);
$size = count($files);

$pos = array_search(basename($quickPath),$files);

$nextImage = '';
for ($next=$pos+1; $nextImage === '' and $next<$size ; $next++) {
	$mime = mime_content_type("$realDir/$files[$next]");
	if ($mime == "image/jpeg")
		$nextImage = $files[$next];
}

$prevImage = '';
for ($prev=$pos-1; $prevImage === '' and $prev>=0 ; $prev--) {
	$mime = mime_content_type("$realDir/$files[$prev]");
	if ($mime == "image/jpeg")
		$prevImage = $files[$prev];
}

$imageUrl = dirname($scriptPath)."/images$quickPath";

if ($nextImage === '') {
	$nextImageUrl = '';
	$nextPageUrl = '';
} else {
	$nextImageUrl = dirname($scriptPath)."/images".dirname($quickPath)."/$nextImage";
	$nextPageUrl = dirname($_SERVER["REQUEST_URI"])."/$nextImage";
}
if ($prevImage === '') $prevPageUrl = '';
else $prevPageUrl = dirname($_SERVER["REQUEST_URI"])."/$prevImage";

$directoryUrl = dirname($_SERVER["SCRIPT_NAME"])."/index".dirname($quickPath);

header('Content-Type: text/html; charset=utf-8');
header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
?>
<html>
<head>
<style type="text/css">
html, body {
height: 100%;
}
body {
margin: 0;
text-align: center;
background: black;
color: white;
}
#theimage {
max-width: 100%;
max-height: 100%;
}
a {
	color: white;
	text-decoration: none;
}
#next, #previous, #up {
	position: fixed;
	font-size: 4em;
	font-weight: bold;
}

#up {
	top: 0;
	left: 0;
	
}
#next {
	top: 50%;
	right: -0;
	
}
#previous {
	top: 50%;
	left: 0;
}
img {
	border: 0;
}
</style>

<?php if ($nextImageUrl !== '') { ?>
<link rel="prefetch" href="<?php echo $nextImageUrl ?>" />
<link rel="prefetch" href="<?php echo $nextPageUrl ?>" />
<?php } ?>

</head>
<body>

<a href="<?php echo $imageUrl ?>"><img src="<?php echo $imageUrl ?>" id="theimage" /></a>

<div id="up">
<a href="<?php echo $directoryUrl ?>" title="Back to directory">^</a>
</div>

<?php if ($nextPageUrl !== '') { ?>
<div id="next">
<a href="<?php echo $nextPageUrl ?>" title="Next image">&gt;</a>
</div>
<?php } ?>

<?php if ($prevPageUrl !== '') { ?>
<div id="previous">
<a href="<?php echo $nextPageUrl ?>" title="Previous image">&lt;</a>
</div>
<?php } ?>

</body>
</html>
