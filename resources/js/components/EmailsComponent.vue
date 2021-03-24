<template>
    <div class="mt-4" v-on:emails-sent="getEmails()">
        <h4>Emails</h4>
        <nav v-if="pagination.length" aria-label="Page navigation example">
            <ul class="pagination">
                <li :class="[{disabled: !pagination.prev_page_url}]" class="page-item">
                    <a class="page-link" @click="getEmails(pagination.prev_page_url)" href="#">
                        Previous
                    </a>
                </li>
                <li class="page-item disabled">
                    <a class="page-link text-dark" href="#">
                        {{ pagination.current_page }} of {{ pagination.last_page }}
                    </a>
                </li>
                <li :class="[{disabled: !pagination.next_page_url}]" class="page-item">
                    <a class="page-link" @click="getEmails(pagination.next_page_url)" href="#">
                        Next
                    </a>
                </li>
            </ul>
        </nav>
        <div class="card card-body mb-2" v-for="email in emails" :key="email.uuid">
            <span>From: {{ email.from }}</span>
            <span>To: {{ email.to }}</span>
            <span>Subject: {{ email.subject }}</span>
            <span>Status: {{ email.status }}</span>
            <button class="mt-2 btn btn-sm btn-primary btn-block" @click="viewEmailDetails(email.uuid)">View Details</button>
        </div>
    </div>
</template>

<script>
    module.exports = {
        props: ['api_token'],
        data: function() {
            return {
                emails: [],
                currentMail: {
                    uuid: '',
                    sender: '',
                    recipient: '',
                    subject: '',
                    attachments: null,
                    content: ''//could be html or text content, either one is fine
                },
                pagination: {}
            }
        },
        created() {
            this.getEmails();
        },
        mounted() {
            console.log("Mounted Emails")
        },
        methods: {
            getHeaders() {
                return {
                    headers: {
                        'content-type': 'application/json',
                        'accept': 'application/json',
                        'Authorization': 'Bearer ' + this.api_token,
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                };
            },
            getEmails(url) {
                let link = url || 'api/emails'
                axios.get(link, this.getHeaders())
                    .then((res) => {
                        let response = res.data.data.emails
                        console.log('Response from fetching emails', response)
                        this.emails = response.data

                        this.makePagination(response)
                    })
                .catch((err) => {
                    console.log("Error fetching emails", err.response.data)
                    this.$emit('apiErrors', err.response.data);
                })
            },
            makePagination(response) {
                this.pagination = {
                    current_page: response.current_page,
                    last_page: response.last_page,
                    next_page_url: response.next_page_url,
                    prev_page_url: response.prev_page_url,
                    total: response.total,
                }
            },
            viewEmailDetails(email_id) {
                let url = "/emails/" + email_id;

                setTimeout(function () {
                    window.location.href = url
                }, 80)
            }
        }
    }
</script>
