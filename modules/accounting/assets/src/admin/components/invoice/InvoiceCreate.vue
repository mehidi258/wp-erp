<template>
    <div class="wperp-container invoice-create">

        <!-- Start .header-section -->
        <div class="content-header-section separator">
            <div class="wperp-row wperp-between-xs">
                <div class="wperp-col">
                    <h2 class="content-header__title">{{ editMode ? 'Edit' : 'New' }} Invoice</h2>
                </div>
            </div>
        </div>
        <!-- End .header-section -->

        <form action="" method="post" @submit.prevent="submitInvoiceForm">

        <div class="wperp-panel wperp-panel-default" style="padding-bottom: 0;">
            <div class="wperp-panel-body">
                <!-- <form action="#" class="wperp-form" method="post"> -->
                    <div class="wperp-row">
                        <div class="wperp-col-sm-4">
                            <select-customers :reset="reset" v-model="basic_fields.customer"></select-customers>
                        </div>
                        <div class="wperp-col-sm-4">
                            <div class="wperp-form-group">
                                <label>Transaction Date<span class="wperp-required-sign">*</span></label>
                                <datepicker v-model="basic_fields.trn_date"></datepicker>
                            </div>
                        </div>
                        <div class="wperp-col-sm-4">
                            <div class="wperp-form-group">
                                <label>Due Date<span class="wperp-required-sign">*</span></label>
                                <datepicker v-model="basic_fields.due_date"></datepicker>
                            </div>
                        </div>
                        <div class="wperp-col-sm-6">
                            <label>Billing Address</label>
                            <textarea v-model="basic_fields.billing_address" rows="2" class="wperp-form-field" placeholder="Type here"></textarea>
                        </div>
                        <div class="wperp-col-sm-6 with-multiselect">
                            <label>Type</label>
                            <multi-select v-model="inv_type" :options="inv_types"></multi-select>
                        </div>
                    </div>
                <!-- </form> -->

            </div>
        </div>

            <div class="wperp-table-responsive">
                <!-- Start .wperp-crm-table -->
                <div class="table-container">
                    <table class="wperp-table wperp-form-table">
                        <thead>
                            <tr>
                                <th scope="col">Product/Service</th>
                                <th scope="col">Qty</th>
                                <th scope="col">Unit Price</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Tax</th>
                                <th scope="col" class="col--actions"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <invoice-trn-row
                                :line="line"
                                :products="products"
                                :taxSummary="taxSummary"
                                :key="index"
                                v-for="(line, index) in transactionLines"
                            ></invoice-trn-row>

                            <tr class="padded"></tr>

                            <tr class="discount-rate-row">
                                <td colspan="4" class="text-right with-multiselect">
                                    <select v-model="discountType">
                                        <option value="discount-percent">Discount percent</option>
                                        <option value="discount-value">Discount value</option>
                                    </select>
                                </td>
                                <td><input type="text" v-model="discount"
                                    :placeholder="discountType">
                                    <em v-show="'discount-percent' === discountType">%</em>
                                </td>
                                <td></td>
                            </tr>

                            <tr class="tax-rate-row">
                                <td colspan="4" class="text-right with-multiselect">
                                    <multi-select v-model="taxRate"
                                        :options="taxRates"
                                        class="tax-rates"
                                        placeholder="Select sales tax" />
                                </td>
                                <td><input type="text" v-model="taxTotalAmount" readonly></td>
                                <td></td>
                            </tr>

                            <tr class="total-amount-row">
                                <td colspan="4" class="text-right">
                                    <span>Total Amount = </span>
                                </td>
                                <td><input type="text" v-model="finalTotalAmount" readonly></td>
                                <td></td>
                            </tr>
                            <tr class="add-new-line">
                                <td colspan="9" style="text-align: left;">
                                    <button @click.prevent="addLine" class="wperp-btn btn--primary add-line-trigger"><i class="flaticon-add-plus-button"></i>Add Line</button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="attachment-item" :key="index" v-for="(file, index) in attachments">
                                        <img :src="erp_acct_assets + '/images/file-thumb.png'">
                                        <span class="remove-file" @click="removeFile(index)">&#10007;</span>

                                        <div class="attachment-meta">
                                            <h3>{{ getFileName(file) }}</h3>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr class="add-attachment-row">
                                <td colspan="9" style="text-align: left;">
                                    <div class="attachment-container">
                                        <label class="col--attachement">Attachment</label>
                                        <file-upload v-model="attachments" url="/invoices/attachments"/>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="9" style="text-align: right;">
                                    <combo-button v-if="editMode" :options="updateButtons" />
                                    <combo-button v-else :options="createButtons" />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </form>

        <!-- End .wperp-crm-table -->
    </div>
