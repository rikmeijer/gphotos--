<?php
/**
 * @param $bytes
 * @param int $decimals
 * @return string
 */
function human_filesize($bytes, $decimals = 2) {
    $sz = ['B','K','M','G','T','P'];
    $factor = min(count($sz), floor((strlen($bytes) - 1) / 3));
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $sz[$factor];
}

function recurse(string $path, Closure $fileClosure) : array {
    $directory = opendir($path);
    $files = [];
    while ($node = readdir($directory)) {
        if (substr($node, 0, 1) === '.') {
            continue;
        }

        $nodePath = $path . DIRECTORY_SEPARATOR . $node;
        if (is_dir($nodePath)) {
            $files += recurse($nodePath, $fileClosure);
        } elseif ($fileClosure($nodePath)) {
            $files[$nodePath] = filesize($nodePath);
        }
    }
    closedir($directory);
    return $files;
}

$files = recurse($_SERVER['argv'][1], function(string $nodePath) : bool {
    if (is_file($nodePath) === false) {
        return false;
    }

    $extension = strtolower(pathinfo($nodePath, PATHINFO_EXTENSION));
    if ($extension === '') {
        $extension = explode('/', mime_content_type($nodePath) )[1];
        rename($nodePath, $nodePath . '.' . $extension);
        $nodePath .= '.' . $extension;
    }

    switch ($extension) {
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            $size = getimagesize($nodePath);
            if ($size === false) {
                print PHP_EOL . 'Fail: ' . ($extension) . ' ' . $nodePath;
            }
            if ($size[0] * $size[1] > 10^6*16) {
                return true;
            }
            return false;

        case 'mp4':
        case 'mpg':
        case 'm4v':
        case 'avi':
        case 'mov':
        case 'mpeg':
        case '3gp':
        case 'wmv':
            exec('mediainfo --Output=JSON ' . escapeshellarg($nodePath), $output);
            $info = json_decode(implode('',$output));
            foreach ($info->media->track as $track) {
                if ($track->{'@type'} !== 'Video') {
                    continue;
                } elseif ($track->Height > 1080) {
                    return true;
                }
            }
            return false;

        default:
            print PHP_EOL . 'Fail: ' . ($extension) . ' ' . $nodePath;
            return false;
    }
    return false;
});
echo PHP_EOL . human_filesize(array_sum($files)) . '/' . count($files) . ' files would be scaled';
echo PHP_EOL;