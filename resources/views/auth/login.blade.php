<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warisan Makan - Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
          rel="stylesheet">
</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center">

        <div class="col-md-5">

            <div class="card shadow mt-5">

                <div class="card-body text-center p-5">

                    <h2>
                        Warisan Makan
                    </h2>


                    <a href="{{ url('/auth/google') }}" 
                       class="btn btn-danger w-100">

                        Sign in with Google

                    </a>


                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>