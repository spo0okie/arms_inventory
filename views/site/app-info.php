<?php
ob_start();
phpinfo();
echo ob_get_clean();
?>

<h2>App params</h2>
<pre>
<?php print_r(Yii::$app->params); ?>
</pre>