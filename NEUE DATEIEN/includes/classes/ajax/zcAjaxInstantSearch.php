<?php
/**
 * @package Instant Search
 * @copyright Copyright Ayoob G 2009-2011
 * Zen Cart German Specific 
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * Zen Cart German Version - www.zen-cart-pro.at
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: zcAjaxInstantSearch.php 2022-10-26 12:21:16Z webchills $
 */

class zcAjaxInstantSearch extends base
{
    /**
     * Ajax instant search function.
     */
    public function instantSearch()
    {
        global $template;

        $instantSearchResults = [];
        $wordSearch = trim($_POST['query'] ?? '');
        $wordSearchLength = strlen($wordSearch);

        if ($wordSearch !== '' && $wordSearchLength >= INSTANT_SEARCH_MIN_WORDSEARCH_LENGTH && $wordSearchLength <= INSTANT_SEARCH_MAX_WORDSEARCH_LENGTH) {
            $wordSearchPlus = trim(preg_replace('/\s+/', ' ', preg_quote($wordSearch, '&')));
            $wordSearchPlusArray = explode(' ', $wordSearchPlus);
            $wordSearchPlus = preg_replace('/\s/', '|', $wordSearchPlus);

            // search products
            $instantSearchResults = $this->execInstantSearchForType('product', $wordSearch, $wordSearchPlus, $wordSearchPlusArray);

            // search categories
            if (INSTANT_SEARCH_INCLUDE_CATEGORIES === 'true') {
                array_push($instantSearchResults, ...$this->execInstantSearchForType('category', $wordSearch, $wordSearchPlus, $wordSearchPlusArray));
            }

            // search manufacturers
            if (INSTANT_SEARCH_INCLUDE_MANUFACTURERS === 'true') {
                array_push($instantSearchResults, ...$this->execInstantSearchForType('manufacturer', $wordSearch, $wordSearchPlus, $wordSearchPlusArray));
            }

            // order by number of matches (desc),
            // then by words that occur first in the title,
            // then by number of views (desc)
            usort($instantSearchResults, static function($prod1, $prod2) {
                return
                    [$prod2['mtch'], $prod1['fsum'], $prod2['views']]
                    <=>
                    [$prod1['mtch'], $prod2['fsum'], $prod1['views']];
            });

            $instantSearchResults = array_slice($instantSearchResults, 0, INSTANT_SEARCH_MAX_NUMBER_OF_RESULTS);

            foreach ($instantSearchResults as $i => $instantSearchResult) {
                $instantSearchResults[$i] = $this->formatSearchResult($instantSearchResult, $wordSearchPlus);
            }

            ob_start();
            require $template->get_template_dir('tpl_ajax_instant_search_results.php', DIR_WS_TEMPLATE, FILENAME_DEFAULT, 'templates') . '/tpl_ajax_instant_search_results.php';
            return ob_get_clean();
        }

        return [];
    }

