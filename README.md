# myth:Events

[![Build Status](https://travis-ci.org/newmythmedia/events.svg)](https://travis-ci.org/newmythmedia/events)

This library provides a very simple, though quite powerful events system in PHP. It follows a publish/subscribe type of pattern, and uses a configuration file to store all of the event listeners in to reduce any potential coupling. 

## Setup
This library requires that a file exist someplace on the server that holds the configuration of the event listeners. The path to this file must be passed in as the only argument when instantiating the class. The definition of the event listeners is described below. 

	$events = new \Myth\Events\Events( 'path/to/config.php' );

This file will only be read in once during the lifetime of that instantiated class, and is not loaded until one of the classes methods are called. 

## Triggering Events
You can trigger an event to happen by simply calling the `trigger()` method on the class. The first parameter is the name of the event to trigger. This name can be pretty much anything you desire. The only requirement is that the listeners listen for the same name. 

	$events->trigger('new_user');

If you want to pass data along to the listeners so they have something to work with, you can pass an array of data items as the second parameter. 

	$events->trigger('new_user', [$user, $role] );

### Canceling Listener Execution

Once the event is triggered, the listeners will be sorted by priority and ran one after another, the higher priority listener going first, naturally. 

You can force the execution of the remaining listeners to stop by returning `false` from any listener. 

## Listening to Events

Listening to events simply means that you're telling the Events class that you want to be notified when that event is triggered, and that you want a chance to do something at that time. This is great for sending emails, checking authorization of the user, etc. 

You define the listener inside of the config file using the `on()` method. The first parameter is the name of the event you want to run on. The second parameter is any callable function, but will often be closures. 

	$events->on('new_user', function ($user, $role) {
		Mail::queue('new_user_email', $user);
	 });
	
You can define the order the events are executed by specifying the listener's priority in the third parameter. The priority is any whole number. The lower the number, the higher the priority.  

	$events->on('new_user', function ($user, $role) {
		Mail::queue('new_user_email', $user);
	 }, 10);

There are three pre-defined values you can use if you don't need to specify any fine-grained orders:

	EVENTS_PRIORITY_LOW = 200
	EVENTS_PRIORITY_NORMAL = 100
	EVENTS_PRIORITY_HIGH = 10


