<?php

namespace WatchDog\Utils;

class FileSystem {

    // Public Methods.
    public static function loadStringFromDirectory($path, $default = null){

        $result = $default;
        $s = $path . DIRECTORY_SEPARATOR;
        if (file_exists($s)){
            $files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator(realpath($s)), \RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $file){
                // Get first available.
                $p = $file->getPath();
                if ($file->getFilename() === '.' &&  $p !== $path){
                    $result = substr($p, strrpos($p, DIRECTORY_SEPARATOR) + 1);
                    break;
                }
            }
        }

        return $result;

    }
    public static function loadStringFromFile($file = null, $path = null, $default = null){

        $result = $default;

        if (is_null($file) && !is_null($path) && file_exists($path)){
            $s = $path . DIRECTORY_SEPARATOR;
            $files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator(realpath($s)), \RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $file){
                // Get first available.
                $f = $file->getFilename();
                if ($f !== '.' && $f !== '..' && is_file($file->getRealPath())){
                    $result = $f;
                    break;
                }
            }
        } elseif (!is_null($file) && file_exists($file)) {
            try {
                $result = @file_get_contents(is_null($path) ? $file : $path . DIRECTORY_SEPARATOR . $file);
            } catch (\Exception $e){}
        }

        return $result;

    }
    public static function createHtAccessFile($path){

        $result = false;
        $fileName = $path . DIRECTORY_SEPARATOR . '.htaccess';

        try {
            if (!is_file($fileName))
                file_put_contents($fileName, 'deny from all');
            $result = true;
        } catch (\Exception $e){}

        return $result;

    }
    public static function deleteHtAccessFile($path){

        $result = false;
        $fileName = $path . DIRECTORY_SEPARATOR . '.htaccess';

        try {
            if (file_exists($fileName))
                unlink($fileName);
            $result = true;
        } catch (\Exception $e){}

        return $result;

    }
    public static function createDirectory($path, $permissions = 0750){

        $result = false;

        try {
            if (!file_exists($path))
                mkdir($path, $permissions);

            if (is_dir($path) && strtolower(substr(PHP_OS, 0, 1)) !== 'w'){
                $p = decoct(fileperms($path) & 0777);
                if ($p !== $permissions)
                    chmod($path, $permissions);
            }
            $result = true;
        } catch (\Exception $e){}

        return $result;

    }
    public static function deleteDirectory($path){

        $result = false;

        try {
            if (file_exists($path) && is_dir($path))
                rmdir($path);
            $result = true;
        } catch (\Exception $e){}

        return $result;

    }
    public static function getDirectoryUsername(){

        $s = dirname(WATCHDOG_PATH);
        $s = str_replace(DIRECTORY_SEPARATOR . 'home' . DIRECTORY_SEPARATOR, '', $s);
        $pos = strpos($s, DIRECTORY_SEPARATOR);
        if ($pos !== false)
            $s = substr($s, 0, $pos - 1);

        return strlen($s) === 1 ? '' : $s;

    }
    public static function getAccessHash(){

        $result = '';

        $file = dirname(WATCHDOG_PATH) . __DS__ . '.accesshash';
        if (file_exists($file)){
            $result = str_replace("\r", '', file_get_contents($file));
            $result = str_replace("\n", '', $result);
            $result = str_replace("\t", '', $result);
            $result = str_replace(' ', '', $result);
        }

        return $result;

    }
    public static function getDirectoriesAndFiles($path = null, array $extensions = array(), $maxDepth = -1){

        $path = is_null($path) ? '/home/' . self::getDirectoryUsername() : $path;
        if (substr($path, strlen($path) - 1, 1) !== __DS__)
            $path .= __DS__;

        $result = array();
        $files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator(realpath($path)), \RecursiveIteratorIterator::LEAVES_ONLY);
        if ($maxDepth > -1)
            $files->setMaxDepth($maxDepth);

        if (count($extensions) > 0){
            $s = implode(count($extensions) === 1 ? '' : '|', $extensions);
            $files = new \RegexIterator($files, '/^.+(' . $s . ')$/i');//, \RecursiveRegexIterator::GET_MATCH);
        }

        foreach ($files as $file){

            $f = $file->getFilename();
            $p = $file->getPath();

            if (/*$f === '.' &&*/ !array_key_exists($p, $result)){
                $result[$p] = array(
                    'files' => array(),
                    'totalSize' => 0,
                    'fileCount' => 0,
                    'permissions' => decoct(fileperms($p) & 0777),
                );
            }

            if ($f !== '.' && $f !== '..'){
                $size = filesize($file->getRealPath());
                $result[$p]['totalSize'] += $size;
                $result[$p]['fileCount']++;
                $result[$p]['files'][] = array(
                    'file' => $f,
                    'size' => $size,
                    'permissions' => decoct(fileperms($file->getRealPath()) & 0777),
                );
            }

        }

        return $result;

    }
    public static function getFiles($path, array $extensions = array()){

        if (substr($path, strlen($path) - 1, 1) !== __DS__)
            $path .= __DS__;

        $result = array();

        if (file_exists($path)){
            $files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator(realpath($path)), \RecursiveIteratorIterator::LEAVES_ONLY);
            if (count($extensions) > 0){
                $s = implode(count($extensions) === 1 ? '' : '|', $extensions);
                $files = new \RegexIterator($files, '/^.+(' . $s . ')$/i');//, \RecursiveRegexIterator::GET_MATCH);
            }

            foreach ($files as $file){

                $f = $file->getFilename();
                $p = $file->getRealPath();

                if ($f !== '.' && $f !== '..'){
                    $result[] = array(
                        'file' => $f,
                        'size' => filesize($p),
                        'permissions' => decoct(fileperms($p) & 0777),
                    );
                }

            }
        }

        return $result;

    }
    public static function formatBytes($bytes, $short = true){

        if ($bytes === 0)
            return '0 Bytes';
        elseif ($bytes === 1)
            return '1 Byte';

        $arr = array(
            'BB' => array('long' => 'Brontobyte', 'short' => 'BB'),
            'YB' => array('long' => 'Yottabyte', 'short' => 'YB'),
            'ZB' => array('long' => 'Zettabyte', 'short' => 'ZB'),
            'EB' => array('long' => 'Exabyte', 'short' => 'EB'),
            'PB' => array('long' => 'Petabyte', 'short' => 'PB'),
            'TB' => array('long' => 'Terabyte', 'short' => 'TB'),
            'GB' => array('long' => 'Gigabyte', 'short' => 'GB'),
            'MB' => array('long' => 'Megabyte', 'short' => 'MB'),
            'KB' => array('long' => 'Kilobyte', 'short' => 'KB'),
            'byte' => array('long' => 'Byte', 'short' => 'Bytes'),
        );

        $i = count($arr);
        foreach ($arr as $k => $v){
            $pow = pow(1024, --$i);
            if ($bytes >= $pow){
                $exact = $bytes === $pow;
                $tag = ($short ? $v['short'] : $v['long']);// . ($exact ? '' : 's');
                if (!$short && !$exact)
                    $tag .= 's';
                $bytes = str_replace('.00', '', number_format($bytes / $pow, 2)) . " $tag";
                break;
            }
        }

        return $bytes;

    }
    public static function getFileName($path, $withExtension = true){

        $result = pathinfo($path);
        return $result['filename'] . ($withExtension && array_key_exists('extension', $result) ? '.' . $result['extension'] : '');

    }
    public static function getFileExtension($path){

        $result = pathinfo($path);
        return $result['extension'];

    }
    public static function getMimeType($file){

        $result = '';

        try {

            // finfo is not enabled on remote server, so
            // have to provide a fallback.
            if (!function_exists('finfo_open')){

                $result = self::getMimeTypeInternal($file);

            } else {

                $info = finfo_open(FILEINFO_MIME_TYPE);
                $result = finfo_file($info, $file);
                finfo_close($info);

            }

        } catch (\Exception $e){}

        return $result;

    }
    public static function findZbBlockPath($path){

        $zbBlock = $path . __DS__ . 'zbblock' . __DS__ . 'vault';
        if (!file_exists($zbBlock))
            $zbBlock = $path . __DS__ . 'public_html' . __DS__ . 'zbblock' . __DS__ . 'vault';

        return file_exists($zbBlock) ? $zbBlock : null;

    }

    // Private Methods.
    private static function getMimeTypeInternal($file) {

        $types = self::getMimeTypes();
        $extension = self::getFileExtension($file);

        // Set default.
        $result = 'application/octet-stream';

        if (array_key_exists($extension, $types))
            $result = $types[$extension];

        return $result;

    }
    private static function getMimeTypes() {

        $mimeTypes = array();

        $mimeTypes['323'] = 'text/h323';
        $mimeTypes['acx'] = 'application/internet-property-stream';
        $mimeTypes['ai'] = 'application/postscript';
        $mimeTypes['aif'] = 'audio/x-aiff';
        $mimeTypes['aifc'] = 'audio/x-aiff';
        $mimeTypes['aiff'] = 'audio/x-aiff';
        $mimeTypes['asf'] = 'video/x-ms-asf';
        $mimeTypes['asr'] = 'video/x-ms-asf';
        $mimeTypes['asx'] = 'video/x-ms-asf';
        $mimeTypes['au'] = 'audio/basic';
        $mimeTypes['avi'] = 'video/x-msvideo';
        $mimeTypes['axs'] = 'application/olescript';
        $mimeTypes['bas'] = 'text/plain';
        $mimeTypes['bcpio'] = 'application/x-bcpio';
        $mimeTypes['bin'] = 'application/octet-stream';
        $mimeTypes['bmp'] = 'image/bmp';
        $mimeTypes['c'] = 'text/plain';
        $mimeTypes['cat'] = 'application/vnd.ms-pkiseccat';
        $mimeTypes['cdf'] = 'application/x-cdf';
        $mimeTypes['cer'] = 'application/x-x509-ca-cert';
        $mimeTypes['class'] = 'application/octet-stream';
        $mimeTypes['clp'] = 'application/x-msclip';
        $mimeTypes['cmx'] = 'image/x-cmx';
        $mimeTypes['cod'] = 'image/cis-cod';
        $mimeTypes['cpio'] = 'application/x-cpio';
        $mimeTypes['crd'] = 'application/x-mscardfile';
        $mimeTypes['crl'] = 'application/pkix-crl';
        $mimeTypes['crt'] = 'application/x-x509-ca-cert';
        $mimeTypes['csh'] = 'application/x-csh';
        $mimeTypes['css'] = 'text/css';
        $mimeTypes['dcr'] = 'application/x-director';
        $mimeTypes['der'] = 'application/x-x509-ca-cert';
        $mimeTypes['dir'] = 'application/x-director';
        $mimeTypes['dll'] = 'application/x-msdownload';
        $mimeTypes['dms'] = 'application/octet-stream';
        $mimeTypes['doc'] = 'application/msword';
        $mimeTypes['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $mimeTypes['dot'] = 'application/msword';
        $mimeTypes['dvi'] = 'application/x-dvi';
        $mimeTypes['dxr'] = 'application/x-director';
        $mimeTypes['eps'] = 'application/postscript';
        $mimeTypes['etx'] = 'text/x-setext';
        $mimeTypes['evy'] = 'application/envoy';
        $mimeTypes['exe'] = 'application/octet-stream';
        $mimeTypes['fif'] = 'application/fractals';
        $mimeTypes['flr'] = 'x-world/x-vrml';
        $mimeTypes['gif'] = 'image/gif';
        $mimeTypes['gtar'] = 'application/x-gtar';
        $mimeTypes['gz'] = 'application/x-gzip';
        $mimeTypes['h'] = 'text/plain';
        $mimeTypes['hdf'] = 'application/x-hdf';
        $mimeTypes['hlp'] = 'application/winhlp';
        $mimeTypes['hqx'] = 'application/mac-binhex40';
        $mimeTypes['hta'] = 'application/hta';
        $mimeTypes['htc'] = 'text/x-component';
        $mimeTypes['htm'] = 'text/html';
        $mimeTypes['html'] = 'text/html';
        $mimeTypes['htt'] = 'text/webviewhtml';
        $mimeTypes['ico'] = 'image/x-icon';
        $mimeTypes['ief'] = 'image/ief';
        $mimeTypes['iii'] = 'application/x-iphone';
        $mimeTypes['ins'] = 'application/x-internet-signup';
        $mimeTypes['isp'] = 'application/x-internet-signup';
        $mimeTypes['jfif'] = 'image/pipeg';
        $mimeTypes['jpe'] = 'image/jpeg';
        $mimeTypes['jpeg'] = 'image/jpeg';
        $mimeTypes['jpg'] = 'image/jpeg';
        $mimeTypes['js'] = 'application/x-javascript';
        $mimeTypes['latex'] = 'application/x-latex';
        $mimeTypes['lha'] = 'application/octet-stream';
        $mimeTypes['lsf'] = 'video/x-la-asf';
        $mimeTypes['lsx'] = 'video/x-la-asf';
        $mimeTypes['lzh'] = 'application/octet-stream';
        $mimeTypes['m13'] = 'application/x-msmediaview';
        $mimeTypes['m14'] = 'application/x-msmediaview';
        $mimeTypes['m3u'] = 'audio/x-mpegurl';
        $mimeTypes['man'] = 'application/x-troff-man';
        $mimeTypes['mdb'] = 'application/x-msaccess';
        $mimeTypes['me'] = 'application/x-troff-me';
        $mimeTypes['mht'] = 'message/rfc822';
        $mimeTypes['mhtml'] = 'message/rfc822';
        $mimeTypes['mid'] = 'audio/mid';
        $mimeTypes['mny'] = 'application/x-msmoney';
        $mimeTypes['mov'] = 'video/quicktime';
        $mimeTypes['movie'] = 'video/x-sgi-movie';
        $mimeTypes['mp2'] = 'video/mpeg';
        $mimeTypes['mp3'] = 'audio/mpeg';
        $mimeTypes['mp4'] = 'video/mpeg';
        $mimeTypes['mpa'] = 'video/mpeg';
        $mimeTypes['mpe'] = 'video/mpeg';
        $mimeTypes['mpeg'] = 'video/mpeg';
        $mimeTypes['mpg'] = 'video/mpeg';
        $mimeTypes['mpp'] = 'application/vnd.ms-project';
        $mimeTypes['mpv2'] = 'video/mpeg';
        $mimeTypes['ms'] = 'application/x-troff-ms';
        $mimeTypes['mvb'] = 'application/x-msmediaview';
        $mimeTypes['nws'] = 'message/rfc822';
        $mimeTypes['oda'] = 'application/oda';
        $mimeTypes['p10'] = 'application/pkcs10';
        $mimeTypes['p12'] = 'application/x-pkcs12';
        $mimeTypes['p7b'] = 'application/x-pkcs7-certificates';
        $mimeTypes['p7c'] = 'application/x-pkcs7-mime';
        $mimeTypes['p7m'] = 'application/x-pkcs7-mime';
        $mimeTypes['p7r'] = 'application/x-pkcs7-certreqresp';
        $mimeTypes['p7s'] = 'application/x-pkcs7-signature';
        $mimeTypes['pbm'] = 'image/x-portable-bitmap';
        $mimeTypes['pdf'] = 'application/pdf';
        $mimeTypes['pfx'] = 'application/x-pkcs12';
        $mimeTypes['pgm'] = 'image/x-portable-graymap';
        $mimeTypes['pko'] = 'application/ynd.ms-pkipko';
        $mimeTypes['pma'] = 'application/x-perfmon';
        $mimeTypes['pmc'] = 'application/x-perfmon';
        $mimeTypes['pml'] = 'application/x-perfmon';
        $mimeTypes['pmr'] = 'application/x-perfmon';
        $mimeTypes['pmw'] = 'application/x-perfmon';
        $mimeTypes['pnm'] = 'image/x-portable-anymap';
        $mimeTypes['pot'] = 'application/vnd.ms-powerpoint';
        $mimeTypes['ppm'] = 'image/x-portable-pixmap';
        $mimeTypes['pps'] = 'application/vnd.ms-powerpoint';
        $mimeTypes['ppt'] = 'application/vnd.ms-powerpoint';
        $mimeTypes['pptx'] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        $mimeTypes['prf'] = 'application/pics-rules';
        $mimeTypes['ps'] = 'application/postscript';
        $mimeTypes['pub'] = 'application/x-mspublisher';
        $mimeTypes['qt'] = 'video/quicktime';
        $mimeTypes['ra'] = 'audio/x-pn-realaudio';
        $mimeTypes['ram'] = 'audio/x-pn-realaudio';
        $mimeTypes['ras'] = 'image/x-cmu-raster';
        $mimeTypes['rgb'] = 'image/x-rgb';
        $mimeTypes['rmi'] = 'audio/mid';
        $mimeTypes['roff'] = 'application/x-troff';
        $mimeTypes['rtf'] = 'application/rtf';
        $mimeTypes['rtx'] = 'text/richtext';
        $mimeTypes['scd'] = 'application/x-msschedule';
        $mimeTypes['sct'] = 'text/scriptlet';
        $mimeTypes['setpay'] = 'application/set-payment-initiation';
        $mimeTypes['setreg'] = 'application/set-registration-initiation';
        $mimeTypes['sh'] = 'application/x-sh';
        $mimeTypes['shar'] = 'application/x-shar';
        $mimeTypes['sit'] = 'application/x-stuffit';
        $mimeTypes['snd'] = 'audio/basic';
        $mimeTypes['spc'] = 'application/x-pkcs7-certificates';
        $mimeTypes['spl'] = 'application/futuresplash';
        $mimeTypes['src'] = 'application/x-wais-source';
        $mimeTypes['sst'] = 'application/vnd.ms-pkicertstore';
        $mimeTypes['stl'] = 'application/vnd.ms-pkistl';
        $mimeTypes['stm'] = 'text/html';
        $mimeTypes['svg'] = 'image/svg+xml';
        $mimeTypes['sv4cpio'] = 'application/x-sv4cpio';
        $mimeTypes['sv4crc'] = 'application/x-sv4crc';
        $mimeTypes['swf'] = 'application/x-shockwave-flash';
        $mimeTypes['t'] = 'application/x-troff';
        $mimeTypes['tar'] = 'application/x-tar';
        $mimeTypes['tcl'] = 'application/x-tcl';
        $mimeTypes['tex'] = 'application/x-tex';
        $mimeTypes['texi'] = 'application/x-texinfo';
        $mimeTypes['texinfo'] = 'application/x-texinfo';
        $mimeTypes['tgz'] = 'application/x-compressed';
        $mimeTypes['tif'] = 'image/tiff';
        $mimeTypes['tiff'] = 'image/tiff';
        $mimeTypes['tr'] = 'application/x-troff';
        $mimeTypes['trm'] = 'application/x-msterminal';
        $mimeTypes['tsv'] = 'text/tab-separated-values';
        $mimeTypes['txt'] = 'text/plain';
        $mimeTypes['uls'] = 'text/iuls';
        $mimeTypes['ustar'] = 'application/x-ustar';
        $mimeTypes['vcf'] = 'text/x-vcard';
        $mimeTypes['vrml'] = 'x-world/x-vrml';
        $mimeTypes['wav'] = 'audio/x-wav';
        $mimeTypes['wcm'] = 'application/vnd.ms-works';
        $mimeTypes['wdb'] = 'application/vnd.ms-works';
        $mimeTypes['wks'] = 'application/vnd.ms-works';
        $mimeTypes['wmf'] = 'application/x-msmetafile';
        $mimeTypes['wps'] = 'application/vnd.ms-works';
        $mimeTypes['wri'] = 'application/x-mswrite';
        $mimeTypes['wrl'] = 'x-world/x-vrml';
        $mimeTypes['wrz'] = 'x-world/x-vrml';
        $mimeTypes['xaf'] = 'x-world/x-vrml';
        $mimeTypes['xbm'] = 'image/x-xbitmap';
        $mimeTypes['xla'] = 'application/vnd.ms-excel';
        $mimeTypes['xlc'] = 'application/vnd.ms-excel';
        $mimeTypes['xlm'] = 'application/vnd.ms-excel';
        $mimeTypes['xls'] = 'application/vnd.ms-excel';
        $mimeTypes['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $mimeTypes['xlt'] = 'application/vnd.ms-excel';
        $mimeTypes['xlw'] = 'application/vnd.ms-excel';
        $mimeTypes['xof'] = 'x-world/x-vrml';
        $mimeTypes['xpm'] = 'image/x-xpixmap';
        $mimeTypes['xwd'] = 'image/x-xwindowdump';
        $mimeTypes['z'] = 'application/x-compress';
        $mimeTypes['zip'] = 'application/zip';

        return $mimeTypes;
    }

}