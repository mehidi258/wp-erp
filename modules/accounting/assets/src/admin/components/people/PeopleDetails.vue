<template>
    <div class="wperp-container">
        <!-- Start .header-section -->
        <UserBasicInfo :userData="resData"></UserBasicInfo>
        <!-- End .header-section -->
        <div class="wperp-stats wperp-section">
            <div class="wperp-panel wperp-panel-default">
                <div class="wperp-panel-body">
                    <div class="wperp-row">
                        <div class="wperp-col-sm-4">
                            <pie-chart
                                id="payment"
                                :sign="getCurrencySign()"
                                :title="paymentChart.title"
                                :labels="paymentChart.labels"
                                :colors="paymentChart.colors"
                                :data="paymentChart.data">
                            </pie-chart>
                        </div>
                        <div class="wperp-col-sm-4">
                            <pie-chart id="status" :title="statusChart.title" :labels="statusChart.labels" :colors="statusChart.colors" :data="statusChart.data"></pie-chart>
                        </div>
                        <div class="wperp-col-sm-4">
                            <div class="wperp-chart-block">
                                <h3>Outstanding</h3>
                                <div class="wperp-total"><h2>$20000,00</h2></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <people-transaction :rows="transactions"></people-transaction>
    </div>
</template>

<script>
    import HTTP from 'admin/http'
    import UserBasicInfo from 'admin/components/userinfo/UserBasic.vue'
    import PieChart from 'admin/components/chart/PieChart.vue'
    import PeopleTransaction from 'admin/components/people/PeopleTransaction.vue'

    export default {
        name: 'PeopleDetails',
        components: {
            UserBasicInfo,
            PieChart,
            PeopleTransaction
        },

        data(){
            return {
                userId : '',
                resData: {},
                userData : {
                    'id': '',
                    'name': '************',
                    'email': '************',
                    // 'img_url': erp_acct_var.acct_assets  + '/images/dummy-user.png',
                    'meta': {
                        'company': '**********',
                        'website': '**********',
                        'phone': '**********',
                        'mobile': '*************',
                        'address': '*********',
                    }
                },
                url: '',
                paymentChart: {
                    title: 'Payment',
                    labels: ['Recieved', 'Outstanding'],
                    colors: ['#55D8FE', '#FF8373'],
                    data: [794, 458],
                },
                statusChart: {
                    title: 'Status',
                    labels: ['Paid', 'Overdue', 'Partial', 'Draft'],
                    colors: ['#208DF8', '#E9485E', '#FF9900', '#2DCB67'],
                    data: [2, 1, 2, 3],
                },

                transactions: [],
            }
        },

        created(){
            this.url = this.$route.params.route;
            this.userId = this.$route.params.id;
            if ( typeof this.url === 'undefined' ) {
                this.url = this.$route.path.split('/')[1];
            }
            this.fetchItem( this.userId );
            this.getTransactions();
            this.$root.$on( 'people-transaction-filter', filter => {
                this.filterTransaction( filter );
            } );
        },

        computed: {

        },

        methods: {
            fetchItem( id ) {
                HTTP.get( this.url+'/'+id, {
                    params: {}
                })
                    .then((response) => {
                        this.resData = response.data;
                    })
                    .catch((error) => {
                        console.log(error);
                    })
                    .then(() => {
                        //ready
                    });
            },
            getTransactions() {
                HTTP.get( this.url + '/' + this.userId + '/transactions' ).then( res => {
                    this.transactions = res.data;
                } );
            },
            filterTransaction( filters = {} ) {
                HTTP.get('customers/' + this.userId + '/transactions/filter', {
                    params: {
                        start_date: filters.start_date,
                        end_date: filters.end_date
                    }
                } ).then( res => {
                    this.transactions = res.data;
                } );
            }
        }
    }
</script>

<style lang="less">

</style>
