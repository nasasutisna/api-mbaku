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
            <img src="https://api.mbaku.online/image/mbaku_header.png" class="my-img" alt="Responsive image">
        </div>
        <!-- end baner -->


        <div class="container">
        @if( $status == 200)
                <h1>{{$msg}}</h1>
                @if({{isReject}} == "true")
                <form>
                    <div class="form-group">
                        <label for="nama">Nama Anda:</label>
                        <input type="text" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat anda:</label>
                        <input type="text" class="form-control" id="alamat">
                    </div>		
                    <button type="submit" class="btn btn-default">Submit</button>
                </form>
                @endif
            @elseif( $status == 400 )
                <h1>{{$msg}}</h1>
            @elseif( $status == 500 )
                <h1>{{$msg}}</h1><br>
                <p>{{$error_msg}}</p>
                <p style="display: none">{{$stacktrace}}</p>
            @endif
        </div>

        <div class="footer">
            <p>&copy;2019 Mbaku. All rights reserved.</p>
        </div>
    </center>

    <!-- Optional JavaScript -->
</body>

</html>