<template>
    <div class="wperp-container">

        <!-- Start .header-section -->
        <div class="content-header-section separator">
            <div class="wperp-row wperp-between-xs">
                <div class="wperp-col">
                    <h2 class="content-header__title">Bill</h2>
                </div>
            </div>
        </div>
        <!-- End .header-section -->

        <div class="wperp-panel wperp-panel-default" style="padding-bottom: 0;">
            <div class="wperp-panel-body">

                <show-errors :error_msgs="form_errors" ></show-errors>

                <form action="" class="wperp-form" method="post">
                    <div class="wperp-row">
                        <div class="wperp-col-sm-3">
                            <div class="wperp-form-group">
                                <select-people v-model="basic_fields.user"></select-people>
                            </div>
                        </div>
                        <div class="wperp-col-sm-3">
                            <div class="wperp-form-group">
                                <label>Reference<span class="wperp-required-sign">*</span></label>
                                <input type="text" v-model="basic_fields.trn_ref"/>
                            </div>
                        </div>
                        <div class="wperp-col-sm-3">
                            <div class="wperp-form-group">
                                <label>Bill Date<span class="wperp-required-sign">*</span></label>
                                <datepicker v-model="basic_fields.trn_date"></datepicker>
                            </div>
                        </div>
                        <div class="wperp-col-sm-3">
                            <div class="wperp-form-group">
                                <label>Due Date<span class="wperp-required-sign">*</span></label>
                                <datepicker v-model="basic_fields.due_date"></datepicker>
                            </div>
                        </div>
                        <div class="wperp-col-xs-12">
                            <label>Billing Address</label>
                            <textarea v-model.trim="basic_fields.billing_address" rows="3" class="wperp-form-field" placeholder="Type here"></textarea>
                        </div>
                    </div>
                </form>

            </div>
        </div>

        <div class="wperp-table-responsive">
            <!-- Start .wperp-crm-table -->
            <div class="table-container">
                <table class="wperp-table wperp-form-table">
                    <thead>
                    <tr>
                        <th scope="col" class="col--id column-primary">ID</th>
                        <th scope="col">Account</th>
                        <th scope="col">Description</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Total</th>
                        <th scope="col" class="col--actions"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr :key="key" v-for="(line,key) in transactionLines">
                        <td scope="row" class="col--id column-primary">{{key+1}}</td>
                        <td class="col--account with-multiselect"><multi-select v-model="line.ledger_id" :options="ledgers" /></td>
                        <td class="col--particulars"><textarea v-model="line.description" rows="1" class="wperp-form-field display-flex" placeholder="Particulars"></textarea></td>
                        <td class="col--amount" data-colname="Amount">
                            <input type="text" name="amount" v-model="line.amount" @keyup="updateFinalAmount" class="text-right"/>
                        </td>
                        <td class="col--total" style="text-align: center" data-colname="Total">
                            <input type="text" :value="line.amount" readonly disabled/>
                        </td>
                        <td class="delete-row" data-colname="Remove Above Selection">
                            <a @click.prevent="removeRow(key)" href="#"><i class="flaticon-trash"></i></a>

                        </td>
                    </tr>

                    <tr class="total-amount-row">
                        <td class="text-right pr-0 hide-sm" colspan="4">Total Amount</td>
                        <td class="text-right" data-colname="Total Amount">
                            <input type="text" class="text-right" name="finalamount" v-model="finalTotalAmount" readonly disabled/></td>
                        <td class="text-right"></td>
                    </tr>
                    <tr class="add-new-line">
                        <td colspan="9" style="text-align: left;">
                            <button @click.prevent="addLine" class="wperp-btn btn--primary add-line-trigger"><i class="flaticon-add-plus-button"></i>Add Line</button>
                        </td>
                    </tr>
                    <tr class="wperp-form-group">
                        <td colspan="9" style="text-align: left;">
                            <label>Particulars</label>
                            <textarea v-model="particulars" rows="4" class="wperp-form-field display-flex" placeholder="Internal Information"></textarea>
                        </td>
                    </tr>
                    </tbody>
                    <tr class="add-attachment-row">
                        <td colspan="9" style="text-align: left;">
                            <div class="attachment-container">
                                <label class="col--attachement">Attachment</label>
                                <file-upload v-model="attachments" url="/bills/attachments"/>
                            </div>
                        </td>
                    </tr>
                    <tfoot>
                    <tr>
                        <td colspan="9" style="text-align: right;">
                            <submit-button text="Add New Bill" @click.native="SubmitForBill" :working="isWorking"></submit-button>
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</template>

