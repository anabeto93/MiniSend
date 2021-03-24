<template>
    <div>
        <div class="input-group ml-3">
            <input id="search" class="form-control-sm "
                   aria-label="Search Emails" type="text"
                   placeholder="Search Emails" v-model="keyword" />
            <div class="input-group-append">
                <button class="btn btn-sm btn-primary" @click="searchEmails" @keyup.enter="searchEmails" type="button">
                    Search
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    module.exports = {
        props: ['api_token'],
        data() {
            return {
                keyword: null,
                bySubject: null,
                byRecipient: null,
                bySender: null,
            }
        },
        watch: {
            keyword: {
                handler: _.debounce(function() {
                    if (this.keyword.length > 0) {

                        this.getOtherParameters()
                        this.searchEmails();
                    }
                }, 100)
            }
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
            searchEmails() {
                console.log("Searching emails for " + this.keyword)
                let link = 'api/emails/search'
                let params = []

                if (this.bySender) {
                    params['sender'] = this.bySender
                }

                if (this.byRecipient) {
                    params['recipient'] = this.byRecipient
                }

                if (this.bySubject) {
                    params['subject'] = this.bySubject
                }

                if (Object.keys(params).length > 0) {
                    link = link + "?"
                    for (const i in params) {
                        link = link + i + "=" + params[i] + "&"
                    }
                }

                axios.get(link, this.getHeaders())
                    .then((res) => {
                        console.log("Response from Search", res.data)

                        this.$emit('emailsUpdated', res.data.data.emails);
                    })
                    .catch((err) => {
                        console.log("Error searching emails", err.response.data)
                        this.$emit('apiErrors', err.response.data);
                    })
            },
            getOtherParameters() {
                let keyword = (' ' + this.keyword).slice(1) //safe copy
                keyword = keyword.toLowerCase()

                let terms = ["to:","from:","subject:"]

                //start afresh
                this.byRecipient = ''
                this.bySender = ''
                this.bySubject = ''

                $.each(terms, (i, term) => {
                    let copy = (' ' + keyword).slice(1)
                    let index = copy.indexOf(term)

                    if (index === -1) return true;

                    copy = copy.slice(index)

                    if (Object.keys(copy).length > 0) {
                        copy = copy.split(term)

                        if (Object.keys(copy).length > 1) {
                            copy = copy[1].split(" ")
                        }

                        copy = ('' + copy[0]).slice(1).trim() !== '' ? copy[0] : copy[1]

                        if (term === "to:") {
                            this.byRecipient = copy
                        } else if (term === "from:") {
                            this.bySender = copy
                        } else if (term === "subject:") {
                            this.bySubject = copy
                        }
                    }
                })

                if (!keyword.includes("from:")) {
                    this.bySender = ''
                }

                if (!keyword.includes("to:")) {
                    this.byRecipient = ''
                }

                //the subject is a lot more complex, so be careful
                let remnant = (' ' + keyword).slice(1)

                if (this.byRecipient) {
                    let regex = new RegExp("#" + this.byRecipient + "#", "g")
                    remnant = remnant.replace(regex, "").replace(this.byRecipient, "")
                }

                if (this.bySender) {
                    let regex = new RegExp("#" + this.bySender + "#", "g")
                    remnant = remnant.replace(regex, "").replace(this.bySender, "")
                }

                //now remove the from: and to: too
                let from = new RegExp("#from:#", "g")
                let to = new RegExp("#to:#", "g")

                remnant = remnant.replace(from, "").replace("from:", "")
                    .replace(to, "").replace("to:", "")

                remnant = remnant.trim().replace( /\s\s+/g, ' ' )

                this.bySubject = remnant
            }
        }
    }
</script>