</template>

<script>
    import { mapState, mapActions } from 'vuex'

    import HTTP from 'admin/http'
    import Datepicker from 'admin/components/base/Datepicker.vue'
    import FileUpload from 'admin/components/base/FileUpload.vue'
    import ComboButton from 'admin/components/select/ComboButton.vue';
    import SubmitButton from 'admin/components/base/SubmitButton.vue'
    import InvoiceTrnRow from 'admin/components/invoice/InvoiceTrnRow.vue'
    import SelectCustomers from 'admin/components/people/SelectCustomers.vue'
    import PrintPreview from 'admin/components/base/PrintPreview.vue';
    import MultiSelect from 'admin/components/select/MultiSelect.vue';

    export default {
        name: 'InvoiceCreate',

        components: {
            HTTP,
            MultiSelect,
            Datepicker,
            FileUpload,
            ComboButton,
            PrintPreview,
            SubmitButton,
            InvoiceTrnRow,
            SelectCustomers
        },

        data() {
            return {
                basic_fields: {
                    customer       : '',
                    trn_date       : '',
                    due_date       : '',
                    billing_address: ''
                },

                inv_types: [
                    {id: 0, name: 'Invoice'},
                    {id: 1, name: 'Estimate'}
                ],

                createButtons: [
                    {id: 'save', text: 'Create Invoice'},
                    {id: 'send_create', text: 'Create and Send'},
                    {id: 'new_create', text: 'Create and New'},
                ],

                updateButtons: [
                    {id: 'update', text: 'Update Invoice'},
                    {id: 'send_update', text: 'Update and Send'},
                    {id: 'new_update', text: 'Update and New'},
                ],

                editMode        : false,
                voucherNo       : 0,
                discountType    : 'discount-percent',
                discount        : 0,
                status          : 2,
                taxRate         : null,
                taxSummary      : null,
                products        : [],
                attachments     : [],
                transactionLines: [],
                taxRates        : [],
                taxTotalAmount  : 0,
                finalTotalAmount: 0,
                inv_type        : {id: 0, name: 'Invoice'},
                erp_acct_assets : erp_acct_var.acct_assets,
                isWorking       : false,
                reset           : false,
                actionType      : null,
            }
        },

        watch: {
            'basic_fields.customer'() {
                this.reset = false;

                this.getCustomerAddress();
            },

            taxRate(newVal) {
                this.$store.dispatch('sales/setTaxRateID', newVal.id);
            },

            discount() {                
                this.discountChanged();
            },

            discountType() {
                this.discountChanged();
            },

            invoiceTotalAmount() {                
                this.discountChanged();
            }
        },

        computed: mapState({
            invoiceTotalAmount: state => state.sales.invoiceTotalAmount
        }),

        created() {
            this.prepareDataLoad();

            this.$root.$on('remove-row', index => {
                this.$delete(this.transactionLines, index);
                this.updateFinalAmount();
            });

            this.$root.$on('total-updated', amount => {
                this.updateFinalAmount();
            });

            this.$root.$on('combo-btn-select', button => {
                this.actionType = button.id;
            });
        },

        methods: {
            async prepareDataLoad() {
                /**
                 * ----------------------------------------------
                 * check if editing
                 * -----------------------------------------------
                 */
                if ( this.$route.params.id ) {
                    this.editMode = true;
                    this.voucherNo = this.$route.params.id;

                    // load products and taxes, before invoice load
                    let [request1, request2] = await Promise.all([
                        HTTP.get('/products'),
                        HTTP.get('/taxes/summary')
                    ]);
                    let request3 = await HTTP.get(`/invoices/${this.$route.params.id}`);

                    if ( ! request3.data.line_items.length ) {
                        this.$swal({
                            position         : 'center',
                            type             : 'error',
                            title            : 'Invoice does not exists!',
                            showConfirmButton: false,
                            timer            : 1500
                        });

                        return;
                    }

                    this.products = request1.data;
                    this.taxRates = this.getUniqueTaxRates(request2.data);
                    this.setDataForEdit( request3.data );

                } else {
                    /**
                     * ----------------------------------------------
                     * create a new invoice
                     * -----------------------------------------------
                     */
                    this.getProducts();
                    this.getTaxRates();

                    this.basic_fields.trn_date = erp_acct_var.current_date;
                    this.basic_fields.due_date = erp_acct_var.current_date;
                    this.transactionLines.push({}, {}, {});
                }
            },

            setDataForEdit(invoice) {                
                this.basic_fields.customer        = { id: parseInt(invoice.customer_id), name: invoice.customer_name };
                this.basic_fields.billing_address = invoice.billing_address;
                this.basic_fields.trn_date        = invoice.trn_date;
                this.basic_fields.due_date        = invoice.due_date;
                this.discount                     = invoice.discount;
                this.status                       = invoice.status;
                this.transactionLines             = invoice.line_items;
                this.taxTotalAmount               = invoice.tax;
                this.finalTotalAmount             = invoice.debit;
                this.attachments                  = invoice.attachments;

                this.taxRate = { 
                    id: parseInt(invoice.tax_rate_id),
                    name: this.getTaxRateNameByID(invoice.tax_rate_id)
                };

                if ( '1' == invoice.estimate ) {
                    this.inv_type = { id: 1, name: 'Estimate' };
                }
            },

            getProducts() {
                HTTP.get('/products').then(response => {
                    this.products = response.data;
                });
            },

            getCustomerAddress() {
                let customer_id = this.basic_fields.customer.id;

                if ( ! customer_id ) {
                    this.basic_fields.billing_address = '';
                    return;
                }

                HTTP.get(`/people/${customer_id}`).then(response => {
                    let billing = response.data;

                    let address = `Street: ${billing.street_1} ${billing.street_2} \nCity: ${billing.city} \nState: ${billing.state} \nCountry: ${billing.country}`;

                    this.basic_fields.billing_address = address;
                });
            },

            discountChanged() {
                let discount = this.discount;                

                if ( 'discount-percent' === this.discountType ) {
                    discount = ( this.invoiceTotalAmount * discount ) / 100;
                }

                this.$store.dispatch('sales/setDiscount', discount);
            },

            getTaxRates() {
                HTTP.get('/taxes/summary').then(response => {
                    this.taxSummary = response.data;

                    this.taxRates = this.getUniqueTaxRates(this.taxSummary);
                });
            },

            getTaxRateNameByID(id) {
                // Array.find()
                let taxRate = this.taxRates.find( rate => {
                    return rate.id == id;
                } );
                
                return taxRate.name;
            },

            getUniqueTaxRates( taxes ) {
                return Array.from(new Set(taxes.map(tax => tax.tax_rate_id))).map(tax_rate_id => {
                    let tax = taxes.find(tax => tax.tax_rate_id === tax_rate_id);

                    if ( tax.default ) {
                        // set default tax rate name for invoice
                        this.taxRate = { id: tax_rate_id, name: tax.tax_rate_name };
                        this.$store.dispatch('sales/setTaxRateID', tax_rate_id);
                    }

                    return {
                        id: tax_rate_id,
                        name: tax.tax_rate_name
                    };
                })
            },

            addLine() {
                this.transactionLines.push({});
            },

            updateFinalAmount() {
                let taxAmount     = 0;
                let totalDiscount = 0;
                let totalAmount   = 0;

                this.transactionLines.forEach(element => {
                    if ( element.qty ) {
                        taxAmount     += parseFloat(element.taxAmount);
                        totalDiscount += parseFloat(element.discount);
                        totalAmount   += parseFloat(element.amount);
                    } 
                });

                this.$store.dispatch('sales/setInvoiceTotalAmount', totalAmount);

                let finalAmount = (totalAmount - totalDiscount) + taxAmount;

                this.taxTotalAmount   = taxAmount.toFixed(2);
                this.finalTotalAmount = finalAmount.toFixed(2);
            },

            formatLineItems() {
                var lineItems = [];

                this.transactionLines.forEach(line => {
                    lineItems.push({
                        product_id       : line.selectedProduct.id,
                        product_type_name: line.selectedProduct.product_type_name,
                        agency_id        : line.agencyId,
                        qty              : line.qty,
                        unit_price       : line.unitPrice,
                        tax              : line.taxAmount,
                        tax_rate         : line.taxRate,
                        discount         : line.discount,
                        item_total       : line.amount
                    });
                });

                return lineItems;
            },

            updateInvoice(requestData) {
                HTTP.put(`/invoices/${this.voucherNo}`, requestData).then(res => {
                    showAlert('success', 'Invoice Updated!');
                }).then(() => {
                    this.isWorking = false;
                    this.reset = true;

                    if ('update' == this.actionType) {
                        this.$router.push({name: 'Sales'});
                    } else if ('new_update' == this.actionType) {
                        this.resetFields();
                    }
                });
            },

            createInvoice(requestData) {
                HTTP.post('/invoices', requestData).then(res => {
                    showAlert('success', 'Invoice Created!');
                }).then(() => {
                    this.isWorking = false;
                    this.reset = true;

                    if ('save' == this.actionType) {
                        this.$router.push({name: 'Sales'});
                    } else if ('new_create' == this.actionType) {
                        this.resetFields();
                    }
                });
            },

            submitInvoiceForm() {                
                this.isWorking = true;

                let requestData = {
                    customer_id    : this.basic_fields.customer.id,
                    date           : this.basic_fields.trn_date,
                    due_date       : this.basic_fields.due_date,
                    billing_address: this.basic_fields.billing_address,
                    discount_type  : this.discountType,
                    tax_rate_id    : this.taxRates.id,
                    line_items     : this.formatLineItems(),
                    attachments    : this.attachments,
                    type           : 'invoice',
                    status         : this.status,
                    estimate       : this.inv_type.id
                };

                if ( this.editMode ) {
                    this.updateInvoice(requestData);
                } else {
                    this.createInvoice(requestData);
                }
            },

            getFileName(path) {
                return path.replace(/^.*[\\\/]/, '');
            },

            removeFile(index) {
                this.$delete(this.attachments, index);
            },

            resetFields() {
                this.basic_fields.customer        = '';
                this.basic_fields.trn_date        = erp_acct_var.current_date;
                this.basic_fields.due_date        = erp_acct_var.current_date;
                this.basic_fields.billing_address = '';
                this.attachments                  = [];
                this.transactionLines             = [];
                this.finalTotalAmount             = 0;
                this.isWorking                    = false;
                this.reset                        = false;
                this.actionType                   = null;
            }
        }

    }
</script>

<style lang="less">
    tr.padded {
        height: 50px;
    }

    .discount-rate-row {
        select {
            width: 235px;
            height: 34px;
        }

        input {
            width: 130px !important;
        }
    }

    .tax-rate-row {
        .tax-rates {
            width: 235px;
            float: right;
        }
    }

    .attachment-item {
        box-shadow: 0 0 0 1px rgba(76, 175, 80, 0.3);
        padding: 3px;
        position: relative;
        height: 58px;
        margin: 10px 0;

        .remove-file {
            position: absolute;
            top: -10px;
            right: -10px;
            font-size: 13px;
            color: #fff;
            cursor: pointer;
            background: #f44336;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
        }

        img {
            float: left;
        }
    }

    .attachment-meta {
        h3 {
            margin-left: 50px;
            text-align: left;
            line-height: 2;
        }
    }
</style>
