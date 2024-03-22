<?php namespace Wc1c\Wc\Products;

defined('ABSPATH') || exit;

use WC_Product_Factory;
use Wc1c\Wc\Contracts\ProductContract;

/**
 * Factory
 *
 * @package Wc1c\Wc
 */
class Factory extends WC_Product_Factory
{
	/**
	 * @var bool
	 */
	protected $init = false;

	/**
	 * Get a product.
	 *
	 * @param mixed $product_id int $product Product identifier.
	 *
	 * @return ProductContract|bool Product object or null if the product cannot be loaded.
	 */
	public function getProduct($product_id)
	{
		return $this->get_product($product_id);
	}

	/**
	 * Create a WC1C coding standards compliant class names.
	 *
	 * @param string $product_type Product type.
	 *
	 * @return string|false
	 */
	public function getClassnameFromProductType($product_type)
	{
		return self::get_classname_from_product_type($product_type);
	}

	/**
	 * Create a WC1C coding standards compliant class name.
	 *
	 * @param string $product_type Product type.
	 *
	 * @return string|false
	 */
	public static function get_classname_from_product_type($product_type)
	{
		switch($product_type)
		{
			case 'simple':
				return SimpleProduct::class;
			case 'variation':
				return VariationVariableProduct::class;
			case 'variable':
				return VariableProduct::class;
			default:
				return $product_type ? 'WC_Product_' . implode( '_', array_map( 'ucfirst', explode( '-', $product_type ) ) ) : false;
		}
	}

	/**
	 * Gets a product classname and allows filtering. Returns SimpleProduct::class if the class does not exist.
	 *
	 * @param int $product_id Product ID.
	 * @param string $product_type Product type.
	 *
	 * @return string
	 */
	public static function get_product_classname($product_id, $product_type)
	{
		$classname = apply_filters('woocommerce_product_class', self::get_classname_from_product_type($product_type), $product_type, 'variation' === $product_type ? 'product_variation' : 'product', $product_id);

		if(!$classname || !class_exists($classname))
		{
			$classname = SimpleProduct::class;
		}

		return $classname;
	}

	/**
	 * Получение продукта или продуктов по идентификатору продукта из 1С
	 *
	 * @param string $id
	 *
	 * @return bool|ProductContract
	 */
	public function getProductByExternalId($id)
	{
		$product_id = $this->findProductIdsByExternalId($id);

		if(0 === $product_id)
		{
			return false;
		}

		return $this->getProduct($product_id);
	}

	/**
	 * Получение идентификатора(ов) продуктов по идентификатору продукта из 1C
	 *
	 * @param int|string $external_id
	 *
	 * @return int|array
	 */
	public function findProductIdsByExternalId($external_id)
	{
		$this->maybe_init();

		$args =
		[
			'_wc1c_external_id' => $external_id,
			'limit' => -1,
			'return' => 'ids',
			'post_status' => implode(',', array_merge(array_keys(get_post_statuses()), ['trash'])),
		];

		$products = wc_get_products($args);

		if(empty($products))
		{
			return 0;
		}

		if(count($products) === 1)
		{
			return reset($products);
		}

		return $products;
	}

	/**
	 * Получение продукта или продуктов по наименованию продукта из WooCommerce
	 *
	 * @param string $name Наименование искомого продукта
	 *
	 * @return false|ProductContract
	 */
	public function getProductByName($name)
	{
		$product_id = $this->findProductIdByName($name);

		if(0 === $product_id)
		{
			return false;
		}

		return $this->getProduct($product_id);
	}

	/**
	 * Получение идентификатора товара по наименованию товара из каталога
	 * - возвращаются простые товары, а так же вариации
	 *
	 * @param string $name
	 *
	 * @return int
	 */
	public function findProductIdByName($name)
	{
		$args =
		[
			'post_type' => ['product', 'product_variation'],
			'post_status' => implode(',', array_merge(array_keys(get_post_statuses()), ['trash'])),
			'title' => $name,
			'posts_per_page' => -1,
			'fields' => 'ids'
		];

		$posts = get_posts($args);
		$product_id = 0;

		if(is_array($posts) && count($posts) === 1)
		{
			$product_id = reset($posts);
		}

		return $product_id;
	}

