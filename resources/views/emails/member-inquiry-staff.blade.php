<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Membership Inquiry</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:#f4f6f4; font-family:'Helvetica Neue',Arial,sans-serif; color:#222; padding:40px 20px; }
    .wrapper { max-width:560px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.07); }
    .header { background:linear-gradient(135deg,#0a5e3a 0%,#12a060 100%); padding:28px 40px; }
    .header h1 { font-size:18px; font-weight:700; color:#fff; }
    .header p { font-size:13px; color:rgba(255,255,255,0.75); margin-top:4px; }
    .body { padding:32px 40px; }
    .intro { font-size:14px; color:#444; line-height:1.7; margin-bottom:24px; }
    .detail-table { width:100%; border-collapse:collapse; margin-bottom:24px; }
    .detail-table th { text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#888; padding:6px 10px; background:#f8fdf9; border:1px solid #e5e7eb; }
    .detail-table td { font-size:14px; color:#222; padding:8px 10px; border:1px solid #e5e7eb; }
    .footer { background:#f0f7f3; border-top:1px solid #d4eadc; padding:18px 40px; text-align:center; }
    .footer p { font-size:12px; color:#7a9e8a; line-height:1.6; }
    @media (max-width:600px) { .body,.footer,.header { padding-left:24px; padding-right:24px; } }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>New Membership Inquiry</h1>
      <p>A member has submitted an inquiry from the verification page.</p>
    </div>
    <div class="body">
      <p class="intro">
        The following member was unable to upload their ID card (membership not found or ineligible) and has requested assistance.
      </p>
      <table class="detail-table">
        <tr><th>Field</th><th>Value</th></tr>
        <tr><td>First Name</td><td>{{ $data['first_name'] }}</td></tr>
        <tr><td>Last Name</td><td>{{ $data['last_name'] }}</td></tr>
        <tr><td>Street Number</td><td>{{ $data['street_number'] }}</td></tr>
        <tr><td>Date of Birth</td><td>{{ $data['date_of_birth'] }}</td></tr>
        <tr><td>Email</td><td>{{ $data['email'] }}</td></tr>
        <tr><td>Reason / Note</td><td>{{ $data['reason'] }}</td></tr>
      </table>
      <p style="font-size:13px;color:#555;">Please look into this member's record and follow up with them at the email address above.</p>
    </div>
    <div class="footer">
      <p>© {{ date('Y') }} Islamic Society of Greater Houston · membership@isgh.org</p>
    </div>
  </div>
</body>
</html>
