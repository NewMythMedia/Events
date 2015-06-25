<?php namespace Myth\Events;
/**
 * Myth/Events
 *
 * A fast, flexible publish/subscribe events library with prioritization for PHP.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package     Sprint
 * @author      Lonnie Ezell
 * @copyright   Copyright 2015, New Myth Media, LLC (http://newmythmedia.com)
 * @license     http://opensource.org/licenses/MIT  (MIT)
 * @link        https://github.com/newmythmedia/events
 * @since       Version 1.0
 */

define('EVENTS_PRIORITY_LOW', 200);
define('EVENTS_PRIORITY_NORMAL', 100);
define('EVENTS_PRIORITY_HIGH', 10);

class Events {

    /**
     * The list of listeners.
     *
     * @var array
     */
    protected $listeners = [];

    /**
     * The server path to the configuration file.
     *
     * @var string
     */
    protected $config_path;

    /**
     * Flag to let us know if we've read from the config file
     * and have all of the defined events.
     *
     * @var bool
     */
    protected $have_read_from_file = false;

    //--------------------------------------------------------------------

    /**
     * Simply stores the path to the configuration file that contains
     * the 'on()' calls that define the subscribers. We force these to
     * be stored within a file for maximum performance. If no calls are
     * ever made, then no time is spent reading the file, anc no memory
     * is used to store info that's not needed.
     *
     * @param $config_path
     */
    public function __construct($config_path)
    {
        $this->config_path = $config_path;
    }

    //--------------------------------------------------------------------


    /**
     * Registers an action to happen on an event. The action can be any sort
     * of callable:
     *
     *  Events::on('create', 'myFunction');               // procedural function
     *  Events::on('create', ['myClass', 'myMethod']);    // Class::method
     *  Events::on('create', [$myInstance, 'myMethod']);  // Method on an existing instance
     *  Events::on('create', function() {});              // Closure
     *
     * @param $event_name
     * @param callable $callback
     * @param int $priority
     */
    public function on($event_name, callable $callback, $priority=EVENTS_PRIORITY_NORMAL)
    {
        if (! isset($this->listeners[$event_name]))
        {
            $this->listeners[$event_name] = [
                true,   // If there's only 1 item, it's sorted.
                [$priority],
                [$callback]
            ];
        }
        else
        {
            $this->listeners[$event_name][0] = false; // Not sorted
            $this->listeners[$event_name][1][] = $priority;
            $this->listeners[$event_name][2][] = $callback;
        }
    }

    //--------------------------------------------------------------------

    /**
     * Runs through all subscribed methods running them one at a time,
     * until either:
     *  a) All subscribers have finished or
     *  b) a method returns false, at which point execution of subscribers stops.
     *
     * @param $event_name
     * @return bool
     */
    public function trigger($event_name, array $arguments = [])
    {
        // Read in our config/events file so that we have them all!
        if (! $this->have_read_from_file)
        {
            $this->readConfigFile();
        }

        foreach ($this->listeners($event_name) as $listener)
        {
            if (! is_callable($listener)) continue;

            $result = call_user_func_array($listener, $arguments);

            if ($result === false)
            {
                return false;
            }
        }

        return true;
    }

    //--------------------------------------------------------------------

    /**
     * Returns an array of listeners for a single event. They are
     * sorted by priority.
     *
     * If the listener could not be found, returns FALSE, or TRUE if
     * it was removed.
     *
     * @param $event_name
     * @return array
     */
    public function listeners($event_name)
    {
        // Read in our config/events file so that we have them all!
        if (! $this->have_read_from_file)
        {
            $this->readConfigFile();
        }

        if (! isset($this->listeners[$event_name]))
        {
            return [];
        }

        // The list is not sorted
        if (! $this->listeners[$event_name][0])
        {
            // Sort it!
            array_multisort($this->listeners[$event_name][1], SORT_NUMERIC, $this->listeners[$event_name][2]);

            // Mark it as sorted already!
            $this->listeners[$event_name][0] = true;
        }

        return $this->listeners[$event_name][2];
    }

    //--------------------------------------------------------------------

    /**
     * Removes a single listener from an event.
     *
     * If the listener couldn't be found, returns FALSE, else TRUE if
     * it was removed.
     *
     * @param $event_name
     * @param callable $listener
     * @return bool
     */
    public function removeListener($event_name, callable $listener)
    {
        // Read in our config/events file so that we have them all!
        if (! $this->have_read_from_file)
        {
            $this->readConfigFile();
        }

        if (! isset($this->listeners[$event_name]))
        {
            return false;
        }

        foreach ($this->listeners[$event_name][2] as $index => $check)
        {
            if ($check === $listener)
            {
                unset($this->listeners[$event_name][1][$index]);
                unset($this->listeners[$event_name][2][$index]);

                return true;
            }
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * Removes all listeners.
     *
     * If the event_name is specified, only listeners for that event will be
     * removed, otherwise all listeners for all events are removed.
     *
     * @param null $event_name
     */
    public function removeAllListeners($event_name=null)
    {
        // Read in our config/events file so that we have them all!
        if (! $this->have_read_from_file)
        {
            $this->readConfigFile();
        }

        if (! is_null($event_name))
        {
            unset($this->listeners[$event_name]);
        }
        else {
            $this->listeners = [];
        }

        return $this;
    }

    //--------------------------------------------------------------------

    //--------------------------------------------------------------------
    // Utility Methods
    //--------------------------------------------------------------------

    /**
     * reads in our configuration file. Makes a new $event variable
     * available in our classes when loading the file.
     */
    protected function readConfigFile()
    {
        if (! is_file($this->config_path))
        {
            throw new \RuntimeException('Invalid Events configuration path: '. $this->config_path);
        }

        extract(['events' => $this]);

        include $this->config_path;

        $this->have_read_from_file = true;
    }

    //--------------------------------------------------------------------


}