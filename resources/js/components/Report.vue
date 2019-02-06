<template>
    <div>
        <h1>Report: {{ id }}</h1>
    </div>
</template>

<script>
    export default {
        data(){
            return {
                id: {}, // Props! Sent from Reports.vue
            }
        },
        methods:{
          //
        },
        mounted() {
            //console.log('Component mounted.')
        },
        created() {
            this.id = this.$route.params.id;
            console.log(this.$route.params);
        },
    }
</script>


// @see https://www.thepolyglotdeveloper.com/2017/11/pass-data-between-routes-vuejs-web-application/
