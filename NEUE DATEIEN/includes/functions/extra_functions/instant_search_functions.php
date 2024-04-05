<?php
/**
 * @package  Instant Search Plugin for Zen Cart German
 * @author   marco-pm
 * @version  4.0.3
 * @see      https://github.com/marco-pm/zencart_instantsearch
 * @license  GNU Public License V2.0
 * modified for Zen Cart German
 * 2024-04-05 webchills
 */

/**
 * Returns the number of (enabled) products per manufacturer.
 *
 * @param int $manufacturers_id Manufacturer's id
 *
 * @return int Products count
 */
function zen_count_products_for_manufacturer(int $manufacturers_id): int
{
    global $db;

    $products = $db->Execute("
        SELECT COUNT(products_id) AS total
        FROM " . TABLE_PRODUCTS . "
        WHERE manufacturers_id = " . $manufacturers_id . "
        AND products_status = 1
    ");

    return (int)$products->fields['total'];
}
/**
 * Returns the display price without vatAddon for instant search results.
 * If you really want to display the vatAddon notice at every price in the ajax search results remove this function
 * And then change in includes/classes/ajax/zcAjaxInstantSearch.php around line 212 from zen_get_products_display_price_instant_search to zen_get_products_display_price
 */

  function zen_get_products_display_price_instant_search($products_id) {
    global $db, $currencies;

    $free_tag = "";
    $call_tag = "";

// 0 = normal shopping
// 1 = Login to shop
// 2 = Can browse but no prices
    // verify display of prices
      switch (true) {
        case (CUSTOMERS_APPROVAL == '1' && !zen_is_logged_in()):
        // customer must be logged in to browse
        return '';
        break;
        case (CUSTOMERS_APPROVAL == '2' && !zen_is_logged_in()):
        // customer may browse but no prices
        return TEXT_LOGIN_FOR_PRICE_PRICE;
        break;
        case (CUSTOMERS_APPROVAL == '3' and TEXT_LOGIN_FOR_PRICE_PRICE_SHOWROOM != ''):
        // customer may browse but no prices
        return TEXT_LOGIN_FOR_PRICE_PRICE_SHOWROOM;
        break;
        case (CUSTOMERS_APPROVAL_AUTHORIZATION != '0' && CUSTOMERS_APPROVAL_AUTHORIZATION != '3' && !zen_is_logged_in()):
        // customer must be logged in to browse
        return TEXT_AUTHORIZATION_PENDING_PRICE;
        break;
        case (CUSTOMERS_APPROVAL_AUTHORIZATION != '0' && CUSTOMERS_APPROVAL_AUTHORIZATION != '3' && (int)$_SESSION['customers_authorization'] > 0):
        // customer must be logged in to browse
        return TEXT_AUTHORIZATION_PENDING_PRICE;
        break;
      case (isset($_SESSION['customers_authorization']) && (int)$_SESSION['customers_authorization'] == 2):
        // customer is logged in and was changed to must be approved to see prices
        return TEXT_AUTHORIZATION_PENDING_PRICE;
        break;
        default:
        // proceed normally
        break;
      }

// show case only
    if (STORE_STATUS != '0') {
      if (STORE_STATUS == '1') {
        return '';
      }
    }

    // $new_fields = ', product_is_free, product_is_call, product_is_showroom_only';
    $product_check = $db->Execute("select products_tax_class_id, products_price, products_priced_by_attribute, product_is_free, product_is_call, product_is_always_free_shipping, products_type from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'" . " limit 1");

    // no prices on Document General
    if ($product_check->fields['products_type'] == 3) {
      return '';
    }

    $display_special_price = false;
    $display_normal_price = zen_get_products_base_price($products_id);
    $display_sale_price = zen_get_products_special_price($products_id, false);

    if ($display_sale_price !== false) {
      $display_special_price = zen_get_products_special_price($products_id, true);
    }

    $show_sale_discount = '';
    if (SHOW_SALE_DISCOUNT_STATUS == '1' and ($display_special_price != 0 or $display_sale_price != 0)) {
      // -----
      // Allows an observer to inject any override to the "Sale Price" formatting.  If an override
      // is performed, the observer sets the 'pricing_handled' value to true.
      //
      $pricing_handled = false;
      $GLOBALS['zco_notifier']->notify(
            'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_SALE', 
            array(
                'products_id' => $products_id, 
                'display_sale_price' => $display_sale_price, 
                'display_special_price' => $display_special_price,
                'display_normal_price' => $display_normal_price,
                'products_tax_class_id' => $product_check->fields['products_tax_class_id']
            ), 
            $pricing_handled,
            $show_sale_discount
      );
      if (!$pricing_handled) {
          if ($display_sale_price) {
            if (SHOW_SALE_DISCOUNT == 1) {
              if ($display_normal_price != 0) {
                $show_discount_amount = number_format(100 - (($display_sale_price / $display_normal_price) * 100),SHOW_SALE_DISCOUNT_DECIMALS);
              } else {
                $show_discount_amount = '';
              }
              $show_sale_discount = '<span class="productPriceDiscount">' . '<br>' . PRODUCT_PRICE_DISCOUNT_PREFIX . $show_discount_amount . PRODUCT_PRICE_DISCOUNT_PERCENTAGE . '</span>';

            } else {
              $show_sale_discount = '<span class="productPriceDiscount">' . '<br>' . PRODUCT_PRICE_DISCOUNT_PREFIX . $currencies->display_price(($display_normal_price - $display_sale_price), zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . PRODUCT_PRICE_DISCOUNT_AMOUNT . '</span>';
            }
          } else {
            if (SHOW_SALE_DISCOUNT == 1) {
              $show_sale_discount = '<span class="productPriceDiscount">' . '<br>' . PRODUCT_PRICE_DISCOUNT_PREFIX . number_format(100 - (($display_special_price / $display_normal_price) * 100),SHOW_SALE_DISCOUNT_DECIMALS) . PRODUCT_PRICE_DISCOUNT_PERCENTAGE . '</span>';
            } else {
              $show_sale_discount = '<span class="productPriceDiscount">' . '<br>' . PRODUCT_PRICE_DISCOUNT_PREFIX . $currencies->display_price(($display_normal_price - $display_special_price), zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . PRODUCT_PRICE_DISCOUNT_AMOUNT . '</span>';
            }
          }
        }
    }

    if ($display_special_price) {
      // -----
      // Allows an observer to inject any override to the "Special/Normal Prices'" formatting.
      //
      $pricing_handled = false;
      $GLOBALS['zco_notifier']->notify(
            'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_SPECIAL', 
            array(
                'products_id' => $products_id, 
                'display_sale_price' => $display_sale_price,
                'display_special_price' => $display_special_price,
                'display_normal_price' => $display_normal_price,
                'products_tax_class_id' => $product_check->fields['products_tax_class_id'],
                'product_is_free' => $product_check->fields['product_is_free']
            ), 
            $pricing_handled,
            $show_normal_price,
            $show_special_price,
            $show_sale_price
      );
      if (!$pricing_handled) {
          $show_normal_price = '<span class="normalprice">' . $currencies->display_price($display_normal_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . ' </span>';
          if ($display_sale_price && $display_sale_price != $display_special_price) {
            $show_special_price = '&nbsp;' . '<span class="productSpecialPriceSale">' . $currencies->display_price($display_special_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</span>';
            if ($product_check->fields['product_is_free'] == '1') {
              $show_sale_price = '<br>' . '<span class="productSalePrice">' . PRODUCT_PRICE_SALE . '<s>' . $currencies->display_price($display_sale_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</s>' . '</span>';
            } else {
              $show_sale_price = '<br>' . '<span class="productSalePrice">' . PRODUCT_PRICE_SALE . $currencies->display_price($display_sale_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</span>';
            }
          } else {
            if ($product_check->fields['product_is_free'] == '1') {
              $show_special_price = '&nbsp;' . '<span class="productSpecialPrice">' . '<s>' . $currencies->display_price($display_special_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</s>' . '</span>';
            } else {
              $show_special_price = '&nbsp;' . '<span class="productSpecialPrice">' . $currencies->display_price($display_special_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</span>';
            }
            $show_sale_price = '';
          }
      }
    } else {
      // -----
      // Allows an observer to inject any override to the "Normal Prices'" formatting.
      //
      $pricing_handled = false;
      $GLOBALS['zco_notifier']->notify(
            'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_NORMAL', 
            array(
                'products_id' => $products_id, 
                'display_sale_price' => $display_sale_price,
                'display_special_price' => $display_special_price,
                'display_normal_price' => $display_normal_price,
                'products_tax_class_id' => $product_check->fields['products_tax_class_id'],
                'product_is_free' => $product_check->fields['product_is_free']
            ), 
            $pricing_handled,
            $show_normal_price,
            $show_special_price,
            $show_sale_price
      );
      if (!$pricing_handled) {
          if ($display_sale_price) {
            $show_normal_price = '<span class="normalprice">' . $currencies->display_price($display_normal_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . ' </span>';
            $show_special_price = '';
            $show_sale_price = '<br>' . '<span class="productSalePrice">' . PRODUCT_PRICE_SALE . $currencies->display_price($display_sale_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</span>';
          } else {
            if ($product_check->fields['product_is_free'] == '1') {
              $show_normal_price = '<span class="productFreePrice"><s>' . $currencies->display_price($display_normal_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</s></span>';
            } else {
              $show_normal_price = '<span class="productBasePrice">' . $currencies->display_price($display_normal_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</span>';
            }
            $show_special_price = '';
            $show_sale_price = '';
          }
      }
    }

    if ($display_normal_price == 0) {
      // don't show the $0.00
      $final_display_price = $show_special_price . $show_sale_price . $show_sale_discount;
    } else {
      $final_display_price = $show_normal_price . $show_special_price . $show_sale_price . $show_sale_discount;
    }
    
    // -----
    // Allows an observer to inject any override to the "Free" and "Call for Price" formatting.
    //
    $tags_handled = false;
    $GLOBALS['zco_notifier']->notify(
        'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_FREE_OR_CALL', 
        array(
            'product_is_free' => $product_check->fields['product_is_free'],
            'product_is_call' => $product_check->fields['product_is_call'],
        ), 
        $tags_handled,
        $free_tag,
        $call_tag
    );
    if (!$tags_handled) {
        // If Free, Show it
        if ($product_check->fields['product_is_free'] == '1') {
          if (OTHER_IMAGE_PRICE_IS_FREE_ON=='0') {
            $free_tag = '<br>' . PRODUCTS_PRICE_IS_FREE_TEXT;
          } else {
            $free_tag = '<br>' . zen_image(DIR_WS_TEMPLATE_IMAGES . OTHER_IMAGE_PRICE_IS_FREE, PRODUCTS_PRICE_IS_FREE_TEXT);
          }
        }

        // If Call for Price, Show it
        if ($product_check->fields['product_is_call']) {
          if (PRODUCTS_PRICE_IS_CALL_IMAGE_ON=='0') {
            $call_tag = '<br>' . PRODUCTS_PRICE_IS_CALL_FOR_PRICE_TEXT;
          } else {
            $call_tag = '<br>' . zen_image(DIR_WS_TEMPLATE_IMAGES . OTHER_IMAGE_CALL_FOR_PRICE, PRODUCTS_PRICE_IS_CALL_FOR_PRICE_TEXT);
          }
        }
    }
    
    return $final_display_price . $free_tag . $call_tag; 
  }
