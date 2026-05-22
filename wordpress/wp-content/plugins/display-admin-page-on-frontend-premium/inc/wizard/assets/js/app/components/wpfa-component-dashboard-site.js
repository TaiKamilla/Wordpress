Vue.component('component-dashboard-site', {
    template: "#wpfa-component-dashboard-site" ,
    data(){
        return{
            languageApp: {}, 
            selected: null,
            $_debounceTimer: null,
            isLoading: false,
            optionsSubdomains: [],
            nonceAjaxSelectSubsite: '',
            isSearchable: false,
            isNetworkAdmin: false
        }
    },
    created(){
        let dataApp =  JSON.parse( JSON.stringify(wpfaData) );
        this.languageApp = dataApp.translations;

        this.$_debounceTimer = null
        this.nonceAjaxSelectSubsite = (dataApp && dataApp.config && dataApp.config.nonceAjaxWPFA) ? dataApp.config.nonceAjaxWPFA : '';
        this.isNetworkAdmin = (dataApp && dataApp.config && dataApp.config.isNetworkAdmin) ? dataApp.config.isNetworkAdmin : false;

        //Get list of sites to determine if select normal or select search will be the field to select site
        let dataImportTemplate = {
            'action': 'search_site_multisite',
            'search': '',
            'nonce': this.nonceAjaxSelectSubsite, 
        };
        
        let self = this;

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: dataImportTemplate, 
            success: function (response) {
                
                if( !response.success ){
                    self.showErrorMessageInRequest(response);
                    return;
                }
                
                if( response.success && response.data ){
                    let listSubdomains = response.data || [];
    
                    if( Array.isArray(listSubdomains) && listSubdomains.length <= 10 ){
                        listSubdomains.forEach( subdomain => {
                            self.optionsSubdomains.push({
                                name: subdomain.name + ` (${subdomain.urlAdmin})`,
                                id: subdomain.id,
                                urlAdmin: subdomain.urlAdmin,
                            });
                        });
                    }
                    else{
                        self.isSearchable = true;
                    }
                }

            }
        });

        let localOptionSubsite = localStorage.getItem('wpfa-dashboard-site');

        if( localOptionSubsite && this.isNetworkAdmin ){
            localOptionSubsite = JSON.parse(localOptionSubsite);
            if( this.isSearchable ){
                this.optionsSubdomains = [localOptionSubsite];
            }
            this.selected = localOptionSubsite;
        }
    },
    methods:{
        debounce (method, timer) {
            if (this.$_debounceTimer !== null) {
                clearTimeout(this.$_debounceTimer)
            }
            this.$_debounceTimer = setTimeout(() => {
                method()
            }, timer)
        },
        searchSiteMultisite(query){
            this.debounce(() => {

                this.isLoading = true;
                this.optionsSubdomains = [];

                if( !query ){
                    this.isLoading = false;
                    return;
                }
                
                //ajax request to get site
                let dataImportTemplate = {
                    'action': 'search_site_multisite',
                    'search': query,
                    'nonce': this.nonceAjaxSelectSubsite, 
                };
                
                let self = this;
        
                jQuery.ajax({
                    url: ajaxurl, // this will point to admin-ajax.php
                    type: 'POST',
                    data: dataImportTemplate, 
                    success: function (response) {
                        
                        if( !response.success ){
                            self.showErrorMessageInRequest(response);
                            return;
                        }
                        
                        let listSubdomains = response.data || [];

                        if( Array.isArray(listSubdomains) && listSubdomains.length > 0 ){
                            listSubdomains.forEach( subdomain => {
                                self.optionsSubdomains.push({
                                    name: subdomain.name + ` (${subdomain.urlAdmin})`,
                                    id: subdomain.id,
                                    urlAdmin: subdomain.urlAdmin,
                                });
                            });
                        }

                        self.isLoading = false;
                    }
                });
            },1000);
        },
        saveSubsite(){
            if(!this.selected){
                Swal.fire({
                    title: this.languageApp.error,
                    text: this.languageApp.pleaseSelectTheSiteWhereTheConfigurationsWillBeApplied,
                    icon: 'error',
                    confirmButtonText: this.languageApp.close
                });
                this.$emit('step-error');
                return;
            }
            localStorage.setItem('wpfa-dashboard-site', JSON.stringify(this.selected) );
            this.$emit("step-successfully-dashboard-site", this.selected);
        },
        showErrorMessageInRequest(response){
            if( response.data && response.data.errorMessage ){
              //Show error message
              Swal.fire({
                  title: this.languageApp.error,
                  text: response.data.errorMessage,
                  icon: 'error',
                  confirmButtonText: this.languageApp.close
              });
            }
        },
    }
});