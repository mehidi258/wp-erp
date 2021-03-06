<template>

    <div class="wperp-transactions-section wperp-section">

        <!-- Start .wperp-crm-table -->
        <div class="table-container">
            <div class="bulk-action">
                <a href="#"><i class="flaticon-trash"></i>Trash</a>
                <a href="#" class="dismiss-bulk-action"><i class="flaticon-close"></i></a>
            </div>

            <list-table
                tableClass="wperp-table table-striped table-dark widefat table2 transactions-table"
                action-column="actions"
                :columns="columns"
                :rows="rows"
                :total-items="paginationData.totalItems"
                :total-pages="paginationData.totalPages"
                :per-page="paginationData.perPage"
                :current-page="paginationData.currentPage"
                @pagination="goToPage"
                :actions="actions"
                @action:click="onActionClick">
                <template slot="trn_no" slot-scope="data">
                    <strong>
                        <router-link :to="{ name: 'SalesSingle', params: { 
                            id: data.row.id,
                            type: isPayment(data.row) ? 'payment' : 'sales_invoice'
                        }}">
                            #{{ data.row.id }}
                        </router-link>
                    </strong>
                </template>
                <template slot="type" slot-scope="data">
                    {{ isPayment(data.row) ? 'Payment' : 'Invoice' }}
                </template>
                <template slot="ref" slot-scope="data">
                    {{ data.row.ref ? data.row.ref : '-' }}
                </template>
                <template slot="customer_name" slot-scope="data">
                    {{ isPayment(data.row) ? data.row.pay_cus_name : data.row.inv_cus_name }}
                </template>
                <template slot="trn_date" slot-scope="data">
                    {{ isPayment(data.row) ? data.row.payment_trn_date : data.row.invoice_trn_date }}
                </template>
                <template slot="due_date" slot-scope="data">
                    {{ isPayment(data.row) ? '-' : data.row.due_date }}
                </template>
                <template slot="due" slot-scope="data">
                    {{ isPayment(data.row) ? '-' : formatAmount(data.row.due) }}
                </template>
                <template slot="amount" slot-scope="data">
                    {{ isPayment(data.row) ? formatAmount(data.row.payment_amount) : formatAmount(data.row.sales_amount) }}
                </template>
                <template slot="status" slot-scope="data">
                    {{ isPayment(data.row) ? 'Received' : data.row.status }}
                </template>

            </list-table>

        </div>
    </div>
</template>

<script>
    import HTTP from 'admin/http';
    import ListTable from 'admin/components/list-table/ListTable.vue';

    export default {
        name: 'SalesList',

        components: {
            ListTable,
        },

        data() {
            return {
                columns: {
                    'trn_no':        {label: 'Voucher No.'},
                    'type':          {label: 'Type'},
                    'ref':           {label: 'Ref'},
                    'customer_name': {label: 'Customer'},
                    'trn_date':      {label: 'Trn Date'},
                    'due_date':      {label: 'Due Date'},
                    'due':           {label: 'Due'},
                    'amount':        {label: 'Total'},
                    'status':        {label: 'Status'},
                    'actions':       {label: ''},
                    
                },
                rows: [],
                paginationData: {
                    totalItems: 0,
                    totalPages: 0,
                    perPage: 10,
                    currentPage: this.$route.params.page === undefined ? 1 : parseInt(this.$route.params.page)
                },
                actions : [
                    { key: 'edit', label: 'Edit' },
                    { key: 'trash', label: 'Delete' }
                ]
            };
        },

        created() {
            this.$root.$on('transactions-filter', filters => {
                this.$router.push({ path: '/transactions/sales', query: { start: filters.start_date, end: filters.end_date } });
                this.fetchItems(filters);
            });

            let filters = {};
            
            // Get start & end date from url on page load
            if ( this.$route.query.start && this.$route.query.end ) {
                filters.start_date = this.$route.query.start;
                filters.end_date = this.$route.query.end;
            }

            this.fetchItems(filters);
        },

        watch: {
            '$route': 'fetchItems'
        },

        methods: {
            fetchItems(filters = {}) {                
                this.rows = [];

                HTTP.get('/transactions/sales', {
                    params: {
                        per_page: this.paginationData.perPage,
                        page: this.$route.params.page === undefined ? this.paginationData.currentPage : this.$route.params.page,
                        start_date: filters.start_date,
                        end_date: filters.end_date
                    }
                }).then( (response) => {                    
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

            onActionClick(action, row, index) {
                switch ( action ) {
                    case 'trash':
                        if ( confirm('Are you sure to delete?') ) {
                            HTTP.delete('invoices/' + row.id).then( response => {
                                this.$delete(this.rows, index);
                            });
                        }
                        break;

                    case 'edit':
                        if ( 'sales_invoice' == row.type ) {
                            this.$router.push({ name: 'InvoiceEdit', params: { id: row.id } })
                        }
                        
                        break;

                    default :

                }
            },

            goToPage(page) {
                let queries = Object.assign({}, this.$route.query);
                this.paginationData.currentPage = page;
                this.$router.push({
                    name: 'PaginateInvoices',
                    params: { page: page },
                    query: queries
                });

                this.fetchItems();
            },

            isPayment(row) {
                return row.type === 'payment' ? true : false;
            }
        },

    }
</script>

<style lang="less">
    .transactions-table {
        .tablenav,
        .column-cb,
        .check-column {
            display: none;
        }
    }
</style>

