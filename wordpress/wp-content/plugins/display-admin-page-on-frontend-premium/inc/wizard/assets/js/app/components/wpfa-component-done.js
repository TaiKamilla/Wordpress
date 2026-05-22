Vue.component('component-done', {
    template: "#wpfa-component-done" ,
    data(){
        return{
            languageApp: {}
        }
    },
    props: ['completedStepInWizard'],
    created(){
        let dataApp =  JSON.parse( JSON.stringify(wpfaData) );
        this.languageApp = dataApp.translations;
    },
});