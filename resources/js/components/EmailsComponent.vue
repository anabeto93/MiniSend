<template>
    <div class="mt-4">
        <h4>Emails</h4>
        <div class="card card-body mb-2" v-for="email in emails" :key="email.uuid">
            <span>From: {{ email.from }}</span>
            <span>To: {{ email.to }}</span>
            <span>Subject: {{ email.subject }}</span>
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
                total: null,
                current_page: null,
                next_page: null,
                prev_page: null,
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
                        'content-type': 'multipart/form-data',
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
                        let response = res.data.data.emails.data
                        console.log('Response from fetching emails', response)
                        this.emails = response
                        this.total = response.total
                        this.current_page = response.current_page
                        this.next_page = response.next_page_url
                        this.prev_page = response.prev_page_url
                    })
                .catch((err) => {
                    console.log("Error fetching emails", err.response.data)
                    this.$emit('apiErrors');
                })
            }
        }
    }
</script>
