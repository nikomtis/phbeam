<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo $meta['title']; ?></title>
		<meta name="description" content="<?php echo $meta['description']; ?>">
		<meta name="keywords" content="<?php echo $meta['keywords']; ?>">
		<?php css('main'); ?>
	</head>
	<body<?php if ($body_class) echo " class=\"$body_class\""; ?>>
		<?php position('content_top'); ?>
		<?php layout($article, $layout); ?>
		<?php position('content_bottom'); ?>
	</body>
</html>
