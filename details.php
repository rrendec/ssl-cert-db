<?php

require_once('common.php');

$pem = get_cert_pem($_REQUEST['id']);
$crt = openssl_x509_parse($pem);

function fmt_serial($hex)
{
    if (strlen($hex) % 2) {
        $hex = '0' . $hex;
    }

    return implode(':', str_split($hex, 2));
}

function fmt_date($timestamp)
{
    return date('r', $timestamp);
}

function fmt_purposes($purposes)
{
    $lines = [];
    foreach ($purposes as $data) {
        if ($data[0]) {
            $lines[] = $data[2];
        }
    }

    return implode('<br>', $lines);
}

function fmt_extension($k, $v)
{
    if ($k == 'authorityKeyIdentifier') {
        $v = preg_replace('/^keyid:/i', '', $v);
    }

    return htmlentities($v);
}

?>
<html>
<body alink="#ff0000" link="#800000" vlink="#800000">
  <font size="-1">
  <table border="1" cellspacing="0" cellpadding="2" style="font-size: small">
    <tr>
      <th align="left">Subject</th>
      <td>
        <table cellspacing="0" cellpadding="0" style="font-size: small">
        <?php foreach ($crt['subject'] as $k => $v) { ?>
          <tr>
            <td align="right"><?= $k ?>=</td>
            <td><b><?= htmlentities($v) ?></b></td>
          </tr>
        <?php } ?>
        </table>
      </td>
    </tr>
    <tr>
      <th align="left">Issuer</th>
      <td>
        <table cellspacing="0" cellpadding="0" style="font-size: small">
        <?php foreach ($crt['issuer'] as $k => $v) { ?>
          <tr>
            <td align="right"><?= $k ?>=</td>
            <td><b><?= htmlentities($v) ?></b></td>
          </tr>
        <?php } ?>
        </table>
      </td>
    </tr>
    <tr>
      <th align="left">Serial</th>
      <td><?= fmt_serial($crt['serialNumberHex']) ?></td>
    </tr>
    <tr>
      <th align="left">Valid From</th>
      <td><?= fmt_date($crt['validFrom_time_t']) ?></td>
    </tr>
    <tr>
      <th align="left">Valid To</th>
      <td><?= fmt_date($crt['validTo_time_t']) ?></td>
    </tr>
    <tr>
      <th align="left">Signature</th>
      <td><?= sprintf('%s (%s)', $crt['signatureTypeSN'], $crt['signatureTypeLN']) ?></td>
    </tr>
    <tr>
      <th align="left">Purposes</th>
      <td><?= fmt_purposes($crt['purposes']) ?></td>
    </tr>
    <tr>
      <th align="left">Extensions</th>
      <td>
        <table cellspacing="0" cellpadding="0" style="font-size: small">
        <?php foreach ($crt['extensions'] as $k => $v) { ?>
          <tr>
            <td><?= $k ?></td>
            <td>&nbsp;<?= fmt_extension($k, $v) ?></td>
          </tr>
        <?php } ?>
        </table>
      </td>
    </tr>
  </table>
  <br>
  <b>Raw certificate</b>
  <pre><?= $pem ?></pre>
  </font>
</body>
</html>
