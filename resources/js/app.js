require('./bootstrap');

import Vue from 'vue';

Vue.component('emails', require('./components/EmailsComponent').default);
Vue.component('search-email', require('./components/SearchEmailComponent').default);
Vue.component('email-form', require('./components/EmailFormComponent').default);

const app = new Vue({
    el: '#app'
});
