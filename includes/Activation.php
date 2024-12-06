<?php 

namespace SAIL;

use InvalidArgumentException;
use SAIL\Packages\Providers\TableProvider;

class Activation {

	const upload_directory = 'wp_sail';

	public function __construct(){
		$this->boot_table_provider();
        register_activation_hook(SAIL_FILE, array($this, "make_plugin_upload_directory"));
		register_uninstall_hook(SAIL_FILE, [__CLASS__, 'remove_plugin_upload_directory']);
	}

	public function make_plugin_upload_directory(){
		$upload_directory = self::upload_directory;
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'] . $upload_directory;
		$permissions = 0755;
		$oldmask = umask(0);
		if( !is_dir($upload_dir) ) {
			mkdir($upload_dir, $permissions);
		}
		$umask = umask($oldmask);
		$chmod = chmod($upload_dir, $permissions);	
	}

	private static function remove_directory($upload_dir){
		if ( !is_dir($upload_dir) ) {
			throw new InvalidArgumentException( sprintf( __("%s is not a directory","sail"), $upload_dir ) );
		}
		$upload_dir = trailingslashit($upload_dir);
		$files = glob($upload_dir . '*', GLOB_MARK);
		foreach( $files as $file ){
			if ( is_dir($file) ){
				self::remove_directory($file);
			} else {
				unlink($file);
			}
		}
		rmdir($upload_dir);
	}

	public static function remove_plugin_upload_directory(){
		$upload_directory = self::upload_directory;
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'] . $upload_directory;
		static::remove_directory($upload_dir);
	}

    public function boot_table_provider(){
		new TableProvider([
            Tables\Session::class => trailingslashit(__DIR__) . "Tables/Session.php",
		]);
    }
}

new Activation;
