<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>@yield('title', 'ISGH Membership Verification')</title>
</head>
<body style="margin:0;padding:32px 16px;background:#f4f6f4;font-family:'Helvetica Neue',Arial,sans-serif;color:#222;">
  <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.07);">
    <div style="background:#0a5e3a;padding:24px 32px;text-align:center;">
      <div style="font-size:18px;font-weight:700;color:#ffffff;">ISGH Membership Verification</div>
      <div style="font-size:13px;color:rgba(255,255,255,0.8);margin-top:4px;">Islamic Society of Greater Houston</div>
    </div>
    <div style="padding:28px 32px;font-size:14px;line-height:1.7;color:#333;">
      @yield('body')
    </div>
  </div>
</body>
</html>
