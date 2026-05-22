Vue.component('component-required-plugins', {
    template: "#wpfa-component-required-plugins" ,
    props: {
        listOfRequiredPlugins: Array
    },
    data(){
        return{
            languageApp: {}, 
            urlBasePlugin: "",
            listOfInstalledPlugins: [],
            urlAdmin: '',
            nonceAjaxInstallPlugin: '',
            nonceAjaxActivateWpPlugin: '',
            disabledAllButtons: false,
            listPlugins: [],
            currentPluginToInstall: 0,
            listOfPluginsToBeInstalledInASingleStep: []
        }
    },
    computed:{
        showButtonAllInstallPlugins(){
            let listPlugins = this.listPlugins.filter( (plugin) => { 
                return ( (plugin.status !== 'install' && plugin.slug !== 'menu-icons') || (!plugin.isActive && plugin.slug !== 'menu-icons') ); 
            });

            if( listPlugins.length === 0 ){
                return false;
            }
            return true;
        }
    },
    created(){
        let dataApp =  JSON.parse( JSON.stringify(wpfaData) );
        this.languageApp = dataApp.translations; //dataApp: variable pasada desde php

        this.urlAdmin = ( dataApp && dataApp.config && dataApp.config.urlAdmin ) ? dataApp.config.urlAdmin : '';
        this.nonceAjaxInstallPlugin = (dataApp && dataApp.config && dataApp.config.nonceAjaxWPFA) ? dataApp.config.nonceAjaxWPFA : ''; 
        this.nonceAjaxActivateWpPlugin = (dataApp && dataApp.config && dataApp.config.nonceAjaxWPFA) ? dataApp.config.nonceAjaxWPFA : '';

        this.listPlugins = JSON.parse(JSON.stringify(this.listOfRequiredPlugins));

        this.urlBasePlugin = (dataApp && dataApp.config &&  dataApp.config.urlBasePlugin) ? dataApp.config.urlBasePlugin : '';
        this.listOfInstalledPlugins = (dataApp && dataApp.config &&  dataApp.config.listPlugins) ? dataApp.config.listPlugins : [];
    },
    methods:{
        installAllPlugins(){
            this.listOfPluginsToBeInstalledInASingleStep = this.listPlugins.filter( (plugin) => { 
                return ( (plugin.status !== 'install' && plugin.slug !== 'menu-icons') || (!plugin.isActive && plugin.slug !== 'menu-icons') ); 
            });

            this.currentPluginToInstall = 0;

            if( this.listOfPluginsToBeInstalledInASingleStep.length == 0 ){
                this.sendRequestInstallPlugins();
                return;
            }

            this.installRequiredPlugins( this.listOfPluginsToBeInstalledInASingleStep[this.currentPluginToInstall] );
        },
        installRequiredPlugins( currentPlugin ){
            if(!currentPlugin){
                this.sendRequestInstallPlugins();
                return;
            }

            let slugPlugin = currentPlugin.slug;

            if( currentPlugin.status === "not-install" ){
                currentPlugin["animation"] = true;
                this.disabledAllButtons = true;
    
                // ajax request to activate plugin
                let dataPlugin = {
                    'action': 'install_required_plugin',
                    'slugPlugin': slugPlugin,
                    'nonce': this.nonceAjaxInstallPlugin, 
                };
    
                let self = this;
    
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: dataPlugin, 
                    success: function (response) {
                        let stepCompleted = self.actionToBeTakenAfterRequestResponse(response, currentPlugin, 'install-and-activate');

                        if(stepCompleted){
                            self.currentPluginToInstall++;
                            self.installRequiredPlugins( self.listOfPluginsToBeInstalledInASingleStep[self.currentPluginToInstall] );
                        }
                    }
                });
            }
            else  if( !currentPlugin.isActive ){
                currentPlugin["animation"] = true;
                this.disabledAllButtons = true;

                // ajax request to activate plugin
                let dataPlugin = {
                    'action': 'activate_plugin_from_ajax',
                    'slugPlugin': slugPlugin,
                    'nonce': this.nonceAjaxActivateWpPlugin, 
                };

                let self = this;

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: dataPlugin, 
                    success: function (response) {
                        let stepCompleted = self.actionToBeTakenAfterRequestResponse(response, currentPlugin, 'activate');

                        if(stepCompleted){
                            self.currentPluginToInstall++;
                            self.installRequiredPlugins( self.listOfPluginsToBeInstalledInASingleStep[self.currentPluginToInstall] );
                        }
                    }
                });
            }
        },
        activateWpPlugin( slugPlugin, currentPlugin ){

            currentPlugin["animation"] = true;
            this.disabledAllButtons = true;

            // ajax request to activate plugin
            let dataPlugin = {
                'action': 'activate_plugin_from_ajax',
                'slugPlugin': slugPlugin,
                'nonce': this.nonceAjaxActivateWpPlugin, 
            };

            let self = this;

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: dataPlugin, 
                success: function (response) {
                    self.actionToBeTakenAfterRequestResponse(response, currentPlugin, 'activate');
                }
            });
        },
        installPlugin( slugPlugin, currentPlugin ){

            currentPlugin["animation"] = true;
            this.disabledAllButtons = true;

            // ajax request to install plugin
            let dataPlugin = {
                'action': 'install_required_plugin',
                'slugPlugin': slugPlugin,
                'nonce': this.nonceAjaxInstallPlugin, 
            };

            let self = this;

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: dataPlugin, 
                success: function (response) {
                    self.actionToBeTakenAfterRequestResponse(response, currentPlugin, 'install-and-activate')
                }
            });

        },
        actionToBeTakenAfterRequestResponse( response, currentPlugin, action="" ){

            if( !response.success ){

                if( response.data && response.data.errorMessage ){
                    //Show error message
                    Swal.fire({
                        title: this.languageApp.error,
                        text: response.data.errorMessage,
                        icon: 'error',
                        confirmButtonText: this.languageApp.close
                    });

                    currentPlugin["animation"] = false;
                    this.disabledAllButtons = false;
                }
                return true;
            }

            let slugPlugin = (response.data && response.data.slug) ? response.data.slug : "";
            
            if( slugPlugin && action === "install-and-activate" ){
                currentPlugin["status"] = "install";
                currentPlugin["isActive"] = true;
                this.$emit("udpate-data-plugin", currentPlugin);
            }
            else if( slugPlugin && action === "activate" ){
                currentPlugin["isActive"] = true;
                this.$emit("udpate-data-plugin", currentPlugin);
            }

            currentPlugin["animation"] = false;
            this.disabledAllButtons = false;
            return true;
        },
        sendRequestInstallPlugins(){
            //Verify that all plugins are installed and active
            let allPluginsDeactivatedOrNotInstalled =  this.listPlugins.filter( (plugin) => plugin.status === "not-install" || !plugin.isActive );

            if( allPluginsDeactivatedOrNotInstalled.length >= 2 ){
                //If a plugin is not active send to plugins page to activate it
                this.showErrorWithRequiredPluginInstallation();
                this.$emit("step-error");
                return;
            }
            else if( allPluginsDeactivatedOrNotInstalled.length == 1 ){
                //Verify if plugin not installed is an optional plugin
                let pluginOptional = false;

                allPluginsDeactivatedOrNotInstalled.forEach( plugin => {
                    if( plugin.slug === "wp-menu-icons" 
                        ||  plugin.slug === "menu-icons" ){
                        pluginOptional = true;
                    }
                });

                if( !pluginOptional ){
                    this.showErrorWithRequiredPluginInstallation();
                    this.$emit("step-error");
                    return;
                }
            }
            this.$emit("step-successfully-generated");
        },
        showErrorWithRequiredPluginInstallation(){
            Swal.fire({
                title: this.languageApp.error,
                text: this.languageApp.pleaseInstallAndActivateTheRequiredPluginsToContinue,
                icon: 'error',
                confirmButtonText: this.languageApp.close
            });
        }
    }
});