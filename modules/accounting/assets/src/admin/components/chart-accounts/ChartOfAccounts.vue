<template>
    <div class="wperp-container chart-accounts">
        <div class="content-header-section separator">
            <div class="wperp-row wperp-between-xs">
                <div class="wperp-col">
                    <h2 class="content-header__title">
                        Chart of Accounts
                        <router-link class="wperp-btn btn--primary" :to="{ name: 'AddChartAccounts'}">Add New</router-link>
                    </h2>
                </div>
            </div>
        </div>

        <ul>
            <li :key="index" v-for="(chart, index) in chartAccounts">
                <h3>{{ chart.label }}</h3>

                <list-table
                    tableClass="wperp-table table-striped table-dark widefat table2 chart-list"
                    action-column="actions"
                    :columns="columns"
                    :actions="actions"
                    :rows="ledgers[parseInt(chart.id)]"
                    @action:click="onActionClick">
                    <template slot="ledger_name" slot-scope="data">
                        <strong>{{ data.row.name }}</strong>
                    </template>
                    <template slot="row-actions" slot-scope="data" v-if="data.row.system != null">
                        <strong class="sys-acc">System</strong>
                    </template>
                </list-table>
            </li>
        </ul>
    </div>
</template>

<script>
    import HTTP from 'admin/http'
    import ListTable from 'admin/components/list-table/ListTable.vue'

    export default {
        name: 'ChartAccounts',

        data() {
            return {
                columns: {
                    'code': {label: 'Code'},
                    'ledger_name': {label: 'Name'},
                    'type': {label: 'Type'},
                    'actions': {label: 'Actions'},
                },
                actions : [
                    { key: 'edit', label: 'Edit' },
                    { key: 'trash', label: 'Delete' }
                ],

                chartAccounts: [],
                ledgers: [],
            }
        },

        components: {
            ListTable
        },

        created() {
            this.fetchChartAccounts();
        },

        methods: {
            groupBy(arr, fn) { /* https://30secondsofcode.org/ */
                return arr.map(typeof fn === 'function' ? fn : val => val[fn]).reduce((acc, val, i) => {
                    acc[val] = (acc[val] || []).concat(arr[i]);
                    return acc;
                }, {})
            },
                
            fetchChartAccounts() {
                this.chartAccounts = [];

                HTTP.get('/ledgers/accounts').then( response => {
                    this.chartAccounts = response.data;

                    this.fetchLedgers();
                });
            },

            fetchLedgers() {
                HTTP.get('/ledgers').then( response => {
                    this.ledgers = this.groupBy(response.data, 'chart_id');
                });
            },

            onActionClick(action, row, index) {

                switch ( action ) {
                    case 'trash':
                        if ( confirm('Are you sure to delete?') ) {
                            HTTP.delete(`/ledgers/${row.id}`).then( response => {
                                this.fetchChartAccounts();
                            });
                        }
                        break;

                    case 'edit':
                        this.$router.push({name: 'AddChartAccounts', params: { row: row } });
                        break;

                    default :

                }
            },

        }
    }
</script>

<style lang="less">
    .chart-accounts {
        .tablenav,
        .column-cb,
        .check-column {
            display: none;
        }

        li {
            margin-bottom: 20px;
        }

        .chart-list {
            .sys-acc {
                color: #ff6f00;
            }

            thead,
            tfoot {
                width: 25%;

                th:last-child {
                    text-align: right;
                }
            }
        }

        .test-seed {
            font-size: 14px;
            cursor: pointer;
            margin-left: 20px;
        }
    }
</style>
