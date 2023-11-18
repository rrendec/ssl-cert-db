<?php

require_once('common.php');

$now = time();
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'pending';
$list = get_aggregate_cert($type, $now);

?>
<html>
<body>
  <font size="-1">
  <table border="1" cellspacing="0" cellpadding="2" style="font-size: small">
    <tr>
      <th>Subject</th>
      <th>Issuer</th>
      <th>Valid To</th>
      <th>Days Left</th>
    </tr>
    <?php foreach ($list as $crt) { ?>
    <tr>
      <td><?= htmlentities(canonical_short($crt['subject'])) ?></td>
      <td><?= htmlentities(canonical_short($crt['issuer'])) ?></td>
      <td><?= format_datetime($crt['valid']) ?></td>
      <td align="right"><font color="blue"><?= days_between($now, $crt['valid']) ?></font></td>
    </tr>
    <?php } ?>
  </table>
  </font>
</body>
</html>
