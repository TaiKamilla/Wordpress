<style>
	body {
		min-height: 250px;
	}
</style>
<?php
echo wp_kses_post( $permissions_message );
?>
<script>
	var body = document.body,
			html = document.documentElement;

	var height = Math.max(body.scrollHeight, body.offsetHeight,
			html.clientHeight, html.scrollHeight, html.offsetHeight);

	// Remove the loading indicator from the parent iframe
	var parent = (window.location.href.indexOf('&elementor-preview') > -1) ? window.parent.parent : window.parent;
	parent.document.querySelectorAll('.vgca-iframe-wrapper .vgca-loading-indicator').forEach(function (element) {
		element.style.display = 'none';
	});
	parent.document.querySelectorAll('.vgca-iframe-wrapper').forEach(function (element) {
		element.classList.remove("vgfa-is-loading");
		element.style.height = height + 'px';
	});
	parent.document.querySelectorAll('.vgca-iframe-wrapper iframe').forEach(function (element) {
		element.style.height = height + 'px';
	});
</script>
