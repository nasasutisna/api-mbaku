<!doctype html>
<html lang="en">

    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link href="{{asset('/css/style.css')}}" rel="stylesheet">
        <title>MBAKU</title>
    </head>

<body>
    <center>
        <!-- BANNER -->
        <div class="banner">
            <img src="/image/mbaku_header.png" class="my-img" alt="Responsive image">
        </div>
        <!-- end baner -->


        <div class="container">
            @if( $status == 200)
                <h1>Verifikasi Email Berhasil</h1><br>
                <p>{{$msg}}</p>
            @elseif( $status == 400 )
                <h1>Data Tidak Valid</h1><br>
                <p>{{$msg}}</p>
            @elseif( $status == 500 )
                <h1>Terjadi Kesalahan, silahkan hubungi Administrator</h1><br>
                <p>{{$error_msg}}</p>
                <p class="hide">{{$stacktrace}}</p>
            @endif
        </div>

        <div class="footer">
            <p>&copy;2019 Mbaku. All rights reserved.</p>
        </div>
    </center>

    <!-- Optional JavaScript -->
</body>

</html>