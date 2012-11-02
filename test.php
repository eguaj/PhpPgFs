<?php

ini_set('error_log', null);

include_once('Class.PhpPgFs.php');

stream_wrapper_register('pgfs', 'PgFs') || die(sprintf("Failed registering protocol 'pgfs'."));

$ctx = stream_context_create(
    array(
        'pgfs' => array(
            'dsn' => 'service=pgfs',
        )
    )
);

printf("\n* fopen()\n");
$file = 'pgfs:///home/john.doe/foo.txt';
$fp = fopen($file, 'r', false, $ctx);
if ($fp === false) {
    die(sprintf("Error opening '%s'.\n", $file));
}

printf("\n* stream_get_contents()\n");
$data = stream_get_contents($fp);
if ($data === false) {
    die(sprintf("Error getting content from '%s'.\n", $file));
}
var_dump($data);

printf("\n* fclose()\n");
fclose($fp);

printf("\n* file_get_contents()\n");
$data = file_get_contents('pgfs:///home/john.doe/bar.txt', null, $ctx);
if ($data === false) {
    die(sprintf("Error getting content from '%s'.\n", $file));
}
var_dump($data);

printf("\n* opendir()\n");
$dir = 'pgfs:///home/john.doe';
$fh = opendir($dir, $ctx);
if ($fh === false) {
    die(sprintf("Error opening dir '%s'.\n", $dir));
}

printf("\n* readdir()\n");
while (($f = readdir($fh)) !== false) {
    printf("  (%s) %s\n", $dir, $f);
}

printf("\n* rewind()\n");
rewind($fh);

printf("\n* readdir()\n");
while (($f = readdir($fh)) !== false) {
    printf("  (%s) %s\n", $dir, $f);
}

printf("\nDone.\n");