window.$ = jQuery;
$(document).ready(function () {
    $(document).on('wpfaUpdatedIframeData', function(e, data ){
        var requestData = {
            action: 'vgfa_update_site_plugins_theme',
            nonce: vgfa_data.nonce,
        };
        var save = false;
        if( data.data.extra_data.last_plugin_change && vgfa_data.current_site_last_plugin_update && data.data.extra_data.last_plugin_change > vgfa_data.current_site_last_plugin_update && vgfa_data.current_site_last_plugins_hash !== data.data.extra_data.last_plugin_change_hash ){
            requestData.plugins = data.data.extra_data.active_plugins;
            save = true;
            // Update the local timestamp to not repeat the ajax call until the iframe sends a new timestamp
            vgfa_data.current_site_last_plugin_update = data.data.extra_data.last_plugin_change;
            requestData.last_plugins_hash = data.data.extra_data.last_plugin_change_hash;
        }
        if( data.data.extra_data.last_theme_change && vgfa_data.current_site_last_theme_update && data.data.extra_data.last_theme_change > vgfa_data.current_site_last_theme_update ){
            requestData.theme = data.data.extra_data.active_theme;
            save = true;
            // Update the local timestamp to not repeat the ajax call until the iframe sends a new timestamp
            vgfa_data.current_site_last_theme_update = data.data.extra_data.last_theme_change;
        }

        if( save ){
            $.post(vgfa_data.wp_ajax_url, requestData, function( response){
                if( response.success ){
                    window.location.reload();
                }
            });
        }
    });
});