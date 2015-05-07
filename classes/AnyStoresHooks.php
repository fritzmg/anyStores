<?php // with ♥ and Contao

/**
 * anyStores for Contao Open Source CMS
 *
 * @copyright   (c) 2014, 2015 Tastaturberuf <mail@tastaturberuf.de>
 * @author      Daniel Jahnsmüller <mail@jahnsmueller.net>
 * @license     http://opensource.org/licenses/lgpl-3.0.html
 * @package     anyStores
 */


namespace Tastaturberuf;


class AnyStoresHooks extends \Controller
{

    /**
     * The "generateBreadcrumb" hook allows to modify the breadcrumb navigation.
     * It passes the navigation items and the frontend module as arguments and
     * expects the items as return value.
     *
     * @param array $arrItems
     * @param \Module $objModule
     * @return array
     */
    public function generateBreadcrumb($arrItems, \Module $objModule)
    {
        $strAlias = \Input::get('auto_item') ?: \Input::get('store');

        $objLocation = AnyStoresModel::findPublishedByIdOrAlias($strAlias);

        if ( !$objLocation )
        {
            return $arrItems;
        }

        $intLastKey = (int) count($arrItems) - 1;
        $arrItems[$intLastKey] = array
        (
            'isActive' => 1,
            'title'    => $objLocation->name
        );

        return $arrItems;
    }


    /**
     * The "replaceInsertTags" hook is triggered when an unknown insert tag is
     * found. It passes the insert tag as argument and expects the replacement
     * value or "false" as return value.
     *
     * anystores::details:[ID]
     * anystores::count:[CategoryID|all]
     *
     * @todo
     * anystores:phone:[ID]
     * anystores:fax:[ID]
     * ...
     *
     * @param string $strTag
     * @return bool | string
     */
    public function replaceInsertTags($strTag)
    {
        $arrElements = explode('::', $strTag);

        if ( $arrElements[0] != 'anystores' )
        {
            return false;
        }

        $arrKeys = explode(':', $arrElements[1]);

        $GLOBALS['TL_DEBUG'][_][] = $arrKeys;

        try
        {
            switch( $arrKeys[0] )
            {
                // get store details
                case 'details':

                    if ( ($objStore = AnyStoresModel::findPublishedByIdOrAlias($arrKeys[1])) !== null )
                    {
                        // Location template
                        $objLocationTemplate = new \FrontendTemplate('anystores_details');
                        $objLocationTemplate->setData($objStore->loadDetails()->row());

                        // Module template
                        $objModuleTemplate = new \FrontendTemplate('mod_anystores_inserttag');
                        $objModuleTemplate->store = $objLocationTemplate;

                        // Parse module template
                        $strTemplate = $objModuleTemplate->parse();
                        $GLOBALS['TL_DEBUG'][_][] = $strTemplate;
                        $strTemplate = parent::replaceInsertTags($strTemplate);
                        $GLOBALS['TL_DEBUG'][_][] = $strTemplate;

                        return $strTemplate;
                    }

                    return false;

                // count category items
                case 'count':

                    if ( $arrKeys[1] == 'all' )
                    {
                        return AnyStoresModel::countAll();
                    }
                    else
                    {
                        return AnyStoresModel::countBy('pid', $arrKeys[1]);
                    }

                default:
                    return false;
            }
        }
        catch (\Exception $e)
        {
            \System::log('Replace insert tag error: '.$e->getMessage(), __METHOD__, TL_ERROR);
        }

        return false;
    }

}