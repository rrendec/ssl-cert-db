<?php

require_once('common.php');

$now = time();
$list = get_history($_REQUEST['subject'], $_REQUEST['issuer']);

function bgcolor($crt)
{
    global $now;

    return $crt['valid'] < $now ? '#ffeeee' : '#eeffee';
}

function href($crt)
{
    return sprintf('details.php?id=%s', urlencode($crt['id']));
}

?>
<html>
<body alink="#ff0000" link="#800000" vlink="#800000">
  <font size="-1">
  <table border="1" cellspacing="0" cellpadding="2" style="font-size: small">
    <tr>
      <th>Subject</th>
      <th>Issuer</th>
      <th>Valid To</th>
      <th>Days Left</th>
    </tr>
    <?php foreach ($list as $crt) { ?>
    <tr style="background: <?= bgcolor($crt) ?>">
      <td><a href="<?= href($crt)?>"><?= htmlentities(canonical_short($crt['subject'])) ?></a></td>
      <td><?= htmlentities(canonical_short($crt['issuer'])) ?></td>
      <td><?= format_datetime($crt['valid']) ?></td>
      <td align="right"><font color="blue"><?= days_between($now, $crt['valid']) ?></font></td>
    </tr>
    <?php } ?>
  </table>
  </font>
</body>
</html>
