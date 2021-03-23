<template>
    <div>
        <div v-if="success != ''" class="alert alert-success">
            {{success}}
        </div>
        <div v-if="validationErrors.length" class="alert alert-danger">
            <div v-for="(v, k) in validationErrors" :key="k">
                {{ v }}
            </div>
        </div>

        <form @submit="createEmail" enctype="multipart/form-data">
            <div class="form-group">
                <label for="sender">Sender</label>
                <input type="email" v-model="sender" class="form-control" id="sender" name="sender" aria-describedby="senderHelp" placeholder="Enter email">
                <small id="senderHelp" class="form-text text-muted">You can leave it blank to originate from you.</small>
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" v-model="subject" class="form-control" id="subject" name="subject" aria-describedby="subjectHelp" placeholder="Enter Subject" required>
                <small id="subjectHelp" class="form-text text-muted">Subject of the email.</small>
            </div>
            <div class="form-group">
                <label for="recipients">Recipient(s)</label>
                <input type="text" v-model="recipients" class="form-control" id="recipients" name="recipients" aria-describedby="recipientHelp" placeholder="Enter recipient emails" required>
                <small id="recipientHelp" class="form-text text-muted">Enter recipients separated by commas <b>,</b></small>
            </div>
            <div class="form-group">
                <label for="text_content">Text Content</label>
                <textarea class="form-control" name="text_content" v-model="text_content" id="text_content" cols="30" rows="3"  aria-describedby="textHelp" placeholder="Enter text content." required></textarea>
                <small id="textHelp" class="form-text text-muted">Leave blank if sending HTML content.</small>
            </div>
            <div class="form-group">
                <label for="text_content">HTML Content</label>
                <textarea class="form-control" name="html_content" v-model="html_content" id="html_content" cols="30" rows="5"  aria-describedby="htmlHelp" placeholder="Enter HTML content." required></textarea>
                <small id="htmlHelp" class="form-text text-muted">Leave blank if sending Text content.</small>
            </div>
            <div class="form-group">
                <label for="attachments">Attachments</label>
                <input type="file" @change="addFile" class="form-control" id="attachments" name="attachments[]" multiple/>
            </div>
            <button class="btn btn-primary btn-block">Send</button>
        </form>
    </div>
</template>

<script>
    module.exports = {
        props: ['api_token'],
        data() {
            return {
                sender: null,
                subject: null,
                recipients: null,
                text_content: null,
                html_content: null,
                attachments: null,
                success: '',
                validationErrors: []
            }
        },
        watch: {
            text_content(after, before) {
                if (this.text_content.length > 0 && this.text_content.trim() !== 0) {
                    $('#html_content').removeAttr('required').prop('required', false)
                } else {
                    $('#html_content').prop('required', true)
                }
            },
            html_content(after, before) {
                if (this.html_content.length > 0 && this.html_content.trim() !== '') {
                    $('#text_content').removeAttr('required').prop('required', false);
                } else {
                    $('#text_content').prop('required', true)
                }
            },
            recipients(after, before) {
                if (this.recipients.length > 3 && this.recipients.trim() !== '') {
                    let recs = this.recipients.split(',')

                    let valid = false
                    let msg = ""

                    if (typeof recs == "string") {
                        valid = this.validateEmail(recs)
                        msg = "The recipient must be a valid email";
                    } else {
                        for (const i in recs) {
                            valid = this.validateEmail(recs[i])

                            msg = "The recipients must be valid emails, separated by commas."
                        }
                    }

                    if (!valid) {
                        this.validationErrors = [ msg ];
                    } else {
                        this.validationErrors = []
                    }
                }
            },
            sender(after, before) {
                if (this.sender.length > 3 && this.sender.trim() !== '') {
                    if (!this.validateEmail(this.sender)) {
                        let msg = "Sender must be a valid email."

                        if (this.validationErrors.length > 0) {
                            let index = this.validationErrors.indexOf(msg)

                            if (index < 0) this.validationErrors.push(msg)
                        } else {
                            this.validationErrors = [msg];
                        }
                    } else {
                        let index = this.validationErrors.indexOf(msg)

                        if (this.validationErrors.length === 1 && index === 0) {
                            this.validationErrors = []
                        }
                    }
                }
            }
        },
        methods: {
            createEmail(event) {
                event.preventDefault();

                let data = new FormData();
                let sender = this.sender;
                let text_content = this.text_content;
                let html_content = this.html_content;
                let attachments = this.attachments

                if (sender !== null && (typeof sender == "string" && sender.trim() !== '')) {
                    data.append('sender', sender);
                }

                if (text_content !== null && (typeof text_content == "string" && text_content.trim() !== '')) {
                    data.append('text_content', text_content);
                }

                if (html_content !== null && (typeof html_content == "string" && html_content.trim() !== '')) {
                    data.append('html_content', html_content);
                }

                if (attachments !== null) {
                    for (const fi of Object.keys(attachments)) {
                        data.append('attachments[]', attachments[fi]);
                    }
                }

                let recipients = this.recipients.split(',')

                if (typeof recipients == "string") {
                    recipients = [recipients]
                }

                data.append('recipients[]', recipients)
                data.append('subject', this.subject)

                data.append('_token', $('meta[name="csrf-token"]').attr('content'));

                const config = {
                    headers: {
                        'content-type': 'multipart/form-data',
                        'accept': 'application/json',
                        'Authorization': 'Bearer ' + this.api_token,
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }

                axios.post('/api/emails', data, config)
                .then((res) => {
                    let response = res.data

                    if (response.error_code === 201) {
                        this.success = response.message
                        this.validationErrors = []

                        this.emptyForm()
                    }
                }).catch((err) => {
                    let errors = err.response.data
                    console.log('errors', err.response.data)

                    if (errors.error_code === 422) {
                        let e = [];

                        for (i in errors.data.errors) {
                            e.push(errors.data.errors[i][0])
                        }

                        this.validationErrors = e
                    }
                })
            },
            addFile(event) {
                this.attachments = event.target.files
            },
            validateEmail(value) {
                return /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(value);
            },
            emptyForm() {
                this.sender = ''
                this.subject = ''
                this.recipients = ''
                this.text_content = ''
                this.html_content = ''
                this.attachments = ''

                $('#attachments').prop('value', '')
            }
        }
    }
</script>
