<?php

require_once('common.php');

function request() {
    if (!@$_REQUEST['pem']) {
        return [true, null];
    }

    $total = 0;
    $new = 0;
    preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $_REQUEST['pem'], $matches);
    foreach ($matches[0] as $pem) {
        $data = openssl_x509_parse($pem);
        if (!$data) {
            continue;
        }

        $id = get_duplicate_cert($data);
        insert_or_update_cert($data, $pem, $id);
        $total++;
        if (!$id) {
            $new++;
        }
    }

    return [true, sprintf('%d certificate(s) imported (%d new)', $total, $new)];
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
