Vue.component('component-dashboard-design', {
    template: "#wpfa-component-dashboard-design" ,
    data(){
        return{
            languageApp: {}, 
            templatesDesign: [],
            urlBasePlugin: "",
            totalsTemplate: 18,
            selectTemplate: "",
            nonceAjaxImportTemplateElementor: '',
            imgSrcLightbox: '',
            showLightbox: false
        }
    },
    props:['templatesElementor'],
    created(){
        let dataApp =  JSON.parse( JSON.stringify(wpfaData) );
        this.languageApp = dataApp.translations; 

        this.urlBasePlugin = (dataApp && dataApp.config &&  dataApp.config.urlBasePlugin) ? dataApp.config.urlBasePlugin : '';
        this.nonceAjaxImportTemplateElementor = (dataApp && dataApp.dataApp && dataApp.dataApp.nonceAjaxWPFA) ? dataApp.dataApp.nonceAjaxWPFA : '';

        let selectTemplateDatabase = localStorage.getItem('wpfa-select-template-elementor') || "";
        this.selectTemplate = selectTemplateDatabase;

        //Adding template data dynamically
        this.templatesElementor.forEach( (template, index) => {
            let idTemplate = index + 1;

            if( idTemplate < 10){
                idTemplate = idTemplate.toString().padStart(2, "0");
            }

            let nameTemplate = (this.languageApp.template).replace(/\%s/g, index+1);

            if( idTemplate === '03' || idTemplate === 18 ){
                this.templatesDesign.push({
                    titleTemplate: nameTemplate,
                    urlImage: template.urlImage,
                    idTemplate: template.idTemplate
                });
                return;
            }

            this.templatesDesign.push({
                titleTemplate: nameTemplate,
                urlImage: template.urlImage,
                idTemplate: template.idTemplate
            }); 
        });
    },
    methods:{
        closeLightbox( $event ){
            if( $event && $event.target ){
                let elementTag = jQuery($event.target);
                if( elementTag && elementTag.hasClass( "wpfa-lightbox-templates" ) ){
                    this.notShowImageTemplateInLightbox();
                }
            }
        },
        notShowImageTemplateInLightbox(){
            this.imgSrcLightbox = "";
            this.showLightbox = false;
        },
        showImageTemplateInLightbox( template ){
            this.imgSrcLightbox = template.urlImage ;
            this.showLightbox = true;
        },  
        selectTemplateDesign( idTemplate ){
            this.selectTemplate = idTemplate;
            localStorage.setItem('wpfa-select-template-elementor', idTemplate);

            this.$emit("step-successfully-generated", this.selectTemplate);
        },
        saveTemplateToUse(){
            if(!this.selectTemplate){

                Swal.fire({
                    title: this.languageApp.error,
                    text: this.languageApp.pleaseSelectATemplateToContinue,
                    icon: 'error',
                    confirmButtonText: this.languageApp.close
                });
                this.$emit("step-error");
                return;
            }

            this.$emit("step-successfully-generated", this.selectTemplate);
        }
    }
});