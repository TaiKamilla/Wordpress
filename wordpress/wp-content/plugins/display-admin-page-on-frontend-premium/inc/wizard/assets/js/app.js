(function () {
  let dataApp = JSON.parse( JSON.stringify(wpfaData) );
  
  Vue.component('vue-multiselect', window.VueMultiselect.default)

  new Vue({
    el: '#wpfa-app-initial-wizard',
    data: {
      languageApp: {}, 
      menuForInitialConfiguration: [],
      currentStepWizard: 'welcome',
      currentStepNumber: 1,
      listStepsConfiguration: [
        {
          text: dataApp.translations.welcome,
          id: 'welcome',
        },
        {
          text: dataApp.translations.requiredPlugins,
          id: 'required-plugins',
        },
        {
          text: dataApp.translations.dashboardSite,
          id: 'dashboard-site',
        },
        {
          text: dataApp.translations.dashboardDesign,
          id: 'dashboard-design',
        },
        {
          text: dataApp.translations.dashboardPages,
          id: 'dashboard-menu',
        },
        {
          text: dataApp.translations.import,
          id: 'done',
        }
      ],
      isMultisite: false,
      disabledButtons: false,
      nonceAjaxActions: '',
      pagesToBeCreatedForEachRequest: 5,
      requestCountParentPages: 0,
      totalRequestParentPages: 0,
      listOfPagesToCreate: [],
      listParentItemsMenuBackend: [],
      listOfChildPagesToCreate: [],
      listOfPagesCutToMakeRequestParent: [],
      requestCountChildsPages: 0,
      totalRequestChildsPages: 0,
      listOfPagesCutToMakeRequestChilds: [],
      idMenuWpfa: 0,
      idTemplateElementorWpfa: 0,
      totalProgressBarStepIncreaser: 0,
      progressBarStepIncreaser: 0,
      urlAdmin: "",
      idMultisite: "",
      idTemplateElementor: "",
      listOfRequiredPlugins: [],
      urlBasePlugin: "",
      currentMenuInBackend: [],
      currentMenuLinks: [],
      wpfaAppStarted: false,
      listOfElementorTemplates: [],
      isNetworkAdmin: false,
      language: ""
    },
    computed:{
      wizardCompleted(){
        if( this.progressBarStepIncreaser >= 100 && this.currentStepWizard === 'done'){
          return true;
        }
        return false;
      }
    },
    created(){
      //Improving ui to display correctly when loading app
      var element = document.getElementById("wpfa-app-initial-wizard");
      element.classList.remove("vue-app-wpfa");

      this.languageApp = dataApp.translations; //dataApp: variable pasada desde php
      this.isMultisite = dataApp.config.isMultisite;
      this.isNetworkAdmin = dataApp.config.isNetworkAdmin;
      this.urlAdmin = ( dataApp && dataApp.config && dataApp.config.urlAdmin ) ? dataApp.config.urlAdmin : '';
      this.urlBasePlugin = (dataApp && dataApp.config &&  dataApp.config.urlBasePlugin) ? dataApp.config.urlBasePlugin : '';
      this.nonceAjaxActions = (dataApp && dataApp.config && dataApp.config.nonceAjaxWPFA) ? dataApp.config.nonceAjaxWPFA : '';
      this.language = (dataApp && dataApp.config && dataApp.config.language) ? dataApp.config.language : 'en_';

      //Make request for get list plugins and get list template elementor wpfa
      let self = this;

      let dataGetLisPlugins = {
        'action': 'get_list_of_required_plugins',
        'nonce':  this.nonceAjaxActions, 
      };

      jQuery.ajax({
          url: ajaxurl,
          type: 'POST',
          data: dataGetLisPlugins, 
          success: function (response) {
            
            if( !response.success || !response.data ){
              self.showErrorMessageInRequest(response);
              return;
            }

            let listPluginsRequired = response.data || [];

            listPluginsRequired.forEach( plugin => {

              if( self.language.startsWith("en_") && plugin.descriptionEn ){
                plugin["description"] = plugin.descriptionEn;
              }
              else if( self.language.startsWith("es_") &&  plugin.descriptionEs ){
                plugin["description"] = plugin.descriptionEs;
              }
              else{
                plugin["description"] = plugin.descriptionEn;  
              }
            });

            //List of required plugins
            self.listOfRequiredPlugins = listPluginsRequired;
            self.getTemplatesElementorWpfa();
          }
      });
    },
    methods:{
      getTemplatesElementorWpfa(){
        let self = this;
        
        let dataGetLisPlugins = {
          'action': 'get_list_of_elementor_templates',
          'nonce': this.nonceAjaxActions, 
        };
  
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: dataGetLisPlugins, 
            success: function (response) {
              
              if( !response.success || !response.data ){
                self.showErrorMessageInRequest(response);
                return;
              }
  
              self.listOfElementorTemplates = response.data || [];
              self.startWpfaApp();
            }
        });
      },
      startWpfaApp(){
        this.wpfaAppStarted = true;
        let welcomeCompleted = localStorage.getItem("wpfa-welcome-completed") || null;

        //Flag to know which site we are going to configure
        let localOptionSubsite = localStorage.getItem("wpfa-dashboard-site");

        if( localOptionSubsite 
          && this.isNetworkAdmin ){
          localOptionSubsite = JSON.parse(localOptionSubsite);
          this.idMultisite = localOptionSubsite.id || '';
          this.urlAdmin = localOptionSubsite.urlAdmin || '';
        }

        //Verifying that all plugins are installed
        let listOfInstalledPluginsWp = (dataApp && dataApp.config &&  dataApp.config.listPlugins) ? dataApp.config.listPlugins : [];

        listOfInstalledPluginsWp.forEach( (plugin)=> {
            let findPlugin = this.listOfRequiredPlugins.find( (pluginRequired) => pluginRequired.name === plugin.name );

            if( findPlugin ){
              findPlugin.isActive = plugin.isActive;
            }
        });

        //Verifying that the plugins have been installed
        this.listOfRequiredPlugins.forEach( pluginRequired => {
            let findPlugin = listOfInstalledPluginsWp.find( (pluginInSite) => {
                let slugPlugin = (pluginInSite.slug).split("/")[0];
                return slugPlugin === pluginRequired.slug;
            });

            if( findPlugin ){
                pluginRequired.status = 'install';
            }
        });

        let pluginsNotInstalled = this.listOfRequiredPlugins.filter( (plugin) => !plugin.isActive ||  plugin.status !== "install");

        //Checking which page to display if you have already seen the startup steps
        if( welcomeCompleted && pluginsNotInstalled.length === 0 ){
          this.pageToShowIfWelcomeHasAlreadyBeenViewed();
        }
        else if( welcomeCompleted && pluginsNotInstalled.length == 1 ){
          let pluginOptional = false;

          pluginsNotInstalled.forEach( plugin => {
              if( plugin.slug === "wp-menu-icons" 
                  ||  plugin.slug === "menu-icons" ){
                  pluginOptional = true;
              }
          });

          if( pluginOptional ){
            this.pageToShowIfWelcomeHasAlreadyBeenViewed();
          }
        }


        //Controlling steps to be contained in the wizard
        this.listStepsConfiguration.forEach( (itemStep, index) => {

            if( itemStep.id === 'welcome' ){
              this.menuForInitialConfiguration.push({
                text: itemStep.text,
                id: itemStep.id,
                stepNumber: index + 1,
                status: 'in-process'
              });
              return;
            }

            //If we are not in a multisite site we will eliminate 1 step of the settings
            if( itemStep.id === 'dashboard-site' 
              && !this.isMultisite ){
                return;
            }
            
            this.menuForInitialConfiguration.push({
              text: itemStep.text,
              id: itemStep.id,
              stepNumber: index + 1,
              status: 'not-complete'
            });

        });

        if( !this.isMultisite ){
          localStorage.removeItem('wpfa-dashboard-site');
        }


        //Determining the view to display from php
        if( dataApp && dataApp.config && dataApp.config.currentViewPlugin 
          && dataApp.config.currentViewPlugin === "dashboard-menu" ){

          currentStep = this.listStepsConfiguration.find( (itemConfig) => itemConfig.id == "dashboard-menu" );
          findIndexStep = this.listStepsConfiguration.findIndex( (itemConfig) => itemConfig.id == "dashboard-menu" );

          this.currentStepWizard = currentStep.id;
          this.currentStepNumber =  findIndexStep + 1;

          let currentUrl = window.location.href;
          let listOfParametersInUrl = currentUrl.split("&");

          listOfParametersInUrl.forEach(params => {
              if( params.includes("template") ){
                let nameTemplate = params.split("=")[1];

                if(nameTemplate){
                  this.idTemplateElementor = nameTemplate;
                }
              }
          });

        }
      },
      backToWizardStep( itemMenu ){
        let passageToWhichWeWillNavigate = itemMenu.stepNumber;
        
        if( this.currentStepNumber < passageToWhichWeWillNavigate || this.currentStepNumber === passageToWhichWeWillNavigate ){
          return;
        }
        
        //Reseteando plantilla
        if( itemMenu.id == "dashboard-design" ){
          this.idTemplateElementor = null;
        }
        
        this.currentStepNumber = passageToWhichWeWillNavigate;
        this.currentStepWizard = itemMenu.id;

        jQuery("html, body").animate({ scrollTop: 0 }, 1000);
      },
      pageToShowIfWelcomeHasAlreadyBeenViewed(){
        let currentStep = null;
        let findIndexStep = null;

        if( this.isMultisite  ){
          currentStep = this.listStepsConfiguration.find( (itemConfig) => itemConfig.id == "dashboard-site" );
          findIndexStep = this.listStepsConfiguration.findIndex( (itemConfig) => itemConfig.id == "dashboard-site" );
        }
        else{
          currentStep = this.listStepsConfiguration.find( (itemConfig) => itemConfig.id == "dashboard-design" );
          findIndexStep = this.listStepsConfiguration.findIndex( (itemConfig) => itemConfig.id == "dashboard-design" );
        }

        this.currentStepWizard = currentStep.id;
        this.currentStepNumber =  findIndexStep + 1;
      },
      udpateDataPlugin( dataPlugin ){
        let slugPluginSearch = dataPlugin.slug;

        let indexPluginDatabaseLocal = this.listOfRequiredPlugins.findIndex( (itemPlugin) => itemPlugin.slug === slugPluginSearch );

        if( indexPluginDatabaseLocal >= 0 ){
          this.listOfRequiredPlugins[indexPluginDatabaseLocal]['status'] = dataPlugin.status;
          this.listOfRequiredPlugins[indexPluginDatabaseLocal]['isActive'] = dataPlugin.isActive;
        }
      },
      stepSuccessfullyDashboardSite( dataSite ){
        if(!dataSite) return;

        this.idMultisite = dataSite.id;
        this.urlAdmin = dataSite.urlAdmin;
        this.nextViewInWizard();
      },
      goToAllPages(){
        let linkPages = jQuery("#menu-pages > a").attr("href");
        let pathAdminPages = this.urlAdmin + linkPages;

        window.location.href = pathAdminPages;
      },
      goToDashboard(){
        window.location.href = this.urlAdmin;
      },
      animationProgressBar(){
          setTimeout(()=>{
            this.progressBarStepIncreaser = this.progressBarStepIncreaser + this.totalProgressBarStepIncreaser;
  
            if( Math.round(this.progressBarStepIncreaser)  >= 100 ){
              this.progressBarStepIncreaser = 100;
  
              if( this.currentStepWizard == "done" ){
                this.currentStepNumber = this.currentStepNumber + 2;
              }
            }
  
            $('head').append(`<style>
              .container-wpfa-main-app .wpfa-body-steps .wpfa-container-component-done .wpfa-done-progress-bar .wpfa-container-progress-bar .wpfa-container-progress-bar-div::before {
                width: ${this.progressBarStepIncreaser}%;
              }
            </style>`);
          },1000);
      },
      nextViewInWizard(){
        let indexCurrentStep = this.menuForInitialConfiguration.findIndex( (itemMenu)=> itemMenu.id === this.currentStepWizard );

        if( indexCurrentStep >= 0 
          && indexCurrentStep < this.menuForInitialConfiguration.length - 1 ){
          let nextIndexStep = indexCurrentStep + 1;
          this.currentStepWizard = this.menuForInitialConfiguration[nextIndexStep].id;
          this.currentStepNumber =  this.menuForInitialConfiguration[nextIndexStep].stepNumber;

          if( nextIndexStep >= 2 ){
            localStorage.setItem("wpfa-welcome-completed", true);
          }

          jQuery("html, body").animate({ scrollTop: 0 }, 1000);
        }

        if(this.disabledButtons){
          this.disabledButtons = false;
        }
      },
      stepSuccessfullyGeneratedDashboardDesign(idTemplate){
        let currentUrl = window.location.href ;
        let localOptionSubsite = localStorage.getItem('wpfa-dashboard-site');
        localOptionSubsite = (localOptionSubsite) ? JSON.parse(localOptionSubsite) : null;

        //Checking if we are in the network panel
        if( this.currentStepWizard == 'dashboard-design' && dataApp.config.isMultisite 
          && dataApp.config.isNetworkAdmin ){

            if(localOptionSubsite){
              window.location.href = localOptionSubsite.urlAdmin + "admin.php?page=wpfa_wizard_initial&view=dashboard-pages&template=" + idTemplate;
              return;
            }

        }
        else if( localOptionSubsite && localOptionSubsite.urlAdmin 
          && !currentUrl.includes(localOptionSubsite.urlAdmin) ){
          window.location.href = localOptionSubsite.urlAdmin + "admin.php?page=wpfa_wizard_initial&view=dashboard-pages&template=" + idTemplate;
          return;
        }

        this.nextViewInWizard();
      },
      stepSuccessfullyGenerated(){
        this.nextViewInWizard();
      },
      /**
       * Function that is executed after selecting the pages to be created in menu
       * @constructor
       * @param {array} pageData - Array que contiene los datos de las páginas a crear  
       *  [
       *    id: <number>,
       *    menuName: <string>,
       *    parent: <boolean>,
       *    checkMenu: <boolean>,
       *    menuLink: <string>,
       *    menuPosition: <number>,
       *    childs: <array>
       * ]
      */
      successfulMenuCreationStep( pageData = null ){
        
        if( !pageData ) return;

        $('head').append(`<style>
          .container-wpfa-main-app .wpfa-body-steps .wpfa-container-component-done .wpfa-done-progress-bar .wpfa-container-progress-bar .wpfa-container-progress-bar-div::before {
            width: 0%;
          }
        </style>`);

        this.listOfPagesToCreate = [];
        this.listOfPagesCutToMakeRequestParent = [];
        this.listOfPagesCutToMakeRequestChilds = [];
        this.listParentItemsMenuBackend = [];
        this.listOfChildPagesToCreate = [];

        this.nextViewInWizard();
        this.listOfPagesToCreate = JSON.parse(JSON.stringify(pageData));

        //Defining how many steps will be to display animation in progress bar
        this.totalProgressBarStepIncreaser = 100 / 4;
        this.progressBarStepIncreaser = 0;

        if( !this.idTemplateElementor ){
          this.idTemplateElementor = localStorage.getItem("wpfa-select-template-elementor");
        }

        //ajax request to import template
        let dataImportTemplate = {
          'action': 'import_template_elementor',
          'template_elementor': this.idTemplateElementor,
          'nonce': this.nonceAjaxActions, 
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
                self.idTemplateElementorWpfa = response.data.id;
                self.createMenuForWpfaApp();
              }

              self.animationProgressBar();
            }
        });

      },
      createMenuForWpfaApp(){
        
        let dataCreateMenu = {
          'action': 'create_custom_menu_for_wpfa',
          'nonce': this.nonceAjaxActions, 
        };
        
        let self = this;

        //ajax request to create menu
        jQuery.ajax({
          url: ajaxurl,
          type: 'POST',
          data: dataCreateMenu, 
          success: function (response) {

            if( !response.success ){
              self.showErrorMessageInRequest(response);
              return;
            }

            if( response.success && response.data 
              && response.data.idMenu ){
                self.idMenuWpfa = response.data.idMenu;
                self.verifyWhichMenuItemsToCreate();

                //animacion de progress bar
                self.animationProgressBar();
            }
          }
        });
      },
      hideMenuItemLabel( nameTemplate ){
        let listOfTemplatesInHideLabel = ['elementor-08', 'es-elementor-08'];

        if( listOfTemplatesInHideLabel.includes(nameTemplate) ){
          return 'yes'
        }

        return 'no';
      },
      checkIfWeHaveToUseCustomizedItems(nameTemplate){
        let listTemplates = ["elementor-01", "elementor-04", "elementor-05", "elementor-06", "elementor-07", "elementor-08", "elementor-16", "elementor-17",
          "es-elementor-01", "es-elementor-04", "es-elementor-05", "es-elementor-06", "es-elementor-07", "es-elementor-08", "es-elementor-16", "es-elementor-17"];

        if( listTemplates.includes(nameTemplate) ){
          return true;
        }

        return false;
      }, 
      verifyWhichMenuItemsToCreate(){

        let createCustomItemsInMenu = this.checkIfWeHaveToUseCustomizedItems(this.idTemplateElementor);
        
        //ajax request to get menu items
        let dataGetMenuItems = {
          'action': 'get_menu_items',
          'nonce': this.nonceAjaxActions, 
        };
        
        let self = this;

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: dataGetMenuItems, 
            success: function (response) {
              
              if( !response.success ){
                self.showErrorMessageInRequest(response);
                return;
              }   

              self.currentMenuInBackend = response.data || [];
              self.currentMenuLinks = self.currentMenuInBackend.map( itemMenu =>  itemMenu.url );

              let listItemsMenu = response.data || [];

              if( createCustomItemsInMenu ){
                let findMySite = listItemsMenu.find( (itemMenu) => {
                  let urlMySite = itemMenu.url || null;
                  if( urlMySite === '[wp_frontend_admin_my_site_url]' ){
                    return true;
                  }
                  return false;
                });
  
  
                if( !findMySite ){
                  //My site
                  self.listOfPagesToCreate.push({
                    "menuName": self.languageApp.mySite,
                    "iconWp": "dashicons-visibility",
                    "menuLink": "[wp_frontend_admin_my_site_url]",
                    "childs": [],
                    "typeLink": "custom",
                    "makeRequest": true,
                    "menuPosition": 99
                  });
                }
  
  
                let findLogOut = listItemsMenu.find( (itemMenu) => {
                  let urlLogOut = itemMenu.url || null;
                  if( urlLogOut === '[vg_display_logout_url]' ){
                    return true;
                  }
                  return false;
                });
  
                if( !findLogOut ){
                  //My site
                  self.listOfPagesToCreate.push({
                    "menuName": self.languageApp.logOut,
                    "iconWp": "dashicons-exit",
                    "menuLink": "[vg_display_logout_url]",
                    "childs": [],
                    "typeLink": "custom",
                    "makeRequest": true,
                    "menuPosition": 99
                  });
                }
              }

              self.createParentPages(true);

            }
        });
      },
      createLinkWPMenu(nameMenu){
        if( !nameMenu ){
          return;
        }

        nameMenu = nameMenu.toLowerCase();

        let nameWp = nameMenu
          .replace(/([a-z])([A-Z])/g, '$1-$2')    // get all lowercase letters that are near to uppercase ones
          .replace(/[\s_]+/g, '-')                // replace all spaces and low dash
          .replace(/[ñ]+/g, 'n')                  // replace all ñ
          .toLowerCase();                         // convert to lower case  
        nameWp = nameWp.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        return nameWp;
      },
      createParentPages( firstRequest = false ){

        if( firstRequest ){

          let listPage = JSON.parse(JSON.stringify(this.listOfPagesToCreate));

          //Filtering pages that already exist
          listPage.forEach( (page, index)=> {

            let slugWpMenu = this.createLinkWPMenu(page.menuName);
            let urlBase = this.urlAdmin.replace(/\/wp\-admin/, '');
            let linkToCreate = urlBase + slugWpMenu + "/";

            if( this.currentMenuLinks.includes(linkToCreate) ){
              page["makeRequest"] = false;
              
              let findItemMenu = this.currentMenuInBackend.find( (itemMenu) => itemMenu.url === linkToCreate );
              this.listOfPagesToCreate[index]["idBackend"] = findItemMenu.object_id;

              this.listParentItemsMenuBackend.push({
                "namePage": page.menuName,
                "idBackend": findItemMenu.object_id,
                "idMenuParent": findItemMenu.ID,
              });
            }
            else{
              page["makeRequest"] = true;
            }
          });


          //Filtering pages that already exist
          listPage = listPage.filter( (page)=> {

            let namePage = page.menuName;
            
            if( namePage == "Donations" ){
              namePage = "Donation history";
            }
            if( namePage == "Donaciones" ){
              namePage = "Historial de donaciones";
            }

            let slugWpMenu = this.createLinkWPMenu(namePage);
            let urlBase = this.urlAdmin.replace(/\/wp\-admin/, '');
            let linkToCreate = urlBase + slugWpMenu + "/";

            if( !this.currentMenuLinks.includes(linkToCreate) ){
              return true;
            }
          })

          let hideLabel = this.hideMenuItemLabel(this.idTemplateElementor);

          listPage = listPage.map( (currentPage)=> {
            return{
              "menuName" : currentPage.menuName,
              "iconWp": currentPage.iconWp,
              "linkWp": currentPage.menuLink, 
              "hideLabel": hideLabel,
              "typeLink": currentPage.typeLink || "normal",
              "menuPosition": currentPage.menuPosition
            }
          });
          
          this.listOfPagesCutToMakeRequestParent = this.chunkArray(listPage, this.pagesToBeCreatedForEachRequest);

          this.requestCountParentPages = 0;
          this.totalRequestParentPages = this.listOfPagesCutToMakeRequestParent.length;
        }

        if( this.requestCountParentPages < this.totalRequestParentPages ){
          
          let self = this;

          let dataCreatePages = {
            'action': 'create_pages',
            'nonce': this.nonceAjaxActions,
            'type_page': 'parent', 
            'menu_id': this.idMenuWpfa,
            'id_template_elementor': this.idTemplateElementorWpfa,
            'data': JSON.stringify(this.listOfPagesCutToMakeRequestParent[this.requestCountParentPages]), 
          };

          //ajax request to create parent pages
          jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: dataCreatePages, 
            success: function (response) {

              if( !response.success ){
                self.showErrorMessageInRequest(response);
                return;
              }            
              
              if( response.success && response.data 
                && Array.isArray(response.data) ){
                self.listParentItemsMenuBackend = self.listParentItemsMenuBackend.concat(response.data);
              }

              self.requestCountParentPages++;
              self.createParentPages();

              //animacion de progress bar
              self.animationProgressBar();
            }
          });

          return;
        }
        else if( firstRequest ){
          //animacion de progress bar
          this.animationProgressBar();
        }

        this.createChildPages();
      },
      createChildPages(){

        this.listOfPagesToCreate.forEach( (page)=> {
          let pageParent = this.listParentItemsMenuBackend.find( (pageItem)=> pageItem.namePage === page.menuName );
          
          if( pageParent ){
            let childsMenu = page.childs || [];

            childsMenu.forEach(pageChild => {
                let itemChildPage = {
                  "idPageParent": pageParent.idBackend,
                  "idMenuParent": pageParent.idMenuParent,
                  "menuName":  pageChild.menuName,
                  "menuPosition": pageChild.menuPosition,
                  "linkWp": pageChild.menuLink
                };
            
                this.listOfChildPagesToCreate.push(itemChildPage);
            });
          }
        });


        this.makeRequestToCreateChildPages( true );
      },
      makeRequestToCreateChildPages( firstRequest = false ){

        if( firstRequest ){

          let listChildsPage = JSON.parse(JSON.stringify(this.listOfChildPagesToCreate));
          //Filtering pages that already exist
          listChildsPage = listChildsPage.filter( (page)=> {

            let parentItemMenu = this.listOfPagesToCreate.find( (pageParent) => {
              if(!pageParent.idBackend){
                return false;
              }
              return pageParent.idBackend === page.idPageParent 
            }); 

            if(!parentItemMenu){
              return true;
            }


            let slugWpMenuParent = this.createLinkWPMenu(parentItemMenu.menuName);
            let slugWpMenu = this.createLinkWPMenu(page.menuName);
            let urlBase = this.urlAdmin.replace(/\/wp\-admin/, '');
            let linkToCreate = urlBase + slugWpMenuParent + "/" + slugWpMenu + "/";

            if( !this.currentMenuLinks.includes(linkToCreate) ){
              return true;
            }
          })

          this.listOfPagesCutToMakeRequestChilds = this.chunkArray(listChildsPage, this.pagesToBeCreatedForEachRequest);
          
          this.totalRequestChildsPages = this.listOfPagesCutToMakeRequestChilds.length || 0; 
          this.requestCountChildsPages = 0;
        }

        if( this.requestCountChildsPages < this.totalRequestChildsPages ){
          let self = this;

          //ajax request to create childs pages
          let dataCreatePages = {
            'action': 'create_pages',
            'nonce': this.nonceAjaxActions,
            'type_page': 'child', 
            'menu_id': this.idMenuWpfa,
            'id_template_elementor': this.idTemplateElementorWpfa,
            'data': JSON.stringify(this.listOfPagesCutToMakeRequestChilds[this.requestCountChildsPages]), 
          };

          jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: dataCreatePages, 
            success: function (response) {

              if( !response.success ){
                self.showErrorMessageInRequest(response);
                return;
              }

              self.requestCountChildsPages++;
              self.makeRequestToCreateChildPages();

              //progress bar animation
              self.animationProgressBar();
            }
          });
        }
        else if(firstRequest){
          //progress bar animation
          this.animationProgressBar();
        }

      },
      chunkArray(myArray, chunk_size){
        var index = 0;
        var arrayLength = myArray.length;
        var tempArray = [];
        
        for (index = 0; index < arrayLength; index += chunk_size) {
            myChunk = myArray.slice(index, index+chunk_size);
            // Do something if you want with the group
            tempArray.push(myChunk);
        }
    
        return tempArray;
      },
      nextViewWizard(){
  
        if( this.currentStepWizard == "required-plugins" ){
          this.disabledButtons = true;
          this.$refs.componenteRequiredPlugins.sendRequestInstallPlugins();
          return;
        }
        else if( this.currentStepWizard == "dashboard-design" ){
          this.disabledButtons = true;
          this.$refs.componenteDashboardDesign.saveTemplateToUse();
          return;
        }
        else if( this.currentStepWizard == "dashboard-site" ){
          this.disabledButtons = true;
          this.$refs.componenteDashboardSite.saveSubsite();
          return;
        }
        else if( this.currentStepWizard == "dashboard-menu" ){
          this.disabledButtons = true;
          this.$refs.componenteDashboardMenu.savePagesToBeCreated();
          return;
        }

        this.nextViewInWizard();

      },
      stepError(){
        this.disabledButtons = false;
      },
      backViewWizard(){
        let indexCurrentStep = this.menuForInitialConfiguration.findIndex( (itemMenu)=> itemMenu.id === this.currentStepWizard );

        if( indexCurrentStep >= 1 ){
          let nextIndexStep = indexCurrentStep - 1;
          this.currentStepWizard = this.menuForInitialConfiguration[nextIndexStep].id;
          this.currentStepNumber =  this.menuForInitialConfiguration[nextIndexStep].stepNumber;

          jQuery("html, body").animate({ scrollTop: 0 }, 1000);
        }
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
      }
    }
  });


})();
