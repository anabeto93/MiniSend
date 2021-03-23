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
        data() {
            return {
                keyword: null,
                bySubject: null,
            }
        },
        watch: {
            keyword: {
                handler: _.debounce(function() {
                    if (this.keyword.length > 1) {
                        this.searchEmails();
                    }
                }, 100)
            }
        },
        methods: {
            searchEmails() {
                console.log("Searching emails for " + this.keyword)
            }
        }
    }
</script>
