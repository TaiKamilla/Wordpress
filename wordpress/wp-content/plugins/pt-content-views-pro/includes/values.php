<?php
/**
 * Define values for input, select...
 *
 * @package   PT_Content_Views_Pro
 * @author    PT Guy <http://www.contentviewspro.com/>
 * @license   GPL-2.0+
 * @link      http://www.contentviewspro.com/
 * @copyright 2014 PT Guy
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( 'PT_CV_Values_Pro' ) ) {

    /**
     * @name PT_CV_Values_Pro
     * @todo Define values for input, select...
     */
    class PT_CV_Values_Pro {

        /**
         * Get Bootstrap styles for thumbnail
         */
        static function field_thumbnail_styles() {
            // All available thumbnail sizes
            $result = array(
                'img-none'      => '(' . __( 'None' ) . ')',
                'img-rounded'   => __( 'Round edge', 'content-views-pro' ),
                'img-thumbnail' => __( 'Border', 'content-views-pro' ),
                'img-circle'    => __( 'Circle', 'content-views-pro' ),
                'img-shadow'    => __( 'Shadow', 'content-views-pro' ),
            );

            return $result;
        }

        static function auto_thumbnail() {
            $result = array(
                'image'       => __( 'Image (in post content)' ),
                'video-audio' => __( 'Video / Audio (in post content)', 'content-views-pro' ),
                'image-ctf'   => __( 'Image (in custom field)' ),
                'none'        => '(' . __( 'None' ) . ')',
            );

            return $result;
        }

        /**
         * Return quick filter options for Woocommerce
         */
        static function field_product_lists() {
            $result = array(
                'sale_products'         => __( 'Sale products', 'content-views-pro' ),
                'recent_products'       => __( 'Recent products', 'content-views-pro' ),
                'best_selling_products' => __( 'Best selling products', 'content-views-pro' ),
                'featured_products'     => __( 'Featured products', 'content-views-pro' ),
                'top_rated_products'    => __( 'Top rated products', 'content-views-pro' ),
                'out_of_stock'          => __( 'Out of stock products', 'content-views-pro' ),
                ''                      => __( 'None of above (use other settings below)', 'content-views-pro' ),
            );

            return $result;
        }

        /**
         * Pro View types
         *
         * @return array
         */
        static function view_type_pro() {
            $result = array(
                'pinterest'  => __( 'Pinterest', 'content-views-pro' ),
                'masonry'    => __( 'Masonry', 'content-views-pro' ),
                'timeline'   => __( 'Timeline', 'content-views-pro' ),
                'glossary'   => __( 'Glossary', 'content-views-pro' ),
                'one_others' => __( 'One and others', 'content-views-pro' ),
            );

            return $result;
        }

        /**
         * Pagination alignment options
         *
         * @return array
         */
        static function pagination_alignment() {

            $result = array(
                'left'   => __( 'Left' ),
                'center' => __( 'Center' ),
                'right'  => __( 'Right' ),
            );

            $result = apply_filters( PT_CV_PREFIX_ . 'pagination_alignment', $result );

            return $result;
        }

        /**
         * Font families
         *
         * @return array
         */
        static function font_families() {
            $fonts_data    = PT_CV_Functions_Pro::get_google_fonts();
            $font_families = array_keys( $fonts_data );

            $result                  = array();
            $result[ '' ]            = __( '- Default Font -', 'content-views-pro' );
            $result[ 'custom-font' ] = __( 'Custom font', 'content-views-pro' );

            foreach ( $font_families as $font ) {
                $result[ $font ] = $font;
            }

            return $result;
        }

        static function border_styles( $full_options = true ) {
            $extra = array(
                ''     => __( '(Default)', 'content-views-pro' ),
                'none' => __( 'None', 'content-views-pro' ),
            );

            $result = array(
                'solid'  => __( 'Solid', 'content-views-pro' ),
                'double' => __( 'Double', 'content-views-pro' ),
                'dotted' => __( 'Dotted', 'content-views-pro' ),
                'dashed' => __( 'Dashed', 'content-views-pro' ),
                'groove' => __( 'Groove', 'content-views-pro' ),
                'ridge'  => __( 'Ridge', 'content-views-pro' ),
                'inset'  => __( 'Inset', 'content-views-pro' ),
                'outset' => __( 'Outset', 'content-views-pro' ),
            );

            return $full_options ? array_merge( $extra, $result ) : $result;
        }

        /**
         * Text direction
         */
        static function text_direction() {
            $result = array(
                'ltr' => __( 'Left to Right', 'content-views-pro' ),
                'rtl' => __( 'Right to Left', 'content-views-pro' ),
            );

            return $result;
        }

        /**
         * Taxonomy filter position
         */
        static function taxonomy_filter_position() {
            $result = array(
                'left'   => __( 'Left' ),
                'center' => __( 'Center' ),
                'right'  => __( 'Right' ),
            );

            return $result;
        }

        static function scan_range() {
            $nums = array( '2', '4', '6', '8', '10', '20' );
            return array_combine( $nums, $nums );
        }

        /**
         * List of custom fields
         */
        static function custom_fields( $include_empty = false, $sort = false, $context = false ) {
            global $wpdb, $cv_admin_ctfs;

            if ( !empty( $cv_admin_ctfs ) ) {
                $keys = $cv_admin_ctfs;
            } else {
				$use_trans	 = PT_CV_Functions::get_option_value( 'cache_ctf_list', false );
				$keys		 = $use_trans ? get_transient( 'cvp_cached_ctf' ) : false;

				if ( false === $keys ) {
					$extra_exclude	 = " AND meta_key NOT LIKE '_crp_cache_%' "; /* contextual-related-posts plugin */
					$extra_exclude	.= " AND meta_key NOT LIKE '__wpdmkey_%' ";
					if ( cv_is_active_plugin( 'wp-automatic' ) ) {
						$extra_exclude .= " AND meta_key NOT REGEXP '^(_?)[a-f0-9]{32}$' ";
					}
					$keys			 = $wpdb->get_col(
					"SELECT DISTINCT meta_key
					FROM $wpdb->postmeta
					WHERE meta_key NOT LIKE '_oembed_%' $extra_exclude AND (meta_value NOT LIKE 'field_%' OR meta_value IS NULL)
					ORDER BY meta_key"
					); /* https://core.trac.wordpress.org/ticket/17210 */ /* https://dev.mysql.com/doc/refman/8.0/en/string-comparison-functions.html#operator_not-like */
					if ( $keys ) {
						natcasesort( $keys );
					}

					// Remove empty custom field
					$keys = array_filter( $keys );

					$cv_admin_ctfs = $keys;

					// store
					if ( $use_trans ) {
						set_transient( 'cvp_cached_ctf', $keys, 2 * DAY_IN_SECONDS );
					}
				}
			}

            // Final result
            $result  = $default = $include_empty ? array( '' => sprintf( '- %s -', __( 'Select' ) ) ) : array();
            foreach ( $keys as $key ) {
                /**
                 * Don't hide protected meta fields, to able to select data of The Events Calendar...
                 * @since 1.6.5
                 *
                  if ( is_protected_meta( $key, 'post' ) ) {
                  continue;
                  }
                 *
                 */

                $result[ esc_attr( $key ) ] = esc_html( $key );
            }

            // Prepend WooCommerce keys
            if ( cv_is_active_plugin( 'woocommerce' ) ) {
                $inserted = [
                    '_regular_price' => __( 'WooCommerce regular price', 'content-views-pro' ),
                    '_sale_price'    => __( 'WooCommerce sale price', 'content-views-pro' ),
                ];
                $result   = $default + $inserted + $result;
            }

            // Sort values of param by saved order
            if ( $sort ) {
                $result = apply_filters( PT_CV_PREFIX_ . 'settings_sort_single', $result, 'custom-fields-list' );
            }

            return apply_filters( PT_CV_PREFIX_ . 'custom_fields_list', $result, $context );
        }

        /**
         * Post date options
         */
        static function post_date() {
            $result = array(
                'today'            => __( 'Today' ),
                'week_ago'         => __( '1 week ago (to today)', 'content-views-pro' ),
                'from_today'       => __( 'Today and future', 'content-views-pro' ),
                'month_ago'        => __( '1 month ago (to today)', 'content-views-pro' ),
                'yesterday'        => __( 'Yesterday', 'content-views-pro' ),
                'year_ago'         => __( '1 year ago (to today)', 'content-views-pro' ),
                'today_in_history' => __( 'Today in history', 'content-views-pro' ),
                'in_the_past'      => __( 'In the past', 'content-views-pro' ),
                'custom_date'      => __( 'Custom date', 'content-views-pro' ),
                'this_week'        => __( 'This week', 'content-views-pro' ),
                'custom_time'      => __( 'Custom time (from &rarr; to)', 'content-views-pro' ),
                'this_month'       => __( 'This month', 'content-views-pro' ),
                'custom_year'      => __( 'Custom year', 'content-views-pro' ),
                'this_year'        => __( 'This year', 'content-views-pro' ),
                'custom_month'     => __( 'Custom month', 'content-views-pro' ),
            );

            return $result;
        }

        /**
         * Post align options
         */
        static function text_align( $default = false ) {
            $result = array(
                ''        => $default ? $default : __( '(Default)', 'content-views-pro' ),
                'left'    => __( 'Left' ),
                'right'   => __( 'Right' ),
                'center'  => __( 'Center' ),
                'justify' => __( 'Justify', 'content-views-pro' ),
            );

            return $result;
        }

        /**
         * Show what from parent page
         */
        static function parent_page_options() {
            $result = array(
                ''          => sprintf( '- %s -', __( 'Select' ) ),
                'children'  => __( 'Show its children', 'content-views-pro' ),
                'siblings'  => __( 'Show its siblings', 'content-views-pro' ),
                'child-sib' => __( 'Show its children & siblings', 'content-views-pro' ),
            );

            return $result;
        }

        /**
         * Show what from parent page
         */
        static function parent_page_info() {
            $result = array(
                ''           => '(' . __( 'None' ) . ')',
                'title'      => __( 'Title' ),
                'title_link' => __( 'Title & Link', 'content-views-pro' ),
            );

            return $result;
        }

        /**
         * Custom field types
         */
        static function custom_field_type() {
            $result = array(
                'CHAR'     => __( 'Text', 'content-views-pro' ),
                'NUMERIC'  => __( 'Number', 'content-views-pro' ),
                'DECIMAL'  => __( 'Decimal', 'content-views-pro' ),
                'DATE'     => __( 'Date' ),
                'DATETIME' => __( 'Date Time' ),
                'BINARY'   => __( 'True/False', 'content-views-pro' ),
            );

            return $result;
        }

        /**
         * Setting options for Sticky posts
         */
        static function sticky_posts() {
            $result = array(
                'default'     => __( 'Show in normal position', 'content-views-pro' ),
                'prepend'     => __( 'Show at the top', 'content-views-pro' ),
                'exclude'     => __( 'Exclude from output', 'content-views-pro' ),
                'prepend-all' => __( '[Custom] Show ALL sticky posts at the top', 'content-views-pro' ),
                'sticky-only' => __( '[Custom] Show ONLY sticky posts', 'content-views-pro' ),
            );

            return $result;
        }

        /**
         * List of social buttons
         */
        static function social_buttons() {
            $result = array(
                'facebook'   => __( 'Facebook', 'content-views-pro' ),
                'twitter'    => __( 'X (Twitter)', 'content-views-pro' ),
                'linkedin'   => __( 'Linkedin', 'content-views-pro' ),
                'pinterest'  => __( 'Pinterest', 'content-views-pro' ),
            );

            $result = apply_filters( PT_CV_PREFIX_ . 'social_buttons', $result );

            return $result;
        }

        /**
         * Animation effects for content
         * @return type
         */
        static function content_animation() {
            $result = array(
                ''          => __( 'Fade in', 'content-views-pro' ),
                'effect-lr' => __( 'Slide left right', 'content-views-pro' ),
                'effect-ud' => __( 'Slide up down', 'content-views-pro' ),
            );

            $result = apply_filters( PT_CV_PREFIX_ . 'content_animation', $result );

            return $result;
        }

        static function term_filter_custom() {
            $result = array(
                ''           => '(' . __( 'None' ) . ')',
                'as_output'  => __( 'Show terms as output', 'content-views-pro' ),
                'as_heading' => __( 'Show first selected term as heading of output', 'content-views-pro' ),
            );

            $result = apply_filters( PT_CV_PREFIX_ . 'term_filter_custom', $result );

            return $result;
        }

        /**
         * View format of Layout: One and others
         * @return type
         */
        static function view_format_one_and_others() {
            $label  = __( 'One post %s other posts', 'content-views-pro' );
            $icon   = '<code><span class="dashicons dashicons-arrow-%s-alt" style="margin-top: 4px;"></span>%s</code>';
            $result = array(
                '2' => sprintf( $label, sprintf( $icon, 'left', __( 'on left of', 'content-views-pro' ) ) ),
                '1' => sprintf( $label, sprintf( $icon, 'up', __( 'above of', 'content-views-pro' ) ) ),
            );

            return $result;
        }

        static function width_prop_one_and_others() {
            $result = array(
                '6-6' => '1 : 1',
                '8-4' => '2 : 1',
                '4-8' => '1 : 2',
            );

            return $result;
        }

        /**
         * Fields to display of other posts
         * @return type
         */
        static function one_others_fields() {
            $result = array(
                'thumbnail'            => __( 'Thumbnail' ),
                'title'                => __( 'Title' ),
                'meta-fields'          => __( 'Date' ),
                'meta-fields-taxonomy' => __( 'Taxonomy', 'content-views-query-and-display-post-page' ),
                'full-content'         => __( 'Full Content' ),
                'content'              => __( 'Excerpt' ),
                'readmore'             => __( 'Read More', 'content-views-query-and-display-post-page' ),
                'custom-fields'        => __( 'Custom Fields' ),
            );

            return $result;
        }

        /**
         * Option to display taxonomy
         * @return type
         */
        static function meta_field_taxonomy_display_what() {
            $result = array(
                ''            => __( 'Show all taxonomies of post', 'content-views-pro' ),
                'custom_taxo' => __( 'Let me choose', 'content-views-pro' ),
            );

            return $result;
        }

        static function meta_field_author_settings() {
            $result = array(
                ''              => __( 'Show name', 'content-views-pro' ),
                'author_avatar' => __( 'Show avatar', 'content-views-pro' ),
                'avatar_name'   => __( 'Show avatar & name', 'content-views-pro' ),
            );

            return $result;
        }

        static function mtf_date_formats() {
            $result = array(
                ''              => __( '(Default)', 'content-views-pro' ),
                'time_ago'      => __( 'Time ago', 'content-views-pro' ),
                'custom_format' => __( 'Custom', 'content-views-pro' ),
            );

            return $result;
        }

        static function manual_excerpt_settings() {
            $result = array(
                'yes'    => __( 'Use manual excerpt (trim & format)', 'content-views-pro' ),
                'origin' => __( 'Use manual excerpt (original)', 'content-views-pro' ),
                ''       => __( 'Ignore manual excerpt', 'content-views-pro' ),
            );

            return $result;
        }

        static function text_transform() {
            $result = array(
                ''           => __( '- Transform - ', 'content-views-pro' ),
                'capitalize' => __( 'Capitalize', 'content-views-pro' ),
                'uppercase'  => __( 'UPPERCASE', 'content-views-pro' ),
                'lowercase'  => __( 'lowercase', 'content-views-pro' ),
            );

            return $result;
        }

        static function excerpt_html_options() {
            $result = array(
                ''         => __( 'Strip all HTML tags', 'content-views-pro' ),
                'yes'      => __( 'Allow some HTML tags (a, br, strong, em, strike, i, ul, ol, li)', 'content-views-pro' ),
                'more-tags' => __( 'Allow more HTML tags (a, br, strong, em, strike, i, ul, ol, li, b, span, p)', 'content-views-pro' ),
                'all-tags' => __( 'Allow all HTML tags', 'content-views-pro' ),
            );

            return $result;
        }

        static function ctf__desc( $which ) {
            $arr = [
                'select' => __( 'A field can be selected when it was added to at least one post', 'content-views-pro' ),
                'name' => __( 'Separate names by comma. Leave empty for the field no need to change, for example: Custom name 1,,Custom name 3', 'content-views-pro' ),
                'datenew' => __( 'Set the new format here', 'content-views-pro' ),
                'dateold' => __( 'Set the current format here, if the converted result is wrong', 'content-views-pro' )
            ];
            return $arr[ $which ];
        }


		static function ctf_filter_operator_all() {
			return array(
				'TODAY'			 => 'Today',
				'NOW_PAST'		 => 'Now & Past',
				'NOW_FUTURE'	 => 'Now & Future',
				'IN_PAST'		 => 'In the past',
				'='				 => 'Equal ( = )',
				'!='			 => 'Differ ( != )',
				'>'				 => 'Greater ( > )',
				'>='			 => 'Greater or Equal ( >= )',
				'<'				 => 'Less ( < )',
				'<='			 => 'Less or Equal ( <= )',
				'LIKE'			 => 'Like',
				'NOT LIKE'		 => 'Not Like',
				'IN'			 => 'IN',
				'NOT IN'		 => 'Not IN',
				'BETWEEN'		 => 'Between',
				'NOT BETWEEN'	 => 'Not Between',
				'EXISTS'		 => 'Exists',
				'NOT EXISTS'	 => 'Not Exists',
			);
		}

		static function ctf_filter_operator_each() {
			return array(
				'CHAR'		 => array( '=', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE', 'EXISTS', 'NOT EXISTS' ),
				'NUMERIC'	 => array( '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS' ),
				'DECIMAL'	 => array( '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS' ),
				'DATE'		 => array( 'TODAY', 'NOW_PAST', 'NOW_FUTURE', 'IN_PAST', '=', '!=', '>', '>=', '<', '<=', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS' ),
				'DATETIME'	 => array( 'NOW_PAST', 'NOW_FUTURE', 'IN_PAST', '=', '!=', '>', '>=', '<', '<=', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS' ),
				'BINARY'	 => array( '=', '!=', 'EXISTS', 'NOT EXISTS' ),
			);
		}

		static function lf_types( $for_ctf = false ) {
			$extra_fields = $for_ctf ? array(
				'range_slider'	 => __( '(Numeric) Range Slider', 'content-views-pro' ),
				'date_range'	 => __( 'Date Range Picker', 'content-views-pro' ),
//				'breadcrumb'	 => __( 'Breadcrumb', 'content-views-pro' ),
//				'button'		 => __( 'Button', 'content-views-pro' ),
//				'search_field'	 => __( 'Text field', 'content-views-pro' ),
			) : array();

			return array_merge( array(
				'dropdown'	 => __( 'Dropdown', 'content-views-pro' ),
				'button'	 => __( 'Button', 'content-views-pro' ),
				'radio'		 => __( 'Radio', 'content-views-pro' ),
				'checkbox'	 => __( 'Checkbox', 'content-views-pro' ),
			), $extra_fields );
		}

		static function lf_behavior() {
			return array(
				'AND'	 => __( 'AND - show posts that match ALL selections', 'content-views-pro' ),
				'OR'	 => __( 'OR - show posts that match ANY selections', 'content-views-pro' ),
			);
		}
		static function lf_order_flag() {
			return array(
				''			 => __( 'Sort normally', 'content-views-pro' ),
				'yes'		 => __( 'Sort as strings case-insensitively', 'content-views-pro' ), /* backward compatible */
				'numsort'	 => __( 'Sort as numbers', 'content-views-pro' ),
			);
		}

		static function lf_orderby( $for_ctf = false ) {
			$txt_str		 = $for_ctf ? __( 'Label', 'content-views-pro' ) : __( 'Name', 'content-views-pro' );
			$val_str		 = $for_ctf ? __( 'Value', 'content-views-pro' ) : __( 'Slug', 'content-views-pro' );
			$extra_options	 = $for_ctf ? array() : array( 'termsin' => __( 'Manual selection of terms above', 'content-views-pro' ) );

			return array_merge( array(
				''					 => __( '(Default)', 'content-views-pro' ),
				'pcount_asc'		 => __( 'Posts count ↑', 'content-views-pro' ),
				'pcount_desc'		 => __( 'Posts count ↓', 'content-views-pro' ),
				'displaytext_asc'	 => $txt_str . ' ↑',
				'displaytext_desc'	 => $txt_str . ' ↓',
				'rawvalue_asc'		 => $val_str . ' ↑',
				'rawvalue_desc'		 => $val_str . ' ↓',
			), $extra_options );
		}

		static function lf_ctf_label() {
			return array(
				''			 => __( '(Value in database of this field)', 'content-views-pro' ),
				'postid'	 => __( 'Post title by ID number in value', 'content-views-pro' ),
				'termid'	 => __( 'Term name by ID number in value', 'content-views-pro' ),
				'authorid'	 => __( 'User name by ID number in value', 'content-views-pro' ),
				'acfchoices' => __( 'Label in Choices of this field (created by Advanced Custom Fields)', 'content-views-pro' ),
			);
		}

		static function lf_date_operator() {
			return array(
				'date-from'		 => __( 'From', 'content-views-pro' ),
				'date-to'		 => __( 'To', 'content-views-pro' ),
				'date-equal'	 => __( 'Exact', 'content-views-pro' ),
				'date-fromto'	 => __( 'From - To', 'content-views-pro' ),
			);
		}

		static function lf_thousand_separator() {
			return array(
				'space'	 => __( 'Space (1 000)', 'content-views-pro' ),
				'comma'	 => __( 'Comma (1,000)', 'content-views-pro' ),
				'dot'	 => __( 'Dot (1.000)', 'content-views-pro' ),
				'none'	 => __( 'None (1000)', 'content-views-pro' ),
			);
		}

		static function ad_positions() {
			return array(
				''		 => __( 'Random', 'content-views-pro' ),
				'manual' => __( 'Manual', 'content-views-pro' ),
			);
		}

		static function ad_desc1() {
			return __( 'Repeat below ads N times automatically. For example, you add 3 ads (A1, A2, A3) and set this value as 2, you will have total 3 x 2 = 6 ads (A1, A2, A3, A1, A2, A3).', 'content-views-pro' );
		}

		static function imgsub_role() {
			return array(
				''				 => __( 'when no featured image found', 'content-views-pro' ),
				'replacement'	 => __( 'to replace featured image', 'content-views-pro' ),
			);
		}

		static function current_author_options() {
			return array(
				''			 => __( '(Default)', 'content-views-pro' ),
				'include'	 => __( 'Show his/her posts', 'content-views-query-and-display-post-page' ),
				'exclude'	 => __( 'Hide his/her posts', 'content-views-query-and-display-post-page' ),
			);
		}

		// @since Hybrid
		static function hybrid_layouts( $full = true ) {
			// Correct the layouts
			$arr = [
				'grid1'		 => __( 'Grid 2', 'content-views-pro' ),
				'list1'		 => __( 'List', 'content-views-pro' ),
				'overlay1'	 => __( 'Overlay 1', 'content-views-pro' ),
				'overlay2'	 => __( 'Overlay 2', 'content-views-pro' ),
				'overlay3'	 => __( 'Overlay 3', 'content-views-pro' ),
				'overlay4'	 => __( 'Overlay 4', 'content-views-pro' ),
				'overlay5'	 => __( 'Overlay 5', 'content-views-pro' ),
				'overlay6'	 => __( 'Overlay 6', 'content-views-pro' ),
				'overlay7'	 => __( 'Overlay 7', 'content-views-pro' ),
				'overlay8'	 => __( 'Overlay 8', 'content-views-pro' ),
				'onebig1'	 => __( 'Big Post 1', 'content-views-pro' ),
				'onebig2'	 => __( 'Big Post 2', 'content-views-pro' ),
			];
			return $full ? $arr : array_keys( $arr );
		}


		// @since 6.4
		// [ operator1 => [ key1 => text1, key2 => text2, ] ]
		static function ctf_filter_operator_elementor() {
			$key_text_arr	 = self::ctf_filter_operator_all();
			$type_operator	 = self::ctf_filter_operator_each();
			$arr			 = [];
			foreach ( $type_operator as $type => $operators ) {
				$key_text = [];
				foreach ( $operators as $operator ) {
					$key_text[ $operator ] = $key_text_arr[ $operator ];
				}

				$arr[ $type ] = $key_text;
			}
			return $arr;
		}

	}

}