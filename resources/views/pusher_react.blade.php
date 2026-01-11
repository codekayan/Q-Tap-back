<!DOCTYPE html>

<head>
    <title>Pusher Test</title>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script>
        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        var pusher = new Pusher('63b495891d2c3cff9d36', {
            cluster: 'eu'
        });

        var channel = pusher.subscribe('notify-channel');
        channel.bind('form-submitted', function(data) {
            
            alert(JSON.stringify(data));
        });
    </script>
</head>


<!------------

    type1 ->notfy
    type2 ->chat
    type3 ->add_order

    ----------->

<body>
    <h1>Pusher Test</h1>
    <p>
        Try publishing an event to channel <code>my-channel</code>
        with event name <code>my-event</code>.
    </p>
</body>
