<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


$file_path = PT_CV_PATH . 'admin/views/dashboard.php';

$text = PT_CV_Functions::file_include_content( $file_path );

echo strip_tags( $text, '<style><div><h3><p><ul><li><h4><a>' );
?>

<style>
	.cv-admin-settings { margin: 0; padding: 0; max-width: 100%; }
	.cv-admin-content { grid-template-columns: auto; gap: 0; }
	.cv-admin-settings .cv-admin-section:not(.features-list) { display: none; }
</style>