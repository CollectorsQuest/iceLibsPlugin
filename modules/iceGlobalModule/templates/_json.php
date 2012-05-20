<!DOCTYPE html>
<html>
<head>
  <title>JSON Response</title>
</head>
<body>
  <h1>JSON</h1>
  <?php echo IceFunctions::json_format(json_encode($sf_data->getRaw('data'))); ?>

  <h1>Original Data Structure</h1>
  <pre><?php print_r($sf_data->getRaw('data')); ?></pre>
</body>
</html>
