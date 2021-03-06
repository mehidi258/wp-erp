<template>
    <div class="app-customers">
        <div class="content-header-section separator">
            <div class="wperp-row wperp-between-xs">
                <div class="wperp-col">
                    <h2 class="content-header__title">Tax Rates</h2>
                    <a class="wperp-btn btn--primary" @click.prevent="addTaxPayment">
                        <span>Pay Tax</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="table-container">
            <list-table
                tableClass="wp-ListTable widefat fixed tax-records-list wperp-table table-striped table-dark"
                action-column="actions"
                :columns="columns"
                :rows="row_data"
                :total-items="paginationData.totalItems"
                :total-pages="paginationData.totalPages"
                :per-page="paginationData.perPage"
                :current-page="paginationData.currentPage"
                @pagination="goToPage"
                :actions="actions"
                :bulk-actions="bulkActions"
                @action:click="onActionClick"
                @bulk:click="onBulkAction">
            </list-table>
        </div>

    </div>
</template>

<script>
    import HTTP from 'admin/http'
    import ListTable     from 'admin/components/list-table/ListTable.vue'
    import NewTaxRate     from 'admin/components/tax/NewTaxRate.vue'

    export default {
        name: 'TaxRecords',

        components: {
            ListTable,
            NewTaxRate
        },

        data () {
            return {
                voucher_no: 0,
                agency_id: 0,
                trn_date: '',
                tax_period: '',
                amount: 0,
                modalParams: null,
                columns: {
                    'voucher_no': {label: 'Voucher No'},
                    'agency_id': {label: 'Agency'},
                    'trn_date': {label: 'Date'},
                    'tax_period': {label: 'Tax Period'},
                    'amount': {label: 'Amount'},
                    'actions': { label: 'Actions' }
                },
                rows: [],
                paginationData: {
                    totalItems: 0,
                    totalPages: 0,
                    perPage: 10,
                    currentPage: this.$route.params.page === undefined ? 1 : parseInt(this.$route.params.page)
                },
                actions : [
                    { key: 'edit', label: 'Edit', iconClass: 'flaticon-edit' },
                    { key: 'trash', label: 'Delete', iconClass: 'flaticon-trash' }
                ],
                bulkActions: [
                    {
                        key: 'trash',
                        label: 'Move to Trash',
                        iconClass: 'flaticon-trash'
                    }
                ],
                taxes: [{}],
                buttonTitle: '',
                pageTitle: '',
                url: '',
                singleUrl: '',
                isActiveOptionDropdown: false,
                showModal: false
            };
        },

        created() {
            this.$on('tax-modal-close', function() {
                this.showModal = false;
            });

            this.pageTitle      =   this.$route.name;
            this.url            =   this.$route.name.toLowerCase();

            this.fetchItems();
        },

        computed: {
            row_data(){
                let items = this.rows;
                items.map( item => {
                    item.voucher_no = item.voucher_no;
                    item.agency_id = item.agency_id;
                    item.trn_date = item.trn_date;
                    item.tax_period = item.tax_period;
                    item.amount = item.amount;
                } );
                return items;
            }
        },

        methods: {
            close() {
                this.showModal = false;
            },
            fetchItems(){
                this.rows = [];
                HTTP.get('taxes/tax-records', {
                    params: {
                        per_page: this.paginationData.perPage,
                        page: this.$route.params.page === undefined ? this.paginationData.currentPage : this.$route.params.page,
                    }
                })
                    .then( (response) => {
                        this.rows = response.data;
                        this.paginationData.totalItems = parseInt(response.headers['x-wp-total']);
                        this.paginationData.totalPages = parseInt(response.headers['x-wp-totalpages']);
                    })
                    .catch((error) => {
                        console.log(error);
                    })
                    .then( () => {
                        //ready
                    } );
            },

            goToPage(page) {
                let queries = Object.assign({}, this.$route.query);
                this.paginationData.currentPage = page;
                this.$router.push({
                    name: 'PaginateTaxRates',
                    params: { page: page },
                    query: queries
                });

                this.fetchItems();
            },

            addTaxPayment() {
                this.$router.push('pay-tax');
            },

            onActionClick(action, row, index) {
                switch ( action ) {
                    case 'trash':
                        if ( confirm('Are you sure to delete?') ) {
                            HTTP.delete( this.url + '/' + row.id).then( response => {
                                this.$delete(this.rows, index);
                            });
                        }
                        break;

                    case 'edit':
                        this.showModal = true;
                        this.taxes = row;
                        break;

                    default :
                        break;
                }
            },

            onBulkAction(action, items) {
                if ( 'trash' === action ) {
                    if ( confirm('Are you sure to delete?') ) {
                        HTTP.delete('taxes/delete/' + items.join(',')).then(response => {
                            let toggleCheckbox = document.getElementsByClassName('column-cb')[0].childNodes[0];

                            if ( toggleCheckbox.checked ) {
                                // simulate click event to remove checked state
                                toggleCheckbox.click();
                            }

                            this.fetchItems();
                        });
                    }
                }
            },
        }
    }
</script>
<style lang="less">

</style>
