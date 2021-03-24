import Vue from "vue";
import Vuex from "vuex";

Vue.use(Vuex);

export default new Vuex.Store({
    state: {
        emails: [],
        api_token: null,
        csrf_token: null,
        headers: {
            'content-type': 'application/json',
            'accept': 'application/json',
        },
        successMessage: '',
        apiErrors: [],
        pagination: {},
        currentEmail: {
            sender: null,
            subject: null,
            recipients: null,
            text_content: null,
            html_content: null,
            attachments: null,
        },
    },
    mutations: {
        SET_AUTH_TOKENS(state, tokens) {
            const { api_token, csrf_token } = tokens

            state.api_token = api_token
            state.csrf_token = csrf_token

            state.headers.Authorization = 'Bearer ' + api_token
            state.headers['X-CSRF-TOKEN'] = csrf_token
        },
        SET_EMAILS(state, emails) {
            state.emails = emails
        },
        SET_SUCCESS_MESSAGE(state, message) {
            state.successMessage = message
            state.apiErrors = []

            setTimeout(() => {
                state.successMessage = ''//so it disappears
            }, 3000)
        },
        SET_API_ERRORS(state, errors) {
            state.apiErrors = errors
            state.successMessage = ''

            setTimeout(() => {
                state.apiErrors = []
            }, 3000)
        },
        SET_PAGINATION(state, response) {
            state.pagination = {
                current_page: response.current_page,
                last_page: response.last_page,
                next_page_url: response.next_page_url,
                prev_page_url: response.prev_page_url,
                total: response.total,
            }
        },
        SET_EMAIL_PROPERTIES(state, properties) {
            const keys = Object.keys(properties)

            for (let i = 0; i < keys.length; ++i) {
                const key = keys[i]

                if (state.currentEmail.hasOwnProperty(key)) {
                    state.currentEmail[key] = properties[key]
                }
            }
        },
        EMPTY_EMAIL_FORM(state) {
            state.currentEmail.sender = ''
            state.currentEmail.subject = ''
            state.currentEmail.recipients = ''
            state.currentEmail.text_content = ''
            state.currentEmail.html_content = ''
            state.currentEmail.attachments = ''

            $('#attachments').prop('value', '')
        }
    },
    actions: {
        SEND_EMAIL({commit, getters, dispatch }, payload) {
            const headers = getters.GET_HEADERS

            axios.post('/api/emails', payload, headers)
                .then((res) => {
                    let response = res.data

                    if (response.error_code === 201) {
                        commit('SET_SUCCESS_MESSAGE', response.message)
                        commit('EMPTY_EMAIL_FORM')
                        dispatch('FETCH_EMAILS')
                    }
                }).catch((err) => {
                    let errors = err.response.data

                    if (errors.error_code === 422) {
                        let e = [];

                        for (let i in errors.data.errors) {
                            e.push(errors.data.errors[i][0])
                        }

                        commit('SET_API_ERRORS', e)
                    }
                })
        },
        SEARCH_EMAILS({commit, getters}, link) {
            const headers = getters.GET_HEADERS

            axios.get(link, headers)
                .then((res) => {
                    commit('SET_EMAILS', res.data.data.emails)
                })
                .catch((err) => {
                    let error = err.response.data

                    if (error.message) {
                        commit('SET_API_ERRORS', [error.message])
                    }
                })
        },
        FETCH_EMAILS({commit, getters}, url) {
            let link = url || 'api/emails'

            const headers = getters.GET_HEADERS

            if (!link.includes('?')) {
                link = link + "?api_token=" + getters.GET_API_TOKEN
            } else {
                link = link + "&api_token=" + getters.GET_API_TOKEN
            }

            axios.get(link, headers)
                .then((res) => {
                    let response = res.data.data.emails

                    commit('SET_EMAILS', response.data)
                    commit('SET_PAGINATION', response)
                })
                .catch((err) => {
                    let temp = err.response.data

                    if (temp.message) {
                        commit('SET_API_ERRORS', [temp.message])
                    }
                })
        }
    },
    getters: {
        GET_EMAILS: state => state.emails,
        GET_PAGINATION: state => state.pagination,
        GET_HEADERS: state => state.headers,
        GET_SUCCESS_MESSAGE: state => state.successMessage,
        GET_ERRORS: state => state.apiErrors,
        GET_API_TOKEN: state => state.api_token,
        GET_CURRENT_EMAIL: state => state.currentEmail,
    },
    modules: {}
})
