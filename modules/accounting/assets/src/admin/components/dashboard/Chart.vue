<template>
    <div class="income-expense-section wperp-panel wperp-panel-default">
        <div class="wperp-panel-heading wperp-bg-white"><h4>Income & Expense</h4></div>
        <div class="wperp-panel-body">
            <div class="wperp-custom-select wperp-custom-select--inline-block wperp-pull-right mb-20" v-if="showDropdown">
                <select name="query_time" class="wperp-form-field" id="att-filter-duration" v-model="chartRange" >
                    <!--<option value="this_month">This Month</option>-->
                    <!--<option value="last_month">Last Month</option>-->
                    <option value="this_quarter" v-if="thisQuarter.labels.length">This Quarter</option>
                    <option value="last_quarter" v-if="lastQuarter.labels.length">Last Quarter</option>
                    <option value="this_year">This Year</option>
                    <option value="last_year" v-if="lastYear.labels.length">Last Year</option>
                </select>
                <i class="flaticon-arrow-down-sign-to-navigate"></i>
            </div>

            <div class="wperp-chart-block">
                <canvas id="bar_chart" ref="bar_chart"></canvas>
            </div>
        </div>
    </div>
</template>

<script>
    import 'assets/js/plugins/chart.min'
    import HTTP from 'admin/http'

    export default {
        name: "Chart",
        components: {
            HTTP
        },

        data() {
            return {
                items: [],
                chart : '',
                chartRange : 'this_year',
                respData : '',
                showDropdown : false
            }
        },

        created() {
            this.fetchData();
        },

        mounted() {
            // this.createChart();
        },

        computed: {
            thisYear(){
                return {
                    labels : this.respData.thisYear.labels,
                    datasets : [
                        {
                            label: 'Income',
                            data: this.respData.thisYear.income,
                            backgroundColor: '#208DF8'
                        },
                        {
                            label: 'Expense',
                            data: this.respData.thisYear.expense,
                            backgroundColor: '#f86e2d'
                        }
                    ]
                }
            },

            lastYear(){
                return {
                    labels : this.respData.lastYear.labels,
                    datasets : [
                        {
                            label: 'Income',
                            data: this.respData.lastYear.income,
                            backgroundColor: '#208DF8'
                        },
                        {
                            label: 'Expense',
                            data: this.respData.lastYear.expense,
                            backgroundColor: '#f86e2d'
                        }
                    ]
                }
            },

            thisQuarter(){
                let quarter = this.getQuarterRange();
                let labels = this.thisYear.labels.slice(quarter.start, quarter.end+1);
                let newIncome = this.thisYear.datasets[0].data.slice(quarter.start, quarter.end+1);
                let newExpense = this.thisYear.datasets[1].data.slice(quarter.start, quarter.end+1);

                return {
                    labels : labels,
                    datasets : [
                        {
                            label: 'Income',
                            data: newIncome,
                            backgroundColor: '#208DF8'
                        },
                        {
                            label: 'Expense',
                            data: newExpense,
                            backgroundColor: '#f86e2d'
                        }
                    ]
                }
            },

            lastQuarter(){
                let quarter = this.getQuarterRange('previous');

                let yearData = this.thisYear.datasets;

                if ( quarter.start < 0 ) {
                    yearData = this.lastYear;
                    quarter.start = quarter.start + 12;
                    quarter.end = quarter.end + 12;
                }

                let labels = yearData.labels.slice(quarter.start, quarter.end+1);
                let newIncome = yearData.datasets[0].data.slice(quarter.start, quarter.end+1);
                let newExpense = yearData.datasets[1].data.slice(quarter.start, quarter.end+1);

                return {
                    labels : labels,
                    datasets : [
                        {
                            label: 'Income',
                            data: newIncome,
                            backgroundColor: '#208DF8'
                        },
                        {
                            label: 'Expense',
                            data: newExpense,
                            backgroundColor: '#f86e2d'
                        }
                    ]
                }
            },

        },

        methods: {
            createChart() {
                let dataChart = {
                        labels: this.thisYear.labels,
                        datasets: this.thisYear.datasets
                    },
                    config = {
                        type: 'bar',
                        data: dataChart,
                        options: {
                            maintainAspectRatio: true,
                            responsive: true,
                            legend: {
                                display: false
                            },
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true
                                    }
                                }]
                            }
                        }
                    };
                let bar_chart_ctx = this.$refs['bar_chart'].getContext("2d");
                this.chart = new Chart(bar_chart_ctx, config);
            },

            fetchData() {

                HTTP.get( 'transactions/income_expense_overview').then( (response) => {
                     this.respData = response.data;
                     this.createChart();
                     this.showDropdown = true;
                } );
            },

            updateRange(){
                let newlabels, newset;
                switch ( this.chartRange ){
                    case "last_year":
                        newlabels = this.lastYear.labels;
                        newset = this.lastYear.datasets;
                        break;
                    case "this_quarter":
                        newlabels = this.thisQuarter.labels;
                        newset = this.thisQuarter.datasets;
                        break;
                    case "last_quarter":
                        newlabels = this.lastQuarter.labels;
                        newset = this.lastQuarter.datasets;
                        break;

                    default:
                        newlabels = this.thisYear.labels;
                        newset = this.thisYear.datasets;
                        break;

                }
                //update labels
                this.chart.data.labels = newlabels;

                //update values
                this.chart.data.datasets = newset;

                //update Chart view
                this.chart.update();
            },

            getQuarterRange( val = 'current' ) {
                let today = new Date(),
                    quarter = Math.floor((today.getMonth() / 3)),
                    startDate = new Date(today.getFullYear(), quarter * 3, 1);

                let endDate = new Date(startDate.getFullYear(), startDate.getMonth() + 3, 0);
                if( val === 'current' ){
                    return { 'start' : startDate.getMonth(), 'end' : endDate.getMonth() }
                }

                return { 'start' : startDate.getMonth() - 3, 'end' : endDate.getMonth() - 3 }
            }

        },

        watch: {
            chartRange(){
                this.updateRange();
            }
        }
    }
</script>

<style scoped>

</style>
