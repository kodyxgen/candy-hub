<!--
  Product Edit
  This component is responsible for displaying the product edit page.
 -->
<script>
    export default {
        data() {
            return {
                loaded: false,
                product: {},
                attribute_groups: [],
                purchasable: true,
                viewable: true,
                variants: [],
                routes: [],
                languages: []
            }
        },
        props: {
            productId: {
                type: String,
                required: true
            }
        },
        created() {
            this.loadLanguages();
            this.loadProduct(this.productId);
        },
        mounted() {
            CandyEvent.$on('product-updated', event => {
                this.loaded = false;
                this.loadProduct(this.productId);
            });
            CandyEvent.$on('product_visibility', visible => {
                this.viewable = visible;
            });
            CandyEvent.$on('product_purchasable', purchasable => {
                this.purchasable = purchasable;
            });
        },
        methods: {
            /**
             * Decorates the data ready for the template to use
             * @param  {Object} data
             * @return
             */
            decorate(data) {
                // this.attribute_groups = data.attribute_groups.data;

                // Get all groups associated to this product.
                let groups = [];

                _.each(data.attributes.data, attribute => {
                    let exists = _.find(groups, group => {
                        return group.handle == attribute.group.data.handle;
                    });
                    if (attribute.group && !exists) {
                        groups.push(attribute.group.data);
                    }
                });


                _.each(data.family.data.attributes.data, attribute => {
                    // // Find the attribute
                    let existing = _.find(groups, group => {
                        return group.handle == attribute.group.data.handle;
                    });

                    if (!existing) {
                        let group = attribute.group.data;
                        this.$set(group, 'attributes', {
                            data: [],
                        });
                        group.attributes.data.push(attribute);
                        groups.push(group);
                    } else {
                        let attributes = existing.attributes.data;
                        // If the attribute doesn't exist, then we add it.
                        let existingAttribute = _.find(attributes, att => {
                            return att.handle == attribute.handle;
                        });
                        if (!existingAttribute) {
                            existing.attributes.data.push(attribute);
                        }

                        if (!data.attribute_data[attribute.handle]) {
                            this.$set(data.attribute_data, attribute.handle, {
                                webstore: {
                                    en: ""
                                }
                            });
                        }
                    }
                });

                groups = _.orderBy(groups, 'position', 'asc');

                this.attribute_groups = groups;

                this.product = data;
                // this.product.attributes = this.product.attribute_data;
                this.variants = this.product.variants.data;
                this.routes = this.product.routes.data;
            },
            /**
             * Loads languages
             * @return
             */
            loadLanguages() {
                apiRequest.send('get', 'languages', [], []).then(response => {
                    response.data.forEach(lang => {
                        this.languages.push({
                            label: lang.name,
                            value: lang.lang,
                            content: '<span class=\'flag-icon flag-icon-' + lang.iso + '\'></span> ' + lang.name
                        });
                    });
                });
            },
            /**
             * Loads the product by its encoded ID
             * @param  {String} id
             */
            loadProduct(id) {
                apiRequest.send('get', '/products/' + this.productId, {}, {
                    excl_tax: true,
                    full_response: true,
                    option_data: true,
                    includes: 'family.attributes.group,attributes.group.attributes,variants.customerPricing.tax,variants.customerPricing.group,variants.tiers.group,variants.tax,assets.transforms,assets.tags,' +
                    'layout,associations.group,associations.association.assets.transforms,routes,channels,customerGroups,categories.assets.transforms,categories.routes,collections,collections.routes,variants.image.transforms'
                }).then(response => {
                    this.decorate(response.data);
                    this.loaded = true;
                    CandyEvent.$emit('title-changed', {
                        title: this.product
                    });
                    document.title = this.$options.filters.attribute(this.product, 'name') + ' Product - GetCandy';
                }).catch(error => {
                    CandyEvent.$emit('notification', {
                        level: 'error',
                        message: error.message
                    });
                });
            },
            getCategoryCount() {
                return this.product.categories.data.length;
            },
            getAssociationCount() {
                return this.product.associations.data.length;
            },
            getComponents(section) {
                return CandyHub.getComponents(section);
            }
        },
        computed: {
            locale() {
                return locale.current();
            },
            channel() {
                return this.$store.getters.getDefaultChannel.handle;
            }
        }
    }
