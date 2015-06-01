<?php

namespace pulse;

/**
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */
class Module extends \Module
{

    public function __construct()
    {
        parent::__construct();
        $this->setTitle('pulse');
        $this->setProperName('Pulse');
    }

    public function getController(\Request $request)
    {
        $cmd = $request->shiftCommand();
        if ($cmd == 'admin' && \Current_User::isDeity()) {
            $admin = new \pulse\PulseAdminController($this);
            return $admin;
        } else {
            try {
                PulseController::runSchedules($request);
            } catch (Exception\PulseException $e) {
                PulseFactory::logError($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine());
                exit('Error: ' . $e->getMessage());
            } catch (\Exception $e) {
                PulseFactory::logError($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine());
                exit('An error occurred outside the scope of Pulse.');
            }
            exit;
        }
    }

}
