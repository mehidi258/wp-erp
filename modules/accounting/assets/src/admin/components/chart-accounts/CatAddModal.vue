<template>
    <div class="wperp-modal wperp-modal-open wperp-invoice-modal wperp-custom-scroll" role="dialog">
        <div class="wperp-modal-dialog" v-click-outside="outside" @click="inside">
            <div class="wperp-modal-content">
                <div class="wperp-modal-header">
                    <h4>{{ `${catData.title} Category` }}</h4>
                </div>
                <div class="wperp-modal-body">
                    <form action="" class="ledger-cat-form" @submit.prevent="saveCategory">

                        <div class="form-row" v-if="error">
                            <p class="error-message">{{ error }}</p>
                        </div>

                        <div class="form-row">
                            <label for="">Parent Category (optional)</label>
                            <treeselect v-model="parent"
                                :options="categories"
                                :disable-branch-nodes="true"
                                :show-count="true"
                                placeholder="Please select a category" />
                        </div>

                        <div class="form-row">
                            <label for="">Name of Category</label>

                            <input type="text" v-model="category" required>
                        </div>

                        <div class="wperp-modal-footer pt-0">
                            <div class="buttons-wrapper text-right">
                                <button class="wperp-btn btn--default modal-close" @click.prevent="outside">Cancel</button>
                                <button class="wperp-btn btn--primary" type="submit">
                                    <template v-if="catData.node">{{ isCatSaving ? 'Updating...' : 'Update' }}</template>
                                    <template v-else>{{ isCatSaving ? 'Saving...' : 'Save' }}</template>
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import HTTP from 'admin/http';
    import Treeselect from '@riophae/vue-treeselect';

    export default {
        name: 'CatAddModal',

        data() {
            return {
                error: false,
                parent: null,
                category: '',
                isCatSaving: false,
            }
        },

        props: {
            categories: {
                type: Array,
            },

            catData: {
                type: Object
            }
        },
        
        components: {
            Treeselect
        },

        created() {
            if ( this.catData.node ) {
                if ( this.catData.node.ancestors.length ) {
                    this.parent = this.catData.node.ancestors[0].id;
                }
                this.category = this.catData.node.label;
            }
        },

        methods: {
            inside() {},

            outside() {
                this.$root.$emit('cat-modal-close');
            },

            saveCategory() {
                this.error = false;
                this.isCatSaving = true;

                // Optimize later
                if ( this.catData.node ) {
                    // Updating
                    HTTP.put(`/ledgers/categories/${this.catData.node.id}`, {
                        parent: this.parent,
                        name: this.category
                    }).then(response => {
                        this.parent = null;
                        this.category = '';

                        this.$root.$emit('category-created');
                    }).catch((err) => {
                        this.category = '';
                        this.isCatSaving = false;
                        // Error message
                        this.error = err.response.data.message;
                        
                    }).then(() => {
                        this.isCatSaving = false;
                    });
                } else {
                    // Creating
                    HTTP.post('/ledgers/categories', {
                        parent: this.parent,
                        name: this.category
                    }).then(response => {
                        this.parent = null;
                        this.category = '';

                        this.$root.$emit('category-created');
                    }).catch((err) => {
                        this.category = '';
                        this.isCatSaving = false;
                        // Error message
                        this.error = err.response.data.message;
                        
                    }).then(() => {
                        this.isCatSaving = false;
                    });
                }
            }
        },

    }
</script>

<style lang="less" scoped>
    .ledger-cat-form {
        padding-top: 20px;
        min-height: 300px;

        .form-row {
            padding-bottom: 20px;
        }

        .buttons-wrapper {
            padding-top: 20px;
        }
    }
</style>
