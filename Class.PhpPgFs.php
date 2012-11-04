<?php

/*

This code is released under the MIT/X11 license.

Copyright (c) 2012 Jérôme Augé <jerome&punctum;auge&ad;gmail&punctum;com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

class PgFs
{
    public $context;

    private $dbh = null;
    private $position = 0;
    private $fileId = null;
    private $_p = array();

    public function __construct()
    {
    }

    public function dir_closedir()
    {
        $this->_reset();
        return true;
    }

    public function dir_opendir($path, $options)
    {
        $path = $this->_contextizePath($path);
        if ($path === false) {
            return false;
        }
        $file = $this->_getFileFromPath($path);
        if ($file === false) {
            return false;
        }
        if ($file['type'] != 'd') {
            return false;
        }
        $this->_reset();
        $this->fileId = $file['id'];
        return true;
    }

    public function dir_readdir()
    {
        if (!isset($this->_p['content'])) {
            $this->_p['content'] = $this->_getDirContent($this->fileId);
        }
        if ($this->_p['content'] === false) {
            return false;
        }

        if (!isset($this->_p['length'])) {
            $this->_p['length'] = count($this->_p['content']);
        }
        if ($this->_p['length'] <= 0) {
            return false;
        }
        if (($this->position + 1) > $this->_p['length']) {
            return false;
        }

        return $this->_p['content'][$this->position++]['name'];
    }

    public function dir_rewinddir()
    {
        $this->position = 0;
        return true;
    }

    public function mkdir($path, $mode, $options)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    public function rename($path_from, $path_to)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    public function rmdir($path, $options)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    public function stream_cast($cast_as)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    public function stream_close()
    {
        $this->_reset();
        return true;
    }

    public function stream_eof()
    {
        $size = $this->_getFileSizeFromId($this->fileId);
        if ($size === false) {
            return false;
        }
        return $this->position >= $size;
    }

    public function stream_flush()
    {
        return true;
    }

    public function stream_lock($operation)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    public function stream_metadata($path, $option, $var)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $path = $this->_contextizePath($path);
        if ($path === false) {
            return false;
        }
        $this->_reset();
        $this->fileId = $this->_getFileIdFromPath($path);
        return true;
    }

    function stream_read($count)
    {
        if ($this->_pgConnect() === false) {
            return false;
        }
        $q = sprintf('SELECT substring(data from %s for %s) AS data FROM fs_data WHERE id = %s', $this->position + 1, $count, $this->fileId);
        error_log(__METHOD__ . " " . $q);
        $res = pg_fetch_all(pg_query($this->dbh, $q));
        if (count($res) <= 0) {
            return false;
        }
        $ret = pg_unescape_bytea($res[0]['data']);
        $this->position += strlen($ret);
        return $ret;
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    public function stream_set_option($option, $args1, $args2)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    public function stream_stat()
    {
        $size = $this->_getFileSizeFromId($this->fileId);
        if ($size === false) {
            return false;
        }
        return array('size' => $size);
    }

    public function stream_tell()
    {
        return $this->position;
    }

    public function stream_truncate($new_size)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    public function stream_write($data)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    public function unlink($path)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    public function url_stat($path, $flags)
    {
        error_log(__METHOD__ . " " . sprintf("Not yet implemented!"));
        return false;
    }

    private function _reset()
    {
        $this->position = 0;
        $this->fileId = null;
        $this->_p = array();
        if (is_resource($this->dbh)) {
            pg_close($this->dbh);
        }
        $this->dbh = null;
    }

    private function _contextizePath($path)
    {
        $path = $this->_stripUrlScheme($path);
        if ($path === false) {
            return false;
        }
        $path = $this->_absolutizePath($path);
        return $path;
    }

    private function _absolutizePath($path)
    {
        if (substr($path, 0, 1) != '/') {
            $path = getcwd() . '/' . $path;
        }
        return $path;
    }

    private function _stripUrlScheme($path)
    {
        if (substr($path, 0, 7) != 'pgfs://') {
            return false;
        }
        return substr($path, 7);
    }

    private function _getFileSizeFromId($fileId)
    {
        if ($this->_pgConnect() === false) {
            return false;
        }
        $q = sprintf('SELECT octet_length(data) AS length FROM fs_data WHERE id = %s', $fileId);
        error_log(__METHOD__ . " " . $q);
        $res = pg_fetch_all(pg_query($this->dbh, $q));
        if (count($res) <= 0) {
            return false;
        }
        return $res[0]['length'];
    }

    private function _getFileIdFromPath($path)
    {
        $file = $this->_getFileFromPath($path);
        if ($file === false) {
            return false;
        }
        return $file['id'];
    }

    private function _getFileFromPath($path)
    {
        if ($this->_pgConnect() === false) {
            return false;
        }
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
        $pathElmts = preg_split(':/+:', $path);
        $fileName = array_pop($pathElmts);
        $dirId = 0;
        foreach ($pathElmts as $p) {
            $q = sprintf("SELECT * FROM fs_dirs, fs_files WHERE fs_dirs.dir = %s AND fs_dirs.file = fs_files.id", $dirId);
            error_log(__METHOD__ . " " . $q);
            $res = pg_fetch_all(pg_query($this->dbh, $q));
            foreach ($res as $r) {
                if ($r['name'] == $p) {
                    if ($r['type'] != 'd') {
                        return false;
                    }
                    $dirId = $r['id'];
                    break;
                }
            }
        }
        $q = sprintf("SELECT * FROM fs_dirs, fs_files WHERE fs_dirs.dir = %s AND fs_dirs.file = fs_files.id AND fs_files.name = '%s'", $dirId, $fileName);
        error_log(__METHOD__ . " " . $q);
        $res = pg_fetch_all(pg_query($this->dbh, $q));
        if (count($res) <= 0) {
            return false;
        }
        return $res[0];
    }

    private function _getParentDir($fileId)
    {
        $ret = $this->_pgConnect();
        if ($ret === false) {
            return false;
        }
        $q = sprintf("SELECT * FROM fs_dirs WHERE file = %s", $fileId);
        error_log(__METHOD__ . " " . $q);
        $res = pg_fetch_all(pg_query($this->dbh, $q));
        if (count($res <= 0)) {
            return null;
        }
        return $res[0]['dir'];
    }

    private function _getDirContent($fileId)
    {
        $ret = $this->_pgConnect();
        if ($ret === false) {
            return false;
        }
        $q = sprintf("SELECT * FROM fs_dirs, fs_files WHERE dir = %s AND file = id", $fileId);
        error_log(__METHOD__ . " " . $q);
        return pg_fetch_all(pg_query($this->dbh, $q));
    }

    private function _pgConnect()
    {
        if ($this->dbh !== null) {
            $stat = pg_connection_status($this->dbh);
            if ($stat === PGSQL_CONNECTION_OK) {
                return $this->dbh;
            }
            pg_close($this->dbh);
            $this->dbh = null;
        }
        $opts = stream_context_get_options($this->context);
        $dsn = false;
        if (isset($opts['pgfs']['dsn'])) {
            $dsn = $opts['pgfs']['dsn'];
        }
        $dbh = pg_connect($dsn);
        if ($dbh === false) {
            error_log(__METHOD__ . " " . sprintf("Error connecting to DSN '%s': %s", $dsn, pg_last_error()));
            return false;
        }
        $this->dbh = $dbh;
        return $this->dbh;
    }
}
