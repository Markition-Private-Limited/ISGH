<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>We received your inquiry – ISGH Membership</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:#f4f6f4; font-family:'Helvetica Neue',Arial,sans-serif; color:#222; padding:40px 20px; }
    .wrapper { max-width:560px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.07); }
    .header { background:linear-gradient(135deg,#0a5e3a 0%,#12a060 100%); padding:32px 40px; text-align:center; }
    .header svg { display:block; margin:0 auto 16px; }
    .header h1 { font-size:22px; font-weight:700; color:#fff; }
    .header p { font-size:14px; color:rgba(255,255,255,0.8); margin-top:6px; }
    .body { padding:36px 40px; }
    .greeting { font-size:16px; font-weight:600; color:#0a5e3a; margin-bottom:16px; }
    .para { font-size:14px; color:#444; line-height:1.75; margin-bottom:16px; }
    .timeline { background:#f0f7f3; border-left:4px solid #12a060; border-radius:0 10px 10px 0; padding:16px 20px; margin:24px 0; }
    .timeline h3 { font-size:13px; font-weight:700; color:#0a5e3a; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px; }
    .timeline ul { list-style:none; padding:0; }
    .timeline ul li { font-size:13px; color:#444; line-height:1.7; padding-left:18px; position:relative; }
    .timeline ul li::before { content:'✓'; position:absolute; left:0; color:#12a060; font-weight:700; }
    .footer { background:#f0f7f3; border-top:1px solid #d4eadc; padding:20px 40px; text-align:center; }
    .footer p { font-size:12px; color:#7a9e8a; line-height:1.6; }
    @media (max-width:600px) { .body,.footer,.header { padding-left:24px; padding-right:24px; } }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <svg width="52" height="52" viewBox="0 0 52 52" fill="none">
        <circle cx="26" cy="26" r="26" fill="rgba(255,255,255,0.15)"/>
        <path d="M16 26l7 7 13-13" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <h1>Inquiry Received</h1>
      <p>Islamic Society of Greater Houston</p>
    </div>
    <div class="body">
      <p class="greeting">As-salamu alaykum, {{ $firstName }},</p>
      <p class="para">
        Thank you for reaching out to us. We have received your membership inquiry and our team will look into it promptly.
      </p>
      <div class="timeline">
        <h3>What happens next?</h3>
        <ul>
          <li>Our membership team reviews your details</li>
          <li>We verify your information in our records</li>
          <li>You will receive a response within <strong>3–5 working days</strong></li>
        </ul>
      </div>
      <p class="para">
        If you have any urgent concerns or additional information to share, please reply to this email or contact us directly at
        <a href="mailto:membership@isgh.org" style="color:#0a5e3a;text-decoration:none;font-weight:600;">membership@isgh.org</a>.
      </p>
      <p class="para">We appreciate your patience and look forward to assisting you.</p>
      <p class="para" style="margin-bottom:0;">Jazak Allah Khair,<br/><strong>ISGH Membership Team</strong></p>
    </div>
    <div class="footer">
      <p>© {{ date('Y') }} Islamic Society of Greater Houston<br/>
        <a href="mailto:membership@isgh.org" style="color:#7a9e8a;">membership@isgh.org</a>
      </p>
    </div>
  </div>
</body>
</html>
