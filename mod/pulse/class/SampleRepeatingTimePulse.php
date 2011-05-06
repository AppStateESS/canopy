<?php

/**
 * A sample Pulse process that repeats by re-scheduling itself.
 *
 * @author Jeff Tickle <jtickle at tux dot appstate dot edu>
 */

class SampleRepeatingTimePulse extends ScheduledPulse
{
    public function __construct($id = NULL)
    {
        $this->module = 'pulse';
        $this->class_file = 'SampleRepeatingTimePulse.php';
        $this->class = 'SampleRepeatingTimePulse';

        parent::__construct($id);
    }

    public function execute()
    {
        $now = time();
        $min = date('i', $now);
        $hr = date('H', $now);
        if($min >= 15 && $min < 45) {
            $then = strtotime("$hr:45:00", $now);
        } else {
            $hr++;
            $nhr = $hr % 24;
            if($nhr == $hr) {
                $then = strtotime("$hr:15:00", $now);
            } else {
                $then = strtotime("tomorrow $nhr:15:00", $now);
            }
        }
        $date = date('m/d/Y H:i:s');
        $newdate = date('m/d/Y H:i:s', $then);
        echo "SampleRepeatingTimePulse.  The time is $date.  Next run time will be $newdate.\n";

        $sp = $this->makeClone();
        $sp->execute_at = $then;
        $sp->save();

        return TRUE;
    }
}

?>