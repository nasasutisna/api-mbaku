<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="{{asset('/css/style2.css')}}" rel="stylesheet">
    <title>Approval User</title>
</head>

<body>
    <center>

<!-- BANNER -->
    <div class="banner" style="background-color: #e0574d;">
    <img src="https://1.bp.blogspot.com/-1vokX6HMcFI/XW4lsu1_96I/AAAAAAAAAko/3oDOwXZYItchxoEHAMBHRyLu0eyhf-9rACLcBGAs/s1600/logo_mbaku.png" style="border-radius: 10px; width: 100px; margin: 15px 0px 15px;" class="my-img" alt="Responsive image">
    </div>
<!-- end baner -->


    <div class="container table-responsive" style="margin-top: 30px; margin-bottom: 18px;">
        <table class="table table-hover my-table">
        
            <thead style="background-color: #e0574d;padding: 4px;">
              <tr>
                <th scope="col" colspan="2"  style="color: white;">Form Approval Account Premium</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th scope="col" id="text-th" style="text-align: left">Member ID</th>
                <td>{{$memberID}}</td>
              </tr>
              <tr>
                <th scope="col" id="text-th" style="text-align: left">Nama</th>
                <td>{{$memberFirstName}} {{$memberLastName}}</td>
              </tr>
              <tr>
                <th scope="col" id="text-th" style="text-align: left">Jenis Kelamin</th>
                <td>{{$memberGender}}</td>
              </tr>
              <tr>
                <th scope="col" id="text-th" style="text-align: left">Nomor Telpon</th>
                <td>{{$memberPhone}}</td>
              </tr>
              <tr>
                <th scope="col" id="text-th" style="text-align: left">Email</th>
                <td>{{$memberEmail}}</td>
              </tr>
              <tr>
                <th scope="col" id="text-th" style="text-align: left">Alamat</th>
                <td>{{$memberAddress}}</td>
              </tr>
              <br>
              <tr>
                <th scope="col" id="text-th" style="text-align: left">Kontak Orang Terdekat (Keluarga)</th>
                <td>:</td>
              </tr>
              <tr>
                <th scope="col" id="text-th" style="text-align: left">Nama</th>
                <td>{{$emergencyName}}</td>
              </tr>
              <tr>
                <th scope="col" id="text-th" style="text-align: left">Nomor HP</th>
                <td>{{$emergencyNumber}}</td>
              </tr>
              <tr>
                <th scope="col" id="text-th" style="text-align: left">Peran</th>
                <td>{{$emergencyRole}}</td>
              </tr>
              <thead style="padding: 8px;">
                <tr>
                  <th scope="col" colspan="2" class="text-right" style="padding: 15px;">
                    <a href="http://localhost/api-perpustakaan/public/api/v1/member/approved/{{$memberPremiumID}}" style="color: white; background-color: #3490DC;border-top: 5px solid #3490DC;border-right: 18px solid #3490DC;border-bottom: 5px solid #3490DC;border-left: 18px solid #3490DC;" >TERIMA</a>
                    <a href="http://localhost/api-perpustakaan/public/api/v1/member/rejected/{{$memberPremiumID}}" style="color: white; background-color: #E3342F;border-top: 5px solid #E3342F;border-right: 18px solid #E3342F;border-bottom: 5px solid #E3342F;border-left: 18px solid #E3342F;" >TOLAK</a>
                    <!-- <button class="btn btn-success" name="isApprove" type="button" href="/member/approved/{{$memberPremiumID}}" >TERIMA</button>
                    <button class="btn btn-primary my-btn" name="isApprove"  type="button" href="/member/rejected/{{$memberPremiumID}}">TOLAK</button>  -->
                  </th>
                </tr>
              </thead>
            </tbody>
            
            
          </table>
    </div>


<!-- footer -->
    <div class="footer" style="position: fixed; left: 0; bottom: 0; width: 100%; background-color: #e0574d; color: white; text-align: center;">
        <p style="margin-top: 17px;">&copy;2019 Mbaku. All rights reserved.</p>
    </div>
<!-- End of Footer -->

</center>

</body>

</html>