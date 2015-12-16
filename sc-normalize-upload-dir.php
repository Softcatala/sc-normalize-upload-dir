<?php
/*
Plugin Name: SC Normalize Upload Dir
Plugin URI: https://github.com/Softcatala/sc-normalize-upload-dir
Description: Functions to filter and normalize upload dir. Based on functions from https://github.com/thephpleague/flysystem/blob/master/src/Util.php#L80
Version: 0.2
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

$sc_upload_dir_normalizer = new SC_Upload_Dir_Normalizer();

add_filter( 'upload_dir', array ( $sc_upload_dir_normalizer, 'sc_upload_dir' ) );

class SC_Upload_Dir_Normalizer {

	var $sc_cached_normalized_paths;

	public function __construct() {
		$this->sc_cached_normalized_paths = array();
	}

	public function sc_upload_dir( $params ) {
		$paths = array_filter( $params, 'is_string' );

		$normalized_paths = array_map( array( $this, 'sc_normalize_path' ) , $paths );

		return array_merge( $params, $normalized_paths );
	}

	private function sc_normalize_path( $path ) {

		if ( ! array_key_exists( $path , $this->sc_cached_normalized_paths ) ) {
			// Remove any kind of funky unicode whitespace
			$normalized = preg_replace( '#\p{C}+|^\./#u', '', $path );

			$normalized = $this->sc_normalize_relative_path( $normalized );

			if ( preg_match( '#/\.{2}|^\.{2}/|^\.{2}$#', $normalized ) ) {
				error_log( "Path outside of the root: " . $normalized );
			}

			$this->sc_cached_normalized_paths[$path] = $this->sc_remove_double_slashes( $normalized );
		}

		return $this->sc_cached_normalized_paths[$path];
	}

	private function sc_remove_double_slashes( $path ) {
		$wrapper = null;

		// Strip the protocol.
		if ( wp_is_stream( $path ) ) {
			list( $wrapper, $path ) = explode( '://', $path, 2 );
		}

		// From php.net/mkdir user contributed notes.
		$path	 = preg_replace( '#\\\{2,}#', '\\', trim( $path, '\\' ) );
		$path	 = preg_replace( '#/{2,}#', '/', $path );

		// Put the wrapper back on the target.
		if ( $wrapper !== null ) {
			$path = $wrapper . '://' . $path;
		}

		return $path;
	}

	private function sc_normalize_relative_path( $path ) {
		// Path remove self referring paths ("/./").
		$path = preg_replace( '#/\.(?=/)|^\./|/\./?$#', '', $path );

		// Regex for resolving relative paths
		$regex	 = '#/*[^/\.]+/\.\.#Uu';
		while ( preg_match( $regex, $path ) ) {
			$path = preg_replace( $regex, '', $path );
		}
		return $path;
	}
}
