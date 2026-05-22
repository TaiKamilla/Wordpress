<style>
	ul.wp-frontend-admin-sites-selector {
		list-style: none;
		box-sizing: border-box;
		display: block;
		text-align: center;
		overflow: auto;
		color: black;
	}
	ul.wp-frontend-admin-sites-selector li {
		margin: 0;
		border-right: 1px solid #999;
		width: 200px;
		display: inline-block;
		padding: 10px;
		box-sizing: border-box;
		float: left;
	}
	ul.wp-frontend-admin-sites-selector li a {
		display: block;
	}
	ul.wp-frontend-admin-sites-selector li h3 {
		font-size: 20px;
	}
</style>
<ul class="wp-frontend-admin-sites-selector">
	<?php
	foreach ($manageable_sites as $user_blog) {
		$is_current = (int) $current_site_id === (int) $user_blog->blog_id;
		if ($exclude_current_site === 'yes' && $is_current) {
			continue;
		}
		switch_to_blog($user_blog->blog_id);
		?>
		<li class="<?php
		if ((int) $is_current) {
			echo 'active';
		}
		?>">
			<h3><?php echo esc_html(get_blog_option($user_blog->blog_id, 'blogname')); ?></h3>
			<?php
			echo "<a class='wpfa-visit-link' href='" . esc_url(home_url('/')) . "'>" . __('Visit') . '</a>';

			if ((int) $is_current) {
				echo __('Active', VG_Admin_To_Frontend::$textname);
			} else {
				echo "<a class='wpfa-dashboard-link' href='" . esc_url($this->get_dashboard_url($user_blog->blog_id)) . "'>" . __('Dashboard') . '</a>';
			}
			?>
		</li>
		<?php
		restore_current_blog();
	}
	?>
</ul>