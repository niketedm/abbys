<?php
/**
* BSS Commerce Co.
*
* NOTICE OF LICENSE
*
* This source file is subject to the EULA
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://bsscommerce.com/Bss-Commerce-License.txt
*
* =================================================================
*                 MAGENTO EDITION USAGE NOTICE
* =================================================================
* This package designed for Magento COMMUNITY edition
* BSS Commerce does not guarantee correct work of this extension
* on any other Magento edition except Magento COMMUNITY edition.
* BSS Commerce does not provide extension support in case of
* incorrect edition usage.
* =================================================================
*
* @category   BSS
* @package    Bss_ConfiguableGridView
* @author     Extension Team
* @copyright  Copyright (c) 2015-2016 BSS Commerce Co. ( http://bsscommerce.com )
* @license    http://bsscommerce.com/Bss-Commerce-License.txt
*/
namespace Bss\ConfiguableGridView\Block\Product\View;
/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Configurable extends \Magento\Swatches\Block\Product\Renderer\Configurable
{
    protected function getRendererTemplate()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create('Bss\ConfiguableGridView\Helper\Data');
        if($helper->isEnabled()) {
            return 'Bss_ConfiguableGridView::product/view/configurable.phtml';
        }

        return $this->isProductHasSwatchAttribute ? self::SWATCH_RENDERER_TEMPLATE : self::CONFIGURABLE_RENDERER_TEMPLATE;
    }


    public function getAllowProducts()
    {
        if (!$this->hasAllowProducts()) {
            $products = [];
            $skipSaleableCheck = $this->catalogProduct->getSkipSaleableCheck();
            $allProducts = $this->getProduct()->getTypeInstance()->getUsedProducts($this->getProduct(), null);

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $helper = $objectManager->create('Bss\ConfiguableGridView\Helper\Data');
            foreach ($allProducts as $product) {
                if($helper->isShowConfig('stock_availability') && $helper->isShowConfig('out_stock')) {
                    $products[] = $product;
                }else {
                    if ($product->isSaleable() || $skipSaleableCheck) {
                        $products[] = $product;
                    }
                }
            }
            $this->setAllowProducts($products);
        }
        return $this->getData('allow_products');
    }

    public function getConfiguableGridViewData() {
        $currentProduct = $this->getProduct();
        $optionPrice = $this->getOptionPrices();
        $options = $this->helper->getOptions($currentProduct, $this->getAllowProducts());
        $attributesData = $this->configurableAttributeData->getAttributesData($currentProduct, $options);

        $assc_product_data = array();
        $sort = false;
        foreach ($this->getAllowProducts() as $product) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $stockRegistry = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface');
            $stockitem = $stockRegistry->getStockItem(
                $product->getId(),
                $product->getStore()->getWebsiteId()
                );

            $data = $options['index'][$product->getId()];
            $data['stock'] = $stockitem->getData();
            $data['price'] = array('old_price' => $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(), 'basePrice' => $product->getPriceInfo()->getPrice('final_price')->getAmount()->getBaseAmount() , 'finalPrice' => $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue());
            foreach ($options['index'][$product->getId()] as $key => $option) {
                if(!$sort) $sort = $key;
                $search = array_search($option, array_column($attributesData['attributes'][$key]['options'],'id'));
                $data['attributes'][$key] = $attributesData['attributes'][$key]['options'][$search];
                $data['attributes'][$key]['code'] = $attributesData['attributes'][$key]['code'];
            }
            $data['tier_price'] = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
            $assc_product_data[] = $data;
        }
        
        $this->array_sort_by_column($assc_product_data, $sort);
        return $assc_product_data;
    }

    public function getDetailHtml() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create('Bss\ConfiguableGridView\Helper\Data');

        $html = '<div class="total-area"><div class="qty-detail"></div><div class="qty-total"><span class="label"><b>' . __('Total Qty:') . '</b></span><span class="value">0</span></div><div class="price-total"><span class="label"><b>' . __('Total:') . '</b></span><span class="value">' . $helper->getDisplayPriceWithCurrency(0) . '</span></div></div>';

        return $html;
    }

    protected function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
        $sort_col = array();
        foreach ($arr as $key=> $row) {
            $sort_col[$key] = $row[$col];
        }

        array_multisort($sort_col, $dir, $arr);
    }

    public function getCurentVersion() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version = $productMetadata->getVersion();

        return $version;
    }
}
