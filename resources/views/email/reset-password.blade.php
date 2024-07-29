<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Email</title>
</head>
<body style="font-family:Arial, Helvetica, sans-serif; font: size 16px;">

<p>hello,{{$formData['user']->name}}</p>
<h1>you have requested to change password</h1>
<p>Please click the link below to reset the password</p>
<a href="{{route('front.resetPassword',$formData['token'])}}">click here</a>
<p>thanks</p>
</body>
</html>