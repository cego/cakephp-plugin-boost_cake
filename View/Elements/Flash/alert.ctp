<?php
if (isset($params['class'])) {
	$class = $params['class'];
}

if (!isset($class)) {
	$class = false;
}

if (!isset($close)) {
	$close = true;
}
?>
<div class="alert<?= ($class) ? ' ' . $class : '' ?>">
<?php if ($close): ?>
	<a class="close" data-dismiss="alert" href="#">&#xD7;</a>
<?php endif; ?>
	<?= $message ?>
</div>