</script>

<template>
    <div>
        <template v-if="loaded">
            <div class="alert alert-warning" v-if="!purchasable"><i class="fa fa-exclamation-triangle"
                                                                    aria-hidden="true"></i>
                This product cannot be purchased - <a href="#">click here to fix this</a></div>
            <div class="alert alert-danger" v-if="!viewable">
                <i class="fa fa-exclamation-triangle" aria-hidden="true"></i> This product cannot be viewed -
                <a href="#">click here to fix this</a>
            </div>

            <transition name="fade">
                <candy-tabs initial="productdetails">

                    <candy-tab name="Product Details" :selected="true" dispatch="product-details">
                        <candy-tabs nested="true">
                            <candy-tab v-for="(group, index) in attribute_groups" :name="group.name" :handle="group.id" :key="group.id" :selected="index == 0 ? true : false" dispatch="product-details">
                                <candy-product-details :product="product" :languages="languages"
                                                       :group="group">
                                </candy-product-details>
                            </candy-tab>
                        </candy-tabs>
                    </candy-tab>

                    <candy-tab name="Media" dispatch="save-media">
                        <candy-media
                            assetable="products"
                            :parent="product">
                        </candy-media>
                    </candy-tab>

                    <candy-tab
                        name="Availability &amp; Pricing"
                        handle="product-availability"
                        dispatch="product-variants"
                    >
                        <candy-product-availability :variants="variants" :product="product" :languages="languages"
                                                    v-if="product"></candy-product-availability>
                    </candy-tab>

                    <candy-tab name="Associations">
                        <candy-tabs nested="true">
                            <candy-tab name="Categories" handle="categories" :selected="true" :badge="getCategoryCount()">
                                <candy-categories :product="product"></candy-categories>
                            </candy-tab>
                            <candy-tab name="Collections" handle="collections">
                                <candy-product-collections :product-id="product.id" :existing="product.collections.data"></candy-product-collections>
                            </candy-tab>
                            <candy-tab name="Products" handle="products" :badge="getAssociationCount()" dispatch="product-associations">
                                <candy-products :product="product"></candy-products>
                            </candy-tab>
                            <candy-tab :name="component.tabLabel" :handle="component.reference" :dispatch="component.reference" v-for="(component, index) in getComponents('catalogue-manager.product.associations')" :key="index">
                                <component :is="component.reference" :product="product" />
                            </candy-tab>
                        </candy-tabs>
                    </candy-tab>
                    <candy-tab name="URLS" dispatch="save-urls">
                        <candy-tabs nested="true">
                            <candy-tab name="Locale URLS" handle="locale-urls" :selected="true" dispatch="save-urls">
                                <candy-urls :languages="languages" :routes="routes" :model="product" endpoint="products"></candy-urls>
                            </candy-tab>
                            <candy-tab name="Redirects" handle="redirects" dispatch="product-details">
                                <candy-redirects :model="product" endpoint="products" :routes="routes"></candy-redirects>
                            </candy-tab>
                        </candy-tabs>
                    </candy-tab>

                </candy-tabs>
            </transition>
        </template>

        <div v-else>
            <div class="page-loading loading">
                <span><i class="fa fa-sync fa-spin fa-3x fa-fw"></i></span> <strong>Loading</strong>
            </div>
        </div>
    </div>

</template>

<style lang="scss" scoped>
    .fade-enter-active, .fade-leave-active {
        transition: opacity 1s;
    }

    .fade-enter, .fade-leave-to /* .fade-leave-active in <2.1.8 */
    {
        opacity: 0;
    }
</style>