<script>
    import HTTP from 'admin/http'
    import Datepicker from 'admin/components/base/Datepicker.vue'
    import MultiSelect from 'admin/components/select/MultiSelect.vue'
    import FileUpload from 'admin/components/base/FileUpload.vue'
    import SelectPeople from 'admin/components/people/SelectPeople.vue'
    import SubmitButton from 'admin/components/base/SubmitButton.vue'
    import ShowErrors from 'admin/components/base/ShowErrors.vue'

    export default {
        name: 'BillCreate',

        components: {
            HTTP,
            Datepicker,
            MultiSelect,
            FileUpload,
            SubmitButton,
            SelectPeople,
            ShowErrors
        },

        data() {
            return {
                basic_fields: {
                    user: '',
                    trn_ref: '',
                    trn_date: '',
                    due_date: '',
                    billing_address: ''
                },

                form_errors: [],

                transactionLines: [{}],
                selected:[],
                ledgers: [],
                attachments: [],
                totalAmounts:[],
                finalTotalAmount: 0,
                particulars: '',
                isWorking: false,
                acct_assets: erp_acct_var.acct_assets
            }
        },

        created() {
            this.getLedgers();

            this.$on('remove-row', index => {
                this.$delete(this.transactionLines, index);
                this.updateFinalAmount();
            });
        },

        methods: {
            getLedgers() {
                HTTP.get(`/ledgers`).then((response) => {
                   response.data.forEach(element => {
                       this.ledgers.push({
                           id: element.id,
                           name: element.name
                       });
                   });
                });
            },

            getPeopleAddress() {
                let people_id = this.basic_fields.user.id;

                if ( ! people_id ) return;

                HTTP.get(`/customers/${people_id}`).then(response => {
                    // add more info
                    this.basic_fields.billing_address =
                        `Street: ${response.data.billing.street_1} ${response.data.billing.street_2},
                        City: ${response.data.billing.city}, Country: ${response.data.billing.country}`;
                });
            },

            updateFinalAmount() {
                let finalAmount = 0;

                this.transactionLines.forEach(element => {
                    finalAmount += parseFloat(element.amount);
                });

                this.finalTotalAmount = parseFloat(finalAmount).toFixed(2);
            },

            addLine() {
                this.transactionLines.push({});
            },

            SubmitForBill() {
                this.validateForm();

                if ( this.form_errors.length ) {
                    return;
                }

                HTTP.post('/bills', {
                    vendor_id: this.basic_fields.user.id,
                    ref: this.basic_fields.trn_ref,
                    trn_date: this.basic_fields.trn_date,
                    due_date: this.basic_fields.due_date,
                    bill_details: this.formatTrnLines(this.transactionLines),
                    attachments: this.attachments,
                    billing_address: this.basic_fields.billing_address,
                    type: 'bill',
                    status: 2,
                    remarks: this.particulars,
                }).then(res => {
                    this.$swal({
                        position: 'center',
                        type: 'success',
                        title: 'Bill Created!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }).then(() => {
                    this.resetData();
                    this.isWorking = false;
                });
            },

            validateForm() {
                if ( !this.basic_fields.user.hasOwnProperty('id') ) {
                    this.form_errors.push('People Name is required.');
                }

                if ( !this.basic_fields.trn_ref ) {
                    this.form_errors.push('Transaction Reference is required.');
                }

                if ( !this.basic_fields.trn_date ) {
                    this.form_errors.push('Transaction Date is required.');
                }

                if ( !this.basic_fields.due_date ) {
                    this.form_errors.push('Due Date is required.');
                }
            },

            formatTrnLines( trl_lines ) {
                trl_lines.forEach(element => {
                    if ( element.length ) {
                        element.ledger_id = element.ledger_id.id;
                    }
                });

                return trl_lines;
            },

            resetData() {
                Object.assign(this.$data, this.$options.data.call(this));
                this.basic_fields.user = '';
            },

            removeRow(index) {
                this.$delete(this.transactionLines, index);
                this.updateFinalAmount();
            },

        },

        watch: {
            finalTotalAmount( newval ) {
                this.finalTotalAmount = newval;
            },

            'basic_fields.user'() {
                this.getPeopleAddress();
            }
        },

    }
</script>

<style lang="less">

</style>
