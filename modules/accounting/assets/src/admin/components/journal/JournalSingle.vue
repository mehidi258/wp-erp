<template>
    <div class="wperp-modal-dialog journal-single">
        <div class="wperp-modal-content">
            <div class="wperp-modal-header">
                <h4>Journal</h4>
                <div class="d-print-none">
                    <a href="#" class="wperp-btn btn--default print-btn" @click.prevent="printPopup">
                        <i class="flaticon-printer-1"></i>
                        &nbsp; Print
                    </a>
                    <!-- todo: more action has some dropdown and will implement later please consider as planning -->
                    <a href="#" class="wperp-btn btn--default">
                        <i class="flaticon-settings-work-tool"></i>
                        &nbsp; More Action
                    </a>
                </div>
            </div>
            <div class="wperp-modal-body">
                <div class="wperp-invoice-panel">
                    <div class="invoice-header" v-if="null != company">
                        <div class="invoice-logo">
                            <img :src="company.logo" alt="logo name" width="100" height="100">
                        </div>
                        <div class="invoice-address">
                            <address>
                                <strong>{{ company.name }}</strong><br>
                                {{ company.address.address_1 }}<br>
                                {{ company.address.address_2 }}<br>
                                {{ company.address.city }}<br>
                                {{ company.address.country }}
                            </address>
                        </div>
                    </div>

                    <div class="invoice-body">
                        <h4>Journal</h4>
                        <div class="wperp-row" v-if="null != journal">
                            <div class="wperp-col-sm-6">
                                <table class="invoice-info pull-right">
                                    <tr>
                                        <th>Journal No</th>
                                        <td>#{{ journal.id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Journal Date:</th>
                                        <td>{{ journal.trn_date }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="wperp-invoice-table" v-if="null != journal">
                        <table class="wperp-table wperp-form-table invoice-table">
                            <thead>
                            <tr>
                                <th>Ledger ID</th>
                                <th>Particulars</th>
                                <th>Debit</th>
                                <th>Credit</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr :key="index" v-for="(line, index) in journal.line_items">
                                <td>{{ line.ledger_id}}</td>
                                <td>{{ line.particulars }}</td>
                                <td>{{ line.debit }}</td>
                                <td>{{ line.credit }}</td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="7">
                                    <ul>
                                        <li><span>Balance:</span> {{ getCurrencySign() + journal.total }}</li>
                                    </ul>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>

                <div class="invoice-attachments d-print-none">
                    <h4>Attachments</h4>
                    <a class="attachment-item" :href="attachment"
                       :key="index"
                       v-for="(attachment, index) in journal.attachments" download>
                        <img :src="acct_var.acct_assets + '/images/file-thumb.png'">
                        <div class="attachment-meta">
                            <span>{{attachment.substring(attachment.lastIndexOf('/')+1) }}</span><br>
                            <!-- <span class="text-muted">file size</span> -->
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    import HTTP from 'admin/http';

    export default {
        name: 'JournalSingle',

        data() {
            return {
                company  : null,
                journal  : {},
                isWorking: false,
                acct_var : erp_acct_var,
            }
        },

        created() {
            this.getCompanyInfo();
            this.getJournal();
        },

        methods: {
            getCompanyInfo() {
                HTTP.get(`/company`).then(response => {
                    this.company = response.data;
                }).then( e => {} ).then(() => {
                    this.isWorking = false;
                });
            },

            getJournal() {
                this.isWorking = true;

                HTTP.get(`/journals/${this.$route.params.id}`).then(response => {
                    this.journal = response.data;
                }).then( e => {} ).then(() => {
                    this.isWorking = false;
                });
            },

            printPopup() {
                window.print();
            }
        },

    }
</script>


<style lang="less">
    .journal-single {
        width: 800px;
        margin: 40px 0;
    }
</style>