    /**
     * Executes the instant search on database for $type, where $type can be "product", "category" or "manufacturer".
     */
    protected function execInstantSearchForType($type, $wordSearch, $wordSearchPlus, $wordSearchPlusArray)
    {
        global $db;
        $instantSearchResults = [];

        switch ($type) {
            case 'product':
            default:
                $sql = "SELECT DISTINCT p.products_id, pd.products_name, p.products_model, p.products_image, pd.products_viewed
                        FROM " . TABLE_PRODUCTS_DESCRIPTION . " pd
                        LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = pd.products_id " .
                        (INSTANT_SEARCH_INCLUDE_OPTIONS_VALUES === 'true'
                            ? "LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa ON pa.products_id = p.products_id 
                               LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov ON pov.products_options_values_id = pa.options_values_id AND pov.language_id = :languagesId: "
                            : ""
                        ) . "
                        WHERE p.products_status <> 0
                        AND (
                                (pd.products_name REGEXP :wordSearchPlus:)" .
                                (INSTANT_SEARCH_INCLUDE_PRODUCT_MODEL === 'true' ? " OR (p.products_model REGEXP :wordSearchPlus:)" : "") .
                                (INSTANT_SEARCH_INCLUDE_OPTIONS_VALUES === 'true' ? " OR (pov.products_options_values_name REGEXP :wordSearchPlus:)" : "") . "
                            )
                        AND pd.language_id = :languagesId:";
                break;

            case 'category':
                $sql = "SELECT c.categories_id, cd.categories_name, c.categories_image
                        FROM " . TABLE_CATEGORIES . " c
                        LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id = c.categories_id
                        WHERE c.categories_status <> 0
                        AND (cd.categories_name REGEXP :wordSearchPlus:)
                        AND cd.language_id = :languagesId:";
                break;

            case 'manufacturer':
                $sql = "SELECT DISTINCT m.manufacturers_id, m.manufacturers_name, m.manufacturers_image
                        FROM " . TABLE_PRODUCTS . " p
                        LEFT JOIN " . TABLE_MANUFACTURERS . " m ON m.manufacturers_id = p.manufacturers_id
                        WHERE p.products_status <> 0
                        AND (m.manufacturers_name REGEXP :wordSearchPlus:)";
                break;
        }

        $this->notify('NOTIFY_INSTANT_SEARCH_QUERY', $type, $sql);

        $sql = $db->bindVars($sql, ':wordSearchPlus:', $wordSearchPlus, 'string');
        $sql = $db->bindVars($sql, ':languagesId:', $_SESSION['languages_id'], 'integer');

        $sqlResults = $db->Execute($sql);

        if ($sqlResults->RecordCount() > 0) {

            foreach ($sqlResults as $sqlResult) {

                $totalMatches = 0;
                $findSum      = null; // sum of first occurrences of words in the name

                switch ($type) {
                    case 'product':
                    default:
                        $id    = $sqlResult['products_id'];
                        $name  = $sqlResult['products_name'];
                        $img   = $sqlResult['products_image'];
                        $model = $sqlResult['products_model'];
                        $views = $sqlResult['products_viewed'];

                        // check if product model is an exact match
                        if (INSTANT_SEARCH_INCLUDE_PRODUCT_MODEL === 'true' && strtolower(trim(preg_replace('/\s+/', ' ', $model))) === strtolower(trim(preg_replace('/\s+/', ' ', $wordSearch)))) {
                            $totalMatches++;
                        }
                        break;

                    case 'category':
                        $id    = $sqlResult['categories_id'];
                        $name  = $sqlResult['categories_name'];
                        $img   = $sqlResult['categories_image'];
                        $model = '';
                        $views = 0;
                        break;

                    case 'manufacturer':
                        $id    = $sqlResult['manufacturers_id'];
                        $name  = $sqlResult['manufacturers_name'];
                        $img   = $sqlResult['manufacturers_image'];
                        $model = '';
                        $views = 0;
                        break;
                }

                foreach ($wordSearchPlusArray as $word) {
                    $word = stripslashes($word);
                    $wordPos = stripos($name, $word);

                    if ($wordPos !== false) { // search for word anywhere in the name
                        $totalMatches++;
                        $findSum += $wordPos;

                        $mWord = preg_quote($word, '/');
                        if (preg_match("/\b$mWord\b/i", $name)) { // exact words matches have a higher priority
                            $totalMatches++;
                        }
                    } elseif ($type === 'product' && INSTANT_SEARCH_INCLUDE_PRODUCT_MODEL === 'true' && stripos($model, $word) === 0) { // search for word at the beginning of the product model
                        $totalMatches++;
                    }
                }

                $result = [
                    'type'  => $type,
                    'id'    => $id,
                    'name'  => $name,
                    'img'   => ($img ?? ''),
                    'model' => $model,
                    'mtch'  => $totalMatches,
                    'views' => $views,
                    'fsum'  => $findSum ?? INSTANT_SEARCH_MAX_WORDSEARCH_LENGTH
                ];

                $instantSearchResults[] = $result;
            }
        }

        return $instantSearchResults;
    }

    /**
     * Prepare the search result for display.
     */
    protected function formatSearchResult($result, $wordSearchPlus)
    {
        $formattedResult = [
            'name'  => $this->highlightSearchWord($wordSearchPlus, strip_tags($result['name'])),
            'img'   => INSTANT_SEARCH_DISPLAY_IMAGE === 'true' ? zen_image(DIR_WS_IMAGES . strip_tags($result['img']), strip_tags($result['img']), SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) : '',
        ];

        switch ($result['type']) {
            case 'product':
            default:
                $formattedResult['link']  = zen_href_link(zen_get_info_page($result['id']), 'products_id=' . $result['id']);
                $formattedResult['model'] = INSTANT_SEARCH_DISPLAY_PRODUCT_MODEL === 'true'
                    ? (INSTANT_SEARCH_INCLUDE_PRODUCT_MODEL === 'true' ? $this->highlightSearchWord($wordSearchPlus, $result['model']) : $result['model'])
                    : '';
                $formattedResult['price'] = INSTANT_SEARCH_DISPLAY_PRODUCT_PRICE === 'true' ? zen_get_products_display_price_instant_search($result['id']) : '';
                break;

            case 'category':
                $formattedResult['link']  = zen_href_link(FILENAME_DEFAULT, 'cPath=' . $result['id']);
                $formattedResult['count'] = INSTANT_SEARCH_DISPLAY_CATEGORIES_COUNT === 'true' ? zen_count_products_in_category($result['id']) : '';
                break;

            case 'manufacturer':
                $formattedResult['link']  = zen_href_link(FILENAME_DEFAULT, 'manufacturers_id=' . $result['id']);
                $formattedResult['count'] = INSTANT_SEARCH_DISPLAY_MANUFACTURERS_COUNT === 'true' ? zen_count_products_for_manufacturer($result['id']) : '';
                break;
        }

        $this->notify('NOTIFY_INSTANT_SEARCH_PRIOR_ADD_RESULT', $result['type'], $result, $formattedResult);

        return $formattedResult;
    }

    /**
     * Formats in bold the $word occurrences in $text.
     */
    protected function highlightSearchWord($word, $text)
    {
        return preg_replace('/(' . str_replace('/', '\/', $word) . ')/i', '<strong>$1</strong>', $text);
    }
}
