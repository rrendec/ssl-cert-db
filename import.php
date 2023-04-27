<?php

require_once('common.php');

function request() {
    if (!@$_REQUEST['pem']) {
        return [true, null];
    }

    $data = openssl_x509_parse($_REQUEST['pem']);
    if (!$data) {
        return [false, 'PEM parse error!'];
    }

    $id = get_duplicate_cert($data);
    insert_or_update_cert($data, $_REQUEST['pem'], $id);

    return [true, 'Import successful!'];
}

?>
<html>
<body>
  <?php $rc = dispatch(); ?>
  <form method="post">
    <textarea name="pem" rows="30" cols="85"><?= $rc ? '' : htmlentities(@$_REQUEST['pem'])?></textarea>
    <br><br>
    <input type="submit" name="submit" value="Import">
  </form>
</body>
</html>
