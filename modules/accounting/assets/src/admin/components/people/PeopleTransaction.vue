
<template>
    <div>
        <div class="content-header-section separator wperp-has-border-top">
            <div class="wperp-row wperp-between-xs">
                <div class="wperp-col">
                    <h2 class="content-header__title">Transactions</h2>
                </div>
                <div class="wperp-col">
                    <form class="wperp-form form--inline">
                        <div :class="['wperp-has-dropdown', {'dropdown-opened': showFilters}]">
                            <a class="wperp-btn btn--default dropdown-trigger filter-button" @click.prevent="toggleFilter">
                                <span><i class="flaticon-search-segment"></i>Filters</span>
                                <i class="flaticon-arrow-down-sign-to-navigate"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right wperp-filter-container">
                                <div class="wperp-panel wperp-panel-default wperp-filter-panel">
                                    <h3>Filter</h3>
                                    <div class="wperp-panel-body">
                                        <h3>Date</h3>
                                        <div class="form-fields">
                                            <div class="start-date has-addons">
                                                <datepicker v-model="filters.start_date"></datepicker>
                                                <span class="flaticon-calendar"></span>
                                            </div>
                                            <span class="label-to">To</span>
                                            <div class="end-date has-addons">
                                                <datepicker v-model="filters.end_date"></datepicker>
                                                <span class="flaticon-calendar"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="wperp-panel-footer">
                                        <input type="reset" value="Cancel" class="wperp-btn btn--default" @click="toggleFilter">
                                        <input type="submit" value="Submit" class="wperp-btn btn--primary" @click.prevent="filterList">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="wperp-import-wrapper display-inline-block">
                            <a class="wperp-btn btn--default" href="#" title="Import"><span class="flaticon-import"></span></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="wperp-transactions-section wperp-section">
            <div class="table-container">
                <list-table
                    tableClass="wperp-table table-striped table-dark widefat"
                    action-column="actions"
                    :columns="columns"
                    :rows="rows"
                    :actions="actions"
                    @action:click="onActionClick"
                    >
                </list-table>
            </div>
        </div>
    </div>
</template>

<script>
    import HTTP from 'admin/http'
    import ListTable from 'admin/components/list-table/ListTable.vue'
    import Datepicker from 'admin/components/base/Datepicker.vue';

    export default {
        name: 'PeopleTransaction',
        components: {
            ListTable,
            Datepicker
        },
        props: [ 'rows' ],

        data() {
            return {
                bulkActions: [
                    {
                        key: 'trash',
                        label: 'Move to Trash',
                        img: erp_acct_var.erp_assets + '/images/trash.png'
                    }
                ],
                columns: {
                    'voucher_no': { label: 'Voucher No' },
                    'amount': { label: 'Amount' },
                    'trn_date': { label: 'Transaction Date' },
                    'trn_by': { label: 'Transaction By' },
                    'particulars': { label: 'Particulars' },
                    'voucher_type': { label: 'Voucher Type' },
                    'actions': { label: '' }
                },
                actions : [
                    { key: 'edit', label: 'Edit' },
                    { key: 'trash', label: 'Delete' }
                ],
                showFilters: false,
                filters: {
                    start_date: '',
                    end_date: ''
                }
            }
        },

        methods: {
            toggleFilter() {
                this.showFilters = !this.showFilters;
            },

            filterList() {
                this.toggleFilter();
                this.$root.$emit( 'people-transaction-filter', this.filters );
            },

            onActionClick(action, row, index) {

                switch ( action ) {
                    case 'trash':
                        if ( confirm('Are you sure to delete?') ) {
                            this.$root.$emit( 'delete-transaction', row.id );
                        }
                        break;

                    case 'edit':
                        this.showModal = true;
                        this.people = row;
                        break;
                    default :
                    break;
                }
            },
        },
        created() {

        }
    }
</script>

<style lang="less">
    .open-dropdown-menu {
        visibility: visible !important;
        opacity: 1 !important;
    }
</style>