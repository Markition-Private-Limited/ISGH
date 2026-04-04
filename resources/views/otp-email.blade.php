<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Your OTP Code</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;700&display=swap');

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      background: #0f0f0f;
      font-family: 'DM Sans', sans-serif;
      color: #e8e8e8;
      padding: 40px 20px;
    }

    .wrapper {
      max-width: 520px;
      margin: 0 auto;
      background: #1a1a1a;
      border: 1px solid #2a2a2a;
      border-radius: 16px;
      overflow: hidden;
    }

    .header {
      background: linear-gradient(135deg, #1e1e1e 0%, #252525 100%);
      border-bottom: 1px solid #2a2a2a;
      padding: 36px 40px;
      text-align: center;
    }

    .logo-mark {
      width: 48px;
      height: 48px;
      background: #e8ff47;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 16px;
      font-size: 22px;
    }

    .header h1 {
      font-size: 22px;
      font-weight: 700;
      color: #ffffff;
      letter-spacing: -0.3px;
    }

    .header p {
      font-size: 14px;
      color: #888;
      margin-top: 6px;
    }

    .body {
      padding: 40px;
    }

    .greeting {
      font-size: 15px;
      color: #aaa;
      margin-bottom: 24px;
      line-height: 1.6;
    }

    .greeting strong {
      color: #e8e8e8;
    }

    .otp-block {
      background: #111;
      border: 1px solid #2e2e2e;
      border-radius: 12px;
      padding: 28px;
      text-align: center;
      margin: 28px 0;
    }

    .otp-label {
      font-size: 11px;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: #555;
      margin-bottom: 14px;
    }

    .otp-code {
      font-family: 'DM Mono', monospace;
      font-size: 44px;
      font-weight: 500;
      letter-spacing: 12px;
      color: #e8ff47;
      text-indent: 12px; /* compensate for letter-spacing on last char */
      white-space: nowrap;
    }

    .otp-timer {
      font-size: 12px;
      color: #555;
      margin-top: 14px;
    }

    .otp-timer span {
      color: #888;
      font-weight: 500;
    }

    .divider {
      border: none;
      border-top: 1px solid #252525;
      margin: 28px 0;
    }

    .note {
      font-size: 13px;
      color: #666;
      line-height: 1.7;
    }

    .note strong {
      color: #999;
    }

    .footer {
      background: #141414;
      border-top: 1px solid #222;
      padding: 24px 40px;
      text-align: center;
    }

    .footer p {
      font-size: 12px;
      color: #444;
      line-height: 1.6;
    }

    .footer a {
      color: #666;
      text-decoration: none;
    }

    /* Responsive styles */
    @media only screen and (max-width: 600px) {
      .otp-code {
        font-size: 20px;
        letter-spacing: 8px;
        text-indent: 8px;
      }
    }
  </style>
</head>
<body>
  <div class="wrapper">

    <div class="header">
      <div class="logo-mark">🔐</div>
      <h1>Verify your identity</h1>
      <p>One-time password for {{ $email }}</p>
    </div>

    <div class="body">
      <p class="greeting">
        Hi there,<br><br>
        You requested an OTP to verify your email address.
        Use the code below to complete the process.
        <strong>Do not share this code with anyone.</strong>
      </p>

      <div class="otp-block">
        <div class="otp-label">Your one-time password</div>
        <div class="otp-code">{{ $otp }}</div>
        <div class="otp-timer">Expires in <span>10 minutes</span></div>
      </div>

      <hr class="divider" />

      <p class="note">
        If you didn't request this code, you can safely ignore this email —
        your account remains secure.<br><br>
        <strong>Never enter this code on any site other than the one you came from.</strong>
      </p>
    </div>

    <div class="footer">
      <p>
        This is an automated message, please do not reply.<br>
        <a href="#">Unsubscribe</a> · <a href="#">Privacy Policy</a>
      </p>
    </div>

  </div>
</body>
</html>