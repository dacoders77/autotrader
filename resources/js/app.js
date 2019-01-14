/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');
import moment from 'moment'; // Working with date formats

// Sweet alert
// https://sweetalert2.github.io/#download
import swal from 'sweetalert2'
window.swal = swal;

const toast = swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000
});
window.toast = toast;

// Form validation
// https://github.com/cretueusebiu/vform
import { Form, HasError, AlertError } from 'vform';
window.Form = Form;
Vue.component(HasError.name, HasError)
Vue.component(AlertError.name, AlertError)

// Pagination
// https://github.com/gilbitron/laravel-vue-pagination
Vue.component('pagination', require('laravel-vue-pagination'));

// Vue router
import VueRouter from 'vue-router';
Vue.use(VueRouter)

// Progress bar. https://github.com/hilongjw/vue-progressbar
import VueProgressBar from 'vue-progressbar';
const options = {
    color: '#bffaf3',
    failedColor: '#874b4b',
    thickness: '10px',
    transition: {
        speed: '0.2s',
        opacity: '0.6s',
        termination: 300
    },
    autoRevert: true,
    location: 'top',
    inverse: false
}
Vue.use(VueProgressBar, options)


// Define routes for vue routing
// https://router.vuejs.org/guide/#javascript
let routes = [
    { path: '/signals', component: require('./components/Signals.vue') },
    { path: '/clients', component: require('./components/Clients.vue') },
    { path: '/executions', component: require('./components/Executions.vue') },
    { path: '/symbols', component: require('./components/Symbols.vue') },
    { path: '/execution', name: 'Page2', component: require('./components/Execution.vue') },

    { path: '/dashboard', component: require('./components/Dashboard.vue') },
    { path: '/profile', component: require('./components/Profile.vue') }
]

// Link vue component without vue router
//Vue.component('signals', require('./components/signals.vue'));

// Link a new instance of vue router
const router = new VueRouter({
    mode: 'history',
    routes
})

// Global function. Vue filter
// Make text in capital letters
// This function is accessable anywhere
Vue.filter('upText', function(text){
    return text.toUpperCase();
});

// Pretty date formatting
Vue.filter('myDate', function(created_at){
    return moment(created_at).format('MM.DD h:mm'); // MMMM Do YYYY, h:mm:ss a
});

// Global event components even listener object
window.Fire = new Vue();


// https://github.com/michaelfitzhavey/vue-json-tree-view
import TreeView from "vue-json-tree-view";
Vue.use(TreeView)





/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */
Vue.component('example-component', require('./components/ExampleComponent.vue'));
const app = new Vue({
    el: '#app',
    router
});
