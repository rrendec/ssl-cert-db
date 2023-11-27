<?php
error_reporting(E_ALL);

// from https://www.php.net/manual/en/class.errorexception.php
function exception_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("exception_error_handler");

require_once 'config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
mysqli_select_db($db, DB_NAME);
mysqli_query($db, 'SET NAMES utf8');

function cn_escape($s)
{
    return strtr($s, [
        '\\'    => '\\\\',
        '/'     => '\\/',
        '='     => '\\=',
    ]);
}

function get_canonical_name($attrs)
{
    $out = [];
    foreach ($attrs as $key => $value) {
        $out[] = cn_escape($key) . '=' . cn_escape($value);
    }

    return implode('/', $out);
}

function parse_canonical_name($str)
{
    $n = strlen($str);
    $buf = '';
    $key = '';
    $out = [];
    $esc = false;
    for ($i = 0; $i < $n; $i++) {
        if ($esc) {
            $esc = false;
            $buf .= $str[$i];
            continue;
        }
        if ($str[$i] == '\\') {
            $esc = true;
            continue;
        }
        if ($str[$i] == '=') {
            $key = $buf;
            $buf = '';
            continue;
        }
        if ($str[$i] == '/') {
            if ($key !== '') {
                $out[$key] = $buf;
            }
            $key = '';
            $buf = '';
            continue;
        }
        $buf .= $str[$i];
    }

    if ($key !== '') {
        $out[$key] = $buf;
    }

    return $out;
}

function canonical_short($str)
{
    static $keys = ['CN', 'O'];

    $dn = parse_canonical_name($str);
    $attrs = [];
    foreach ($keys as $key) {
        if (isset($dn[$key])) {
            $attrs[$key] = $dn[$key];
        }
    }

    return get_canonical_name($attrs);
}

function get_duplicate_cert($data)
{
    global $db;

    $subject = get_canonical_name($data['subject']);
    $issuer = get_canonical_name($data['issuer']);

    $stmt = mysqli_prepare($db, 'SELECT id FROM cert WHERE subject=? AND issuer=? AND sn=?');
    mysqli_stmt_bind_param($stmt, 'sss', $subject, $issuer, $data['serialNumberHex']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return $id;
}

function fetch_results_assoc($stmt)
{
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $out = [];
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $out[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $out;
}

function get_aggregate_cert($type, $when, $offset = 0, $limit = 50)
{
    global $db;

    switch ($type) {
    case 'pending':
        $sql = 'SELECT subject, issuer, MAX(valid_to) AS valid FROM cert ' .
               'WHERE valid_to > ? GROUP BY subject, issuer ORDER BY valid LIMIT ?, ?';
        break;
    case 'expired':
        $sql = 'SELECT * FROM ('.
               '    SELECT subject, issuer, MAX(valid_to) AS valid FROM cert' .
               '    GROUP BY subject, issuer' .
               ') AS subquery WHERE valid < ? ORDER BY valid DESC LIMIT ?, ?';
        break;
    default:
        return [];
    }
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'iii', $when, $offset, $limit);

    return fetch_results_assoc($stmt);
}

function get_history($subject, $issuer, $offset = 0, $limit = 50)
{
    global $db;

    $sql = 'SELECT id, subject, issuer, valid_to AS valid FROM cert ' .
           'WHERE subject=? AND issuer=? ORDER BY valid DESC LIMIT ?, ?';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'ssii', $subject, $issuer, $offset, $limit);

    return fetch_results_assoc($stmt);
}

function get_cert_pem($id)
{
    global $db;

    $stmt = mysqli_prepare($db, 'SELECT pem FROM cert WHERE id=?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $pem);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return $pem;
}

function insert_or_update_cert($data, $pem, $id = null)
{
    global $db;

    $subject = get_canonical_name($data['subject']);
    $issuer = get_canonical_name($data['issuer']);

    if ($id) {
        $stmt = mysqli_prepare($db, 'UPDATE cert SET subject=?, issuer=?, sn=?, valid_from=?, valid_to=?, pem=? WHERE id=?');
        mysqli_stmt_bind_param($stmt, 'sssiisi', $subject, $issuer, $data['serialNumberHex'], $data['validFrom_time_t'],
                               $data['validTo_time_t'], $pem, $id);
    } else {
        $stmt = mysqli_prepare($db, 'INSERT INTO cert(subject, issuer, sn, valid_from, valid_to, pem) VALUES(?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssiis', $subject, $issuer, $data['serialNumberHex'], $data['validFrom_time_t'],
                               $data['validTo_time_t'], $pem);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function format_datetime($ts)
{
    return date('Y-m-d H:i:s', $ts);
}

function days_between($ts1, $ts2)
{
    return round(($ts2 - $ts1) / 86400);
}

function print_exception($e)
{
    printf("<font color=\"red\"><b>%s in %s on line %d</b></font><br>\n",
           htmlentities($e->getMessage()), $e->getFile(), $e->getLine());
    printf("<pre>%s</pre><br>\n", htmlentities($e->getTraceAsString()));
}

function dispatch()
{
    try {
        list($rc, $message) = request();
    } catch(Exception $e) {
        while ($e) {
            print_exception($e);
            $e = $e->getPrevious();
        }

        return;
    }

    if ($message) {
        printf("<font color=\"%s\"><b>%s</b></font><br><br>\n", $rc ? 'blue' : 'red', htmlentities($message));
    }

    return $rc;
}
