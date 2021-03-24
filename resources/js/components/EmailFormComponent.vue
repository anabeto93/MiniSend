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
                <input type="email" v-model="emailForm.sender" class="form-control" id="sender" name="sender" aria-describedby="senderHelp" placeholder="Enter email">
                <small id="senderHelp" class="form-text text-muted">You can leave it blank to originate from you.</small>
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" v-model="emailForm.subject" class="form-control" id="subject" name="subject" aria-describedby="subjectHelp" placeholder="Enter Subject" required>
                <small id="subjectHelp" class="form-text text-muted">Subject of the email.</small>
            </div>
            <div class="form-group">
                <label for="recipients">Recipient(s)</label>
                <input type="text" v-model="emailForm.recipients" class="form-control" id="recipients" name="recipients" aria-describedby="recipientHelp" placeholder="Enter recipient emails" required>
                <small id="recipientHelp" class="form-text text-muted">Enter recipients separated by commas <b>,</b></small>
            </div>
            <div class="form-group">
                <label for="text_content">Text Content</label>
                <textarea class="form-control" name="text_content" v-model="emailForm.text_content" id="text_content" cols="30" rows="3"  aria-describedby="textHelp" placeholder="Enter text content." required></textarea>
                <small id="textHelp" class="form-text text-muted">Leave blank if sending HTML content.</small>
            </div>
            <div class="form-group">
                <label for="text_content">HTML Content</label>
                <textarea class="form-control" name="html_content" v-model="emailForm.html_content" id="html_content" cols="30" rows="5"  aria-describedby="htmlHelp" placeholder="Enter HTML content." required></textarea>
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
    import { mapActions, mapGetters, mapMutations } from 'vuex';

    export default {
        computed: {
            ...mapGetters({
                success: "GET_SUCCESS_MESSAGE",
                validationErrors: "GET_ERRORS",
                emailForm: "GET_CURRENT_EMAIL"
            })
        },
        watch: {
            'emailForm.text_content': function (after, before) {
                if (this.emailForm.text_content.length > 0 && this.emailForm.text_content.trim() !== 0) {
                    $('#html_content').removeAttr('required').prop('required', false)
                } else {
                    $('#html_content').prop('required', true)
                }
            },
            'emailForm.html_content': function (after, before) {
                if (this.emailForm.html_content.length > 0 && this.emailForm.html_content.trim() !== '') {
                    $('#text_content').removeAttr('required').prop('required', false);
                } else {
                    $('#text_content').prop('required', true)
                }
            },
            'emailForm.recipients': function (after, before) {
                if (this.emailForm.recipients.length > 3 && this.emailForm.recipients.trim() !== '') {
                    let recs = this.emailForm.recipients.split(',')

                    let valid = true
                    let msg = ""

                    if (typeof recs == "string") {
                        valid = this.validateEmail(recs)
                        msg = "The recipient must be a valid email";
                    } else {
                        for (const i in recs) {
                            valid = this.validateEmail(recs[i].trim())

                            msg = "The recipients must be valid emails, separated by commas."
                        }
                    }

                    if (!valid) {
                        this.setErrors([ msg ])
                    } else {
                        this.setErrors([])
                    }
                }
            },
            'emailForm.sender': function (after, before) {
                if (this.emailForm.sender.length > 3 && this.emailForm.sender.trim() !== '') {
                    let msg = "Sender must be a valid email."

                    if (!this.validateEmail(this.emailForm.sender)) {

                        if (this.validationErrors.length > 0) {
                            let index = this.validationErrors.indexOf(msg)

                            if (index < 0) this.validationErrors.push(msg)
                        } else {
                            this.setErrors([ msg ])
                        }
                    } else {
                        let index = this.validationErrors.indexOf(msg)

                        if (this.validationErrors.length === 1 && index === 0) {
                            this.setErrors([])
                        }
                    }
                }
            }
        },
        methods: {
            ...mapActions({ sendEmail: "SEND_EMAIL" }),
            ...mapMutations({ setErrors: "SET_API_ERRORS", updateForm: "SET_EMAIL_PROPERTIES" }),
            createEmail(event) {
                event.preventDefault();

                let data = new FormData();
                let sender = this.emailForm.sender;
                let text_content = this.emailForm.text_content;
                let html_content = this.emailForm.html_content;
                let attachments = this.emailForm.attachments

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

                let recipients = this.emailForm.recipients.split(',')

                if (typeof recipients == "string") {
                    recipients = [recipients]
                }

                for (const i in recipients) {
                    data.append('recipients[]', recipients[i].trim())
                }

                data.append('subject', this.emailForm.subject)

                data.append('_token', $('meta[name="csrf-token"]').attr('content'));

                this.sendEmail(data)
            },
            addFile(event) {
                console.log("changing file")
                this.updateForm({
                    attachments: event.target.files
                })
                console.log("Form Afterwards", this.emailForm)
            },
            validateEmail(value) {
                return /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(value);
            }
        }
    }
</script>
