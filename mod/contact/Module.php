<?php

/**
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */

namespace contact;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Module extends \Module implements \SettingDefaults
{

    public function __construct()
    {
        parent::__construct();
        $this->setTitle('contact');
        $this->setProperName('Contact');
    }

    public function getController(\Request $request)
    {
        $cmd = $request->shiftCommand();
        if ($cmd == 'admin') {
            if (\Current_User::allow('contact')) {
                $controller = $request->shiftCommand();

                switch ($controller) {
                    case 'map':
                        $map = new \contact\Controller\Map($this);
                        return $map;

                    case 'social':
                        $social = new \contact\Controller\Social($this);
                        return $social;

                    case 'contactinfo':
                    default:
                        $contact_info = new \contact\Controller\ContactInfo($this);
                        return $contact_info;
                }
            } else {
                \Current_User::requireLogin();
            }
        } else {
            $controller = new \contact\Controller\User($this);
            return $controller;
        }
    }

    public function runTime(\Request $request)
    {
        Factory\ContactInfo::showSiteContact();
        $request = \Server::getCurrentRequest();
        $module = $request->getModule();
        if ($module !== 'contact') {
            $content = Factory\ContactInfo::display();
            if (!empty($content)) {
                \Layout::add($content, 'contact', 'box');
            }
        }
    }

    public function getSettingDefaults()
    {
        // ContactInfo
        $settings['building'] = null;
        $settings['room_number'] = null;
        $settings['phone_number'] = null;
        $settings['fax_number'] = null;
        $settings['email'] = null;
        $settings['site_contact_name'] = null;
        $settings['site_contact_email'] = null;
        $settings['other_information'] = null;

        // Physical Address
        $settings['street'] = null;
        $settings['post_box'] = null;
        $settings['city'] = null;
        $settings['state'] = 'NC';
        $settings['zip'] = null;

        // Offsite
        $settings['social'] = null;

        // Map
        $settings['thumbnail_map'] = null;
        $settings['latitude'] = null;
        $settings['longitude'] = null;
        $settings['full_map_link'] = null;

        $settings['zoom'] = 17;
        $settings['dimension_x'] = '300';
        $settings['dimension_y'] = '300';

        return $settings;
    }

}
