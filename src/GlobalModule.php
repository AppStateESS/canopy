<?php
namespace Canopy;

/**
 * This is a faux module used for purposes of extracting site settings.
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */

class GlobalModule extends Module implements SettingDefaults {

    /**
     * Eventually to be handled by UI
     */
    public function getSettingDefaults()
    {
        $settings['language'] = DEFAULT_LANGUAGE;
        return $settings;
    }

    public function getTitle()
    {
        return 'Global';
    }

    public function getController(Request $request)
    {
        // TODO ...?
    }

    public function run()
    {

    }

    public function init()
    {

    }

}
