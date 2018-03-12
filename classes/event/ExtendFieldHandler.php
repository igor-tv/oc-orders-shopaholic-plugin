<?php namespace Lovata\OrdersShopaholic\Classes\Event;

use System\Models\MailTemplate;

use Lovata\Toolbox\Classes\Helper\UserHelper;
use Lovata\Shopaholic\Models\Settings;
use Lovata\OrdersShopaholic\Models\Order;
use Lovata\OrdersShopaholic\Models\Property;
use Lovata\OrdersShopaholic\Controllers\Orders;
use Lovata\OrdersShopaholic\Classes\CartProcessor;

/**
 * Class ExtendCategoryModel
 * @package Lovata\OrdersShopaholic\Classes\Event
 * @author  Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class ExtendFieldHandler
{
    /**
     * Add listeners
     * @param \Illuminate\Events\Dispatcher $obEvent
     */
    public function subscribe($obEvent)
    {
        $obEvent->listen('backend.form.extendFields', function ($obWidget) {
            $this->extendSettingsFields($obWidget);
            $this->extendOrderFields($obWidget);
        });
    }

    /**
     * Extend settings fields
     * @param \Backend\Widgets\Form $obWidget
     */
    protected function extendSettingsFields($obWidget)
    {
        // Only for the Settings controller
        if (!$obWidget->getController() instanceof \System\Controllers\Settings || $obWidget->isNested) {
            return;
        }

        // Only for the Settings model
        if (!$obWidget->model instanceof Settings) {
            return;
        }

        $arAdditionFieldList = [
            'cart_cookie_lifetime'                 => [
                'tab'     => 'lovata.ordersshopaholic::lang.tab.order_settings',
                'label'   => 'lovata.ordersshopaholic::lang.settings.cart_cookie_lifetime',
                'span'    => 'left',
                'type'    => 'number',
                'default' => CartProcessor::$iCookieLifeTime,
            ],
            'check_offer_quantity'                 => [
                'tab'   => 'lovata.ordersshopaholic::lang.tab.order_settings',
                'label' => 'lovata.ordersshopaholic::lang.settings.check_offer_quantity',
                'span'  => 'left',
                'type'  => 'checkbox',
            ],
            'decrement_offer_quantity'             => [
                'tab'   => 'lovata.ordersshopaholic::lang.tab.order_settings',
                'label' => 'lovata.ordersshopaholic::lang.settings.decrement_offer_quantity',
                'span'  => 'left',
                'type'  => 'checkbox',
            ],
            'send_email_after_creating_order'      => [
                'tab'   => 'lovata.ordersshopaholic::lang.tab.order_settings',
                'label' => 'lovata.ordersshopaholic::lang.settings.send_email_after_creating_order',
                'span'  => 'left',
                'type'  => 'checkbox',
            ],
            'creating_order_mail_template'         => [
                'tab'         => 'lovata.ordersshopaholic::lang.tab.order_settings',
                'label'       => 'lovata.ordersshopaholic::lang.settings.creating_order_mail_template',
                'span'        => 'left',
                'type'        => 'dropdown',
                'emptyOption' => 'lovata.toolbox::lang.field.empty',
                'options'     => MailTemplate::listAllTemplates(),
                'trigger'     => [
                    'action'    => 'show',
                    'field'     => 'send_email_after_creating_order',
                    'condition' => 'checked',
                ],
            ],
            'creating_order_manager_mail_template' => [
                'tab'         => 'lovata.ordersshopaholic::lang.tab.order_settings',
                'label'       => 'lovata.ordersshopaholic::lang.settings.creating_order_manager_mail_template',
                'span'        => 'left',
                'type'        => 'dropdown',
                'emptyOption' => 'lovata.toolbox::lang.field.empty',
                'options'     => MailTemplate::listAllTemplates(),
                'trigger'     => [
                    'action'    => 'show',
                    'field'     => 'send_email_after_creating_order',
                    'condition' => 'checked',
                ],
            ],
            'creating_order_manager_email_list'    => [
                'tab'     => 'lovata.ordersshopaholic::lang.tab.order_settings',
                'label'   => 'lovata.ordersshopaholic::lang.settings.creating_order_manager_email_list',
                'comment' => 'lovata.toolbox::lang.field.email_list_description',
                'span'    => 'left',
                'type'    => 'text',
                'trigger' => [
                    'action'    => 'show',
                    'field'     => 'send_email_after_creating_order',
                    'condition' => 'checked',
                ],
            ],
        ];

        $obWidget->addTabFields($arAdditionFieldList);
        $sUserPluginName = UserHelper::instance()->getPluginName();
        if (empty($sUserPluginName)) {
            return;
        }

        // Add an extra birthday field
        $obWidget->addTabFields([
            'create_new_user'     => [
                'tab'   => 'lovata.ordersshopaholic::lang.tab.order_settings',
                'label' => 'lovata.ordersshopaholic::lang.settings.create_new_user',
                'span'  => 'left',
                'type'  => 'checkbox',
            ],
            'generate_fake_email' => [
                'tab'   => 'lovata.ordersshopaholic::lang.tab.order_settings',
                'label' => 'lovata.ordersshopaholic::lang.settings.generate_fake_email',
                'span'  => 'left',
                'type'  => 'checkbox',
            ],
        ]);
    }

    /**
     * Extend fields for Order model
     * @param \Backend\Widgets\Form $obWidget
     */
    public function extendOrderFields($obWidget)
    {
        if (!$obWidget->getController() instanceof Orders || $obWidget->isNested) {
            return;
        }

        // Only for the Order model
        if (!$obWidget->model instanceof Order || $obWidget->context != 'update') {
            return;
        }

        $obPropertyList = Property::active()->orderBy('sort_order', 'asc')->get();
        if ($obPropertyList->isEmpty()) {
            return;
        }

        //Get widget data for properties
        $arAdditionPropertyData = [];
        /** @var Property $obProperty */
        foreach ($obPropertyList as $obProperty) {
            $arPropertyData = $obProperty->getWidgetData();
            if (!empty($arPropertyData)) {
                $arAdditionPropertyData[Property::NAME.'['.$obProperty->code.']'] = $arPropertyData;
            }
        }

        // Add fields
        if (!empty($arAdditionPropertyData)) {
            $obWidget->addTabFields($arAdditionPropertyData);
        }
    }
}