<?php
/*
Plugin Name: SC Normalize Upload Dir
Plugin URI: https://github.com/Softcatala/sc-normalize-upload-dir
Description: Functions to filter and normalize upload dir. Based on functions from https://github.com/thephpleague/flysystem/blob/master/src/Util.php#L80
Version: 0.1
Author: Xavi Ivars
Author URI: http://xavi.ivars.me
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

add_filter('upload_dir', 'sc_upload_dir');
function sc_upload_dir( $params ) {
    return array_map('sc_normalize_path', $params);	
}

function sc_normalize_path($path)
{
    // Remove any kind of funky unicode whitespace
    $normalized = preg_replace('#\p{C}+|^\./#u', '', $path);
    $normalized = sc_normalize_relative_path($normalized);
    if (preg_match('#/\.{2}|^\.{2}/|^\.{2}$#', $normalized)) {
        throw new LogicException(
            'Path is outside of the defined root, path: [' . $path . '], resolved: [' . $normalized . ']'
        );
    }
    $normalized = preg_replace('#\\\{2,}#', '\\', trim($normalized, '\\'));
    $normalized = preg_replace('#/{2,}#', '/', trim($normalized, '/'));
    return $normalized;
}
	
function sc_normalize_relative_path($path)
{
    // Path remove self referring paths ("/./").
    $path = preg_replace('#/\.(?=/)|^\./|/\./?$#', '', $path);
    // Regex for resolving relative paths
    $regex = '#/*[^/\.]+/\.\.#Uu';
    while (preg_match($regex, $path)) {
        $path = preg_replace($regex, '', $path);
    }
    return $path;
}

