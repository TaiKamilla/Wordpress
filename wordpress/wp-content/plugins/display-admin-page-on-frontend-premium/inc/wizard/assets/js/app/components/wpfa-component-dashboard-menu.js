Vue.component('component-dashboard-menu', {
    template: "#wpfa-component-dashboard-menu" ,
    data(){
        return{
            languageApp: {},
            listOfSiteMenu: [],
            urlAdmin: "",
            showAnimationMenuItems: true,
            urlBasePlugin: "",
            isNetworkAdmin: false
        }
    },
    created(){
        let dataApp =  JSON.parse( JSON.stringify(wpfaData) );
        this.languageApp = dataApp.translations;
        this.urlAdmin = ( dataApp && dataApp.config && dataApp.config.urlAdmin ) ? dataApp.config.urlAdmin : '';
        this.urlBasePlugin = (dataApp && dataApp.config &&  dataApp.config.urlBasePlugin) ? dataApp.config.urlBasePlugin : '';
        this.isNetworkAdmin = (dataApp && dataApp.config && dataApp.config.isNetworkAdmin) ? dataApp.config.isNetworkAdmin : false;

        //If multisite is active we change url to get menu data
        let localOptionSubsite = localStorage.getItem('wpfa-dashboard-site');

        if( localOptionSubsite && this.isNetworkAdmin ){
            localOptionSubsite = JSON.parse(localOptionSubsite);
            this.urlAdmin = localOptionSubsite.urlAdmin;
        }

        //Get the html administrative panel
        $ = jQuery;
        let self = this;
        
        $.ajax({
            "url": this.urlAdmin, 
            "type": 'GET',
            success: function (response) {
                var doc = document.documentElement.cloneNode();
                doc.innerHTML = response;
                let $content = $(doc.querySelectorAll('#adminmenuwrap #adminmenu > li'))

                $content.each( function ( index ) {
   
                    if( $(this).hasClass("wp-has-submenu") ){
                        let textButtonParentMenu =  $( this ).find('.wp-menu-name').clone().children().remove().end().text();
                        let hrefButtonParentMenu = $( this ).find('> a').attr('href');

                        let $childsElement = $(this).find('ul.wp-submenu');

                        //Extracting menu icon
                        let $menuIcon = $(this).find(".wp-menu-image");
                        let listClassIcon = ($menuIcon) ? $menuIcon.attr('class').split(/\s+/) : [];
                        let dashiconName = "dashicons-admin-generic";
                        
                        if( listClassIcon ) {
                            listClassIcon.forEach( (classIcon)=> {
                                if( classIcon != "dashicons-before" && classIcon != "wp-menu-image" 
                                    && classIcon != "dashicons-after" && classIcon.includes("dashicons-") ){
                                    dashiconName = classIcon;
                                }
                            });
                        }

                        //Extract child items from each parent menu
                        if( $childsElement.length > 0 ){
                            let childsSubmenu = [];
                            let $childsElementLi = $childsElement.children();

                            $childsElementLi.each( function ( indexChild ) {
                                
                                if( indexChild == 0 || indexChild == 1 ){
                                    return true;
                                }  

                                let $linkSubmenu = $(this).find('a');

                                if( $linkSubmenu ){  
                                    let textButtonSubmenu = $linkSubmenu.clone().children().remove().end().text();
                                    let hrefButtonSubmenu = $linkSubmenu.attr('href');

                                    if( textButtonSubmenu && hrefButtonSubmenu ){
                                        childsSubmenu.push({
                                            id: self.textToCamelCase(textButtonSubmenu, indexChild, textButtonParentMenu),
                                            menuName: (textButtonSubmenu).trim(),
                                            parent: false,
                                            checkMenu: false,
                                            menuLink: hrefButtonSubmenu, 
                                            menuPosition: indexChild + 1,
                                            iconWp: dashiconName
                                        });
                                    }
                                }
                            });

                            self.listOfSiteMenu.push({
                                id: self.textToCamelCase(textButtonParentMenu, index),
                                menuName: (textButtonParentMenu).trim(),
                                parent: true,
                                toggleMenu: true,
                                menuLink: hrefButtonParentMenu,
                                checkMenu: false,
                                childs: childsSubmenu,
                                iconWp: dashiconName,
                                menuPosition: index + 1,
                            });
                        }
                        else{
                            self.listOfSiteMenu.push({
                                id: self.textToCamelCase(textButtonParentMenu, index),
                                menuName: (textButtonParentMenu).trim(),
                                parent: true,
                                toggleMenu: true,
                                menuLink: hrefButtonParentMenu,
                                checkMenu: false,
                                childs: [],
                                iconWp: dashiconName,
                                menuPosition: index + 1,
                            });
                        }

                        self.showAnimationMenuItems = false;
                    }
                });
                
            }
        });
    },
    methods:{
        selectAllChildsMenu(event, itemMenu){
            event.preventDefault();
            event.stopPropagation();

            itemMenu.checkMenu = true;

            if( itemMenu && Array.isArray(itemMenu.childs) 
                && itemMenu.childs.length > 0 ){
                let itemsChilds = itemMenu.childs || [];

                itemsChilds.forEach( childItem => {
                    childItem["checkMenu"] = true;
                });
            }
        },
        unselectAllChildsMenu(event, itemMenu){
            event.preventDefault();
            event.stopPropagation();

            if( itemMenu.checkMenu && itemMenu && Array.isArray(itemMenu.childs) 
                && itemMenu.childs.length > 0 ){
                let itemsChilds = itemMenu.childs || [];

                itemsChilds.forEach( childItem => {
                    childItem["checkMenu"] = false;
                });
            }
        },
        changeInMenuParentItem( value, itemMenu ){

            if( value && itemMenu && Array.isArray(itemMenu.childs) 
                && itemMenu.childs.length > 0 ){
                let itemsChilds = itemMenu.childs || [];

                itemsChilds.forEach( childItem => {
                    childItem["checkMenu"] = true;
                });
            }
            else if( !value && itemMenu && Array.isArray(itemMenu.childs) 
                && itemMenu.childs.length > 0 ){
                let itemsChilds = itemMenu.childs || [];

                itemsChilds.forEach( childItem => {
                    childItem["checkMenu"] = false;
                });
            }
        },
        textToCamelCase( text, index, textParentMenu="" ) {
            text = text + '-' + index;

            if( textParentMenu ){
                text = text + '-' + index + textParentMenu;
            }

            var hash = 0,
                i, chr;
            if (text.length === 0) return hash;
            for (i = 0; i < text.length; i++) {
                chr = text.charCodeAt(i);
                hash = ((hash << 5) - hash) + chr;
                hash |= 0; // Convert to 32bit integer
            }
            return hash;
        },
        changeViewOfChildItems( itemMenu ){
            itemMenu.toggleMenu = !itemMenu.toggleMenu;
        },
        savePagesToBeCreated(){
            let copyListOfSiteMenu = JSON.parse( JSON.stringify(this.listOfSiteMenu) );
            copyListOfSiteMenu.forEach( itemParentMenu => {
                let childsMenu = itemParentMenu.childs || [];

                childsMenu.forEach( itemChildMenu => {
                    if( itemChildMenu.checkMenu ){
                        itemParentMenu.checkMenu = true;
                    }
                });

                //Filter and send child items in menu if they are marked in front end
                let filterChilds = childsMenu.filter( (itemMenu) => itemMenu.checkMenu );
                itemParentMenu.childs = filterChilds;
            });

            let listItemMenuToCreated = copyListOfSiteMenu.filter( (itemMenu)=> itemMenu.checkMenu );

            //Send emit of pages to be created
            if(listItemMenuToCreated.length === 0){
                Swal.fire({
                    title: this.languageApp.error,
                    text: this.languageApp.pleaseSelectThePagesYouWishToAddInOrderToContinue,
                    icon: 'error',
                    confirmButtonText: this.languageApp.close
                });
                this.$emit('step-error');
                return;
            }

            this.$emit('step-successfully-generated', listItemMenuToCreated);
        },
        changeCheckboxChild( value, itemParentMenu ){
            if( value ){
                itemParentMenu.checkMenu = true;
            }
        }
    }
});