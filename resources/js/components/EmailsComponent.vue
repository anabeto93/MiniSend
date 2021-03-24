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
    import { mapActions, mapGetters } from 'vuex';

    export default {
        created() {
            setTimeout(() => {
                this.getEmails();
            }, 200)
        },
        computed: {
            ...mapGetters({ emails: "GET_EMAILS", pagination: "GET_PAGINATION" })
        },
        methods: {
            ...mapActions({ getEmails: "FETCH_EMAILS" }),
            viewEmailDetails(email_id) {
                let url = "/emails/" + email_id;

                setTimeout(function () {
                    window.location.href = url
                }, 80)
            }
        }
    }
</script>
