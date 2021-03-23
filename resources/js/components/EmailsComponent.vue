<template>
    <div>
        <h2>Emails Component</h2>
        <div class="card card-body" v-for="email in emails" :key="email.uuid">
            <h3>{{ email.subject }}</h3>
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
            getEmails() {
                axios.get('api/emails', this.getHeaders())
                    .then((res) => {
                        let response = res.data
                        console.log('Response from fetching emails', response)
                        this.emails = response.data.emails
                    })
                .catch((err) => {
                    console.log("Error fetching emails", err.response.data)
                    this.$emit('apiErrors');
                })
            }
        }
    }
</script>
