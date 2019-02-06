<template>
    <div>
        <h1>Reports Reports</h1>
        <br>
        report_1
        <br>
        report_2
        <br>
        <td><router-link :to="{ name: 'report', params: { id: 12345 } }">router link</router-link></td>
        <br>
    </div>

</template>


<script>
    export default {
        mounted() {
            //.log($route);
        }
    }
</script>