	/**
	 * Получение идентификатора(ов) продукта(ов) по внешнему идентификатору с возможным указанием характеристики
	 *
	 * @param int|string $external_id Внешний идентификатор продукта
	 * @param int|string $external_characteristic_id Внешний идентификатор характеристики продукта
	 *
	 * @return int|array
	 *
	 * @since 0.8.0
	 */
	public function findIdsByExternalIdAndCharacteristicId($external_id, $external_characteristic_id = '')
	{
		$this->maybe_init();

		$args =
		[
			'_wc1c_external_id' => $external_id,
			'_wc1c_external_characteristic_id' => $external_characteristic_id,
			'limit' => -1,
			'return' => 'ids',
			'type' => array_merge(array_keys(wc_get_product_types()), ['product', 'variation']),
			'post_status' => implode(',', array_merge(array_keys(get_post_statuses()), ['trash'])),
		];

		$products = wc_get_products($args);

		if(empty($products))
		{
			return 0;
		}

		if(count($products) === 1)
		{
			return reset($products);
		}

		return $products;
	}

	/**
	 * Handle a custom 'customvar' query var to get products with the 'customvar' meta.
	 *
	 * @param array $query - Args for WP_Query.
	 * @param array $query_vars - Query vars from WC_Product_Query.
	 *
	 * @return array modified $query
	 */
	public function handleCustomQueryVar($query, $query_vars)
	{
		$query['meta_query'] = [];

		/**
		 * Поиск по внешнему коду и характеристике
		 */
		if(!empty($query_vars['_wc1c_external_id']) && !empty($query_vars['_wc1c_external_characteristic_id']))
		{
			$query['meta_query'][] =
			[
				[
					'relation' => 'AND',
					[
						'key' => '_wc1c_external_id',
						'value' => $query_vars['_wc1c_external_id'],
						'compare' => '=',
					],
					[
						'key' => '_wc1c_external_characteristic_id',
						'value' => $query_vars['_wc1c_external_characteristic_id'],
						'compare' => '=',
					]
				]
			];
		}

		/**
		 * Поиск по внешнему коду с отсутствием характеристики
		 */
		if(!empty($query_vars['_wc1c_external_id']) && empty($query_vars['_wc1c_external_characteristic_id']))
		{
			$query['meta_query'][] =
			[
				[
					'relation' => 'AND',
					[
						'key' => '_wc1c_external_id',
						'value' => $query_vars['_wc1c_external_id'],
						'compare' => '=',
					],
					[
						'key' => '_wc1c_external_characteristic_id',
						'value' => '',
						'compare' => 'NOT EXISTS',
					]
				]
			];
		}

		return $query;
	}

	/**
	 * @return void
	 */
	public function maybe_init()
	{
		if($this->init)
		{
			return;
		}

		$this->init = true;
		add_filter('woocommerce_product_data_store_cpt_get_products_query', [$this, 'handleCustomQueryVar'], 10, 2);
	}

    /**
     * Get a product.
     *
     * @param mixed $product_id WC_Product|WP_Post|int|bool $product Product instance, post instance, numeric or false to use global $post.
     * @param array $deprecated Previously used to pass arguments to the factory, e.g. to force a type.
     * @return \WC_Product|bool Product object or false if the product cannot be loaded.
     */
    public function get_product( $product_id = false, $deprecated = array() )
    {
        $product_id = $this->get_product_id( $product_id );

        if ( ! $product_id ) {
            return false;
        }

        $product_type = $this->get_product_type( $product_id );

        // Backwards compatibility.
        if ( ! empty( $deprecated ) )
        {
            wc_deprecated_argument( 'args', '3.0', 'Passing args to the product factory is deprecated. If you need to force a type, construct the product class directly.' );

            if ( isset( $deprecated['product_type'] ) ) {
                $product_type = $this->get_classname_from_product_type( $deprecated['product_type'] );
            }
        }

        $classname = $this->get_product_classname( $product_id, $product_type );

        try
        {
            return new $classname($product_id, $deprecated);
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    /**
     * Get the product ID depending on what was passed.
     *
     * @param  \WC_Product|\WP_Post|int|bool $product Product instance, post instance, numeric or false to use global $post.
     * @return int|bool false on failure
     */
    private function get_product_id( $product )
    {
        global $post;

        if ( false === $product && isset( $post, $post->ID ) && 'product' === get_post_type( $post->ID ) )
        {
            return absint( $post->ID );
        }
        elseif ( is_numeric( $product ) )
        {
            return $product;
        }
        elseif ( $product instanceof \WC_Product )
        {
            return $product->get_id();
        } elseif ( ! empty( $product->ID ) ) {
            return $product->ID;
        } else {
            return false;
        }
    }
}