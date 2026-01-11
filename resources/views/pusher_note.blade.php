<!DOCTYPE html>
<html>

<head>
    <title>Pusher Test</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>

        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        var pusher = new Pusher('ae9c5e13913dad07f844', {
            cluster: 'mt1'
        });

        var channel = pusher.subscribe('my-channel');
        channel.bind('form-submitted', function(data) {
            Swal.fire({
                title: "Drag me!",
                icon: "success"
            });
            // alert('mmmm');
            document.getElementsByClassName('ttt')[0].style.color = "red";
        });
    </script>
</head>

<body>
    <h1 class="ttt">Pusher Test</h1>
    <p>
        Try publishing an event to channel <code>my-channel</code>
        with event name <code>form-submitted</code>.
    </p>
</body>

</html>
