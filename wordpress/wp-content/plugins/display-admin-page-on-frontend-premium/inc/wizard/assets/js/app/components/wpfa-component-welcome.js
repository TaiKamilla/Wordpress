Vue.component('component-welcome', {
    template: "#wpfa-component-welcome" ,
    data(){
        return{
            languageApp: {}, 
        }
    },
    created(){
        let dataApp =  JSON.parse( JSON.stringify(wpfaData) );
        this.languageApp = dataApp.translations;
    },
    methods:{

    }
});